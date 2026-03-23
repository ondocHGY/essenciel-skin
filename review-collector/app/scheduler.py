"""스케줄러 관리"""

import os
import json
import logging
from datetime import datetime
from concurrent.futures import ThreadPoolExecutor, TimeoutError as FuturesTimeoutError
from apscheduler.schedulers.background import BackgroundScheduler
from apscheduler.triggers.cron import CronTrigger
from apscheduler.triggers.interval import IntervalTrigger

from app.config import settings
from app.database import SessionLocal
from app.models import ProductReviewSource, ProductReview, SyncLog
from app.scrapers import get_scraper, get_supported_platforms

logger = logging.getLogger(__name__)

scheduler: BackgroundScheduler = None


def _sync_platform_with_timeout(platform: str, db, trigger_type='scheduled'):
    """플랫폼 동기화를 별도 스레드에서 타임아웃 적용하여 실행"""
    timeout = settings.SYNC_TIMEOUT_SECONDS

    def _run():
        sync_platform(platform, db, trigger_type=trigger_type)

    try:
        with ThreadPoolExecutor(max_workers=1) as executor:
            future = executor.submit(_run)
            future.result(timeout=timeout)
    except FuturesTimeoutError:
        logger.error(f"[{platform}] 동기화 타임아웃 ({timeout}초 초과) - 강제 스킵")
    except Exception as e:
        logger.error(f"[{platform}] 동기화 실패: {e}")


def sync_all_platforms():
    """모든 지원 플랫폼 동기화"""
    logger.info("=== 스케줄된 동기화 시작 ===")

    platforms = get_supported_platforms()
    logger.info(f"지원 플랫폼: {platforms}")

    db = SessionLocal()
    try:
        for platform in platforms:
            _sync_platform_with_timeout(platform, db, trigger_type='scheduled')

    finally:
        db.close()

    logger.info("=== 스케줄된 동기화 완료 ===")


def sync_platform(platform: str, db, trigger_type='manual'):
    """플랫폼 동기화 (소스 불필요)"""
    logger.info(f"[{platform}] 동기화 시작")

    if platform not in get_supported_platforms():
        logger.warning(f"[{platform}] 지원하지 않는 플랫폼")
        return

    # SyncLog 생성
    sync_log = SyncLog(
        review_source_id=None,
        platform=platform,
        trigger_type=trigger_type,
        status='running',
        started_at=datetime.now(),
    )
    db.add(sync_log)
    db.commit()
    db.refresh(sync_log)

    try:
        scraper = get_scraper(platform)
        result = scraper.fetch_reviews()

        if not result["success"]:
            logger.error(f"[{platform}] 수집 실패: {result['message']}")
            _complete_sync_log(db, sync_log, 'failed', error_message=result['message'])
            return

        reviews = result.get("reviews", [])
        added, updated = save_reviews(platform, reviews, db)

        _complete_sync_log(db, sync_log, 'success',
                           reviews_added=added, reviews_updated=updated,
                           total_reviews=len(reviews))

        logger.info(f"[{platform}] 동기화 완료: {added}개 추가, {updated}개 업데이트")

    except Exception as e:
        logger.error(f"[{platform}] 동기화 오류: {e}")
        db.rollback()
        # rollback 후 새 세션에서 로그 업데이트
        try:
            db.refresh(sync_log)
            _complete_sync_log(db, sync_log, 'failed', error_message=str(e))
        except Exception:
            logger.error(f"[{platform}] SyncLog 업데이트 실패")


def _complete_sync_log(db, sync_log: SyncLog, status: str,
                       reviews_added: int = 0, reviews_updated: int = 0,
                       total_reviews: int = 0, error_message: str = None):
    """SyncLog 완료 처리"""
    now = datetime.now()
    sync_log.status = status
    sync_log.completed_at = now
    sync_log.duration_seconds = int((now - sync_log.started_at).total_seconds())
    sync_log.reviews_added = reviews_added
    sync_log.reviews_updated = reviews_updated
    sync_log.total_reviews = total_reviews
    sync_log.error_message = error_message
    db.commit()


def save_reviews(platform: str, reviews: list, db) -> tuple:
    """리뷰 저장

    - platform_product_code: 플랫폼별 상품코드 저장 (Qoo10, 네이버 등 각각의 코드)
    - product_id: 2단계 매칭 시도, 실패하면 NULL (나중에 관리자에서 매칭)
      1단계: product_review_sources에서 platform + external_id로 product_id 획득
      2단계: products 테이블에서 code 매칭
    """
    import hashlib
    from sqlalchemy import text

    added = 0
    updated = 0

    # product_code -> product_id 캐시 (DB 조회 최소화)
    product_cache = {}

    # 리뷰 소스 캐시: platform + external_id -> [product_id, ...] (다대다)
    source_cache = {}
    sources = db.query(ProductReviewSource).filter(
        ProductReviewSource.platform == platform,
        ProductReviewSource.is_active == True
    ).all()
    for src in sources:
        if src.external_id and src.product_id:
            source_cache.setdefault(src.external_id, []).append(src.product_id)

    for review_data in reviews:
        # external_id 생성
        external_id = review_data.get("external_id")
        if not external_id:
            content_hash = hashlib.md5(review_data.get("content", "").encode()).hexdigest()[:16]
            external_id = f"{platform}_{content_hash}"

        # 플랫폼별 상품코드 및 상품명
        platform_product_code = review_data.get("product_code")
        product_name = review_data.get("product_name")

        # product_ids 결정: 리뷰 데이터에 직접 지정된 product_id 우선 사용
        product_ids = []

        direct_product_id = review_data.get("product_id")
        if direct_product_id:
            product_ids = [direct_product_id]
        elif platform_product_code:
            # 1단계: product_review_sources에서 external_id 매칭 (여러 product_id 가능)
            if platform_product_code in source_cache:
                product_ids = source_cache[platform_product_code]

            # 2단계: products 테이블에서 code 매칭
            if not product_ids:
                if platform_product_code not in product_cache:
                    result = db.execute(
                        text("SELECT id FROM products WHERE code = :code LIMIT 1"),
                        {"code": platform_product_code}
                    ).fetchone()
                    product_cache[platform_product_code] = result[0] if result else None

                if product_cache[platform_product_code]:
                    product_ids = [product_cache[platform_product_code]]

            if not product_ids:
                logger.debug(f"상품코드 '{platform_product_code}' 매칭 대기")

        # product_ids가 비어있으면 product_id=None으로 1건 저장
        if not product_ids:
            product_ids = [None]

        # product_id가 매칭된 경우, 기존 NULL 버전이 있으면 업데이트로 승격
        first_assigned = False
        for product_id in product_ids:
            # 1차: 정확히 같은 product_id 조합 확인
            query = db.query(ProductReview).filter(
                ProductReview.platform == platform,
                ProductReview.external_id == external_id
            )
            if product_id is not None:
                query = query.filter(ProductReview.product_id == product_id)
            else:
                query = query.filter(ProductReview.product_id.is_(None))

            existing = query.first()

            # 2차: product_id 매칭됐는데 정확한 조합이 없으면, NULL 버전을 승격
            if not existing and product_id is not None and not first_assigned:
                existing = db.query(ProductReview).filter(
                    ProductReview.platform == platform,
                    ProductReview.external_id == external_id,
                    ProductReview.product_id.is_(None)
                ).first()

            if existing:
                existing.product_id = product_id if product_id is not None else existing.product_id
                existing.rating = review_data.get("rating", 5.0)
                existing.content = review_data.get("content", "")
                existing.review_source_id = platform_product_code
                existing.platform_product_code = platform_product_code
                existing.product_name = product_name
                existing.updated_at = datetime.now()
                updated += 1
            else:
                review = ProductReview(
                    product_id=product_id,
                    review_source_id=platform_product_code,
                    platform=platform,
                    platform_product_code=platform_product_code,
                    product_name=product_name,
                    external_id=external_id,
                    rating=review_data.get("rating", 5.0),
                    content=review_data.get("content", ""),
                    author=review_data.get("author"),
                    purchased_option=review_data.get("purchased_option"),
                    reviewed_at=review_data.get("reviewed_at"),
                    is_visible=True,
                    created_at=datetime.now(),
                    updated_at=datetime.now()
                )
                db.add(review)
                added += 1
            first_assigned = True

    db.commit()

    unmatched = sum(1 for r in reviews if r.get("product_code") and not source_cache.get(r.get("product_code")) and not product_cache.get(r.get("product_code")))
    if unmatched > 0:
        logger.info(f"상품코드 미매칭 {unmatched}개 (관리자에서 매칭 필요)")

    return added, updated


def keep_alive_cookies():
    """Playwright로 모든 쿠키 기반 플랫폼 세션 유지

    하나의 브라우저 인스턴스에서 플랫폼별로 컨텍스트를 생성하여
    쿠키 로드 → 여러 페이지 순회 접속 → 갱신된 쿠키 저장.
    무신사는 SSO API 로그인이라 keep-alive 불필요.
    """
    from playwright.sync_api import sync_playwright

    logger.info("=== 쿠키 keep-alive 시작 (Playwright) ===")

    try:
        with sync_playwright() as p:
            browser = p.chromium.launch(headless=True)

            for platform, urls in settings.KEEP_ALIVE_URLS.items():
                try:
                    _keep_alive_platform(browser, platform, urls)
                except Exception as e:
                    logger.error(f"[{platform}] keep-alive 오류: {e}")

            browser.close()
    except Exception as e:
        logger.error(f"Playwright 브라우저 시작 실패: {e}")

    logger.info("=== 쿠키 keep-alive 완료 ===")


def _keep_alive_platform(browser, platform: str, urls: list):
    """Playwright 컨텍스트로 플랫폼별 쿠키 갱신 (여러 URL 순회)"""
    cookie_path = settings.COOKIE_PATHS.get(platform)
    if not cookie_path or not os.path.exists(cookie_path):
        logger.debug(f"[{platform}] 쿠키 파일 없음, 스킵")
        return

    # Selenium 포맷 쿠키 로드
    with open(cookie_path, 'r') as f:
        selenium_cookies = json.load(f)
    logger.info(f"[{platform}] 쿠키 로드: {len(selenium_cookies)}개")

    # Selenium → Playwright 쿠키 변환
    pw_cookies = []
    for c in selenium_cookies:
        pc = {
            'name': c['name'],
            'value': c['value'],
            'domain': c.get('domain', ''),
            'path': c.get('path', '/'),
            'secure': c.get('secure', False),
            'httpOnly': c.get('httpOnly', False),
        }
        if c.get('expiry'):
            pc['expires'] = float(c['expiry'])
        same_site = c.get('sameSite', 'Lax')
        if same_site in ('Strict', 'Lax', 'None'):
            pc['sameSite'] = same_site
        pw_cookies.append(pc)

    # 브라우저 컨텍스트 생성 → 쿠키 주입 → 여러 페이지 순회
    context = browser.new_context(
        user_agent='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
                   '(KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36',
        locale='ko-KR',
    )
    context.add_cookies(pw_cookies)

    page = context.new_page()
    login_patterns = settings.KEEP_ALIVE_LOGIN_PATTERNS.get(platform, [])

    for i, url in enumerate(urls):
        try:
            page.goto(url, wait_until='domcontentloaded', timeout=30000)
            page.wait_for_timeout(3000)

            # 첫 번째 URL에서 로그인 리다이렉트 감지 → 세션 만료
            is_expired = any(pat.lower() in page.url.lower() for pat in login_patterns)
            if is_expired:
                logger.warning(f"[{platform}] 세션 만료됨 (URL: {page.url[:100]})")
                context.close()
                return

            logger.info(f"[{platform}] URL {i+1}/{len(urls)} 접속 OK: {url[:80]}")
        except Exception as e:
            logger.warning(f"[{platform}] URL {i+1}/{len(urls)} 접속 실패: {url[:80]} - {e}")

    logger.info(f"[{platform}] 세션 유효 ({len(urls)}개 URL 순회 완료)")

    # Playwright → Selenium 포맷으로 갱신된 쿠키 저장
    new_cookies = context.cookies()
    sel_cookies = []
    for c in new_cookies:
        sc = {
            'name': c['name'],
            'value': c['value'],
            'domain': c.get('domain', ''),
            'path': c.get('path', '/'),
            'secure': c.get('secure', False),
            'httpOnly': c.get('httpOnly', False),
        }
        if c.get('expires', -1) > 0:
            sc['expiry'] = int(c['expires'])
        if c.get('sameSite'):
            sc['sameSite'] = c['sameSite']
        sel_cookies.append(sc)

    with open(cookie_path, 'w') as f:
        json.dump(sel_cookies, f, indent=2)
    logger.info(f"[{platform}] 쿠키 갱신 저장: {len(sel_cookies)}개")

    context.close()


def start_scheduler():
    """스케줄러 시작"""
    global scheduler

    if not settings.SYNC_ENABLED:
        logger.info("스케줄러 비활성화됨")
        return

    scheduler = BackgroundScheduler()

    # 매일 지정 시간에 동기화 (쉼표 구분으로 여러 시각 지원)
    hours = settings.SYNC_HOURS
    scheduler.add_job(
        sync_all_platforms,
        CronTrigger(hour=hours, minute=settings.SYNC_MINUTE),
        id="daily_review_sync",
        replace_existing=True
    )

    # 쿠키 keep-alive (N시간마다)
    interval = settings.KEEP_ALIVE_INTERVAL_HOURS
    scheduler.add_job(
        keep_alive_cookies,
        IntervalTrigger(hours=interval),
        id="cookie_keep_alive",
        replace_existing=True
    )

    scheduler.start()
    logger.info(f"스케줄러 시작: 동기화 매일 {hours}시 {settings.SYNC_MINUTE:02d}분, keep-alive {interval}시간 간격")


def stop_scheduler():
    """스케줄러 종료"""
    global scheduler
    if scheduler:
        scheduler.shutdown()
        logger.info("스케줄러 종료")
