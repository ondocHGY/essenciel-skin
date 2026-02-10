"""스케줄러 관리"""

import logging
from datetime import datetime
from apscheduler.schedulers.background import BackgroundScheduler
from apscheduler.triggers.cron import CronTrigger

from app.config import settings
from app.database import SessionLocal
from app.models import ProductReviewSource, ProductReview, SyncLog
from app.scrapers import get_scraper, get_supported_platforms

logger = logging.getLogger(__name__)

scheduler: BackgroundScheduler = None


def sync_all_platforms():
    """모든 지원 플랫폼 동기화"""
    logger.info("=== 스케줄된 동기화 시작 ===")

    platforms = get_supported_platforms()
    logger.info(f"지원 플랫폼: {platforms}")

    db = SessionLocal()
    try:
        for platform in platforms:
            try:
                sync_platform(platform, db, trigger_type='scheduled')
            except Exception as e:
                logger.error(f"[{platform}] 동기화 실패: {e}")

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

    # 리뷰 소스 캐시: platform + external_id -> product_id
    source_cache = {}
    sources = db.query(ProductReviewSource).filter(
        ProductReviewSource.platform == platform,
        ProductReviewSource.is_active == True
    ).all()
    for src in sources:
        if src.external_id and src.product_id:
            source_cache[src.external_id] = src.product_id

    for review_data in reviews:
        # external_id 생성
        external_id = review_data.get("external_id")
        if not external_id:
            content_hash = hashlib.md5(review_data.get("content", "").encode()).hexdigest()[:16]
            external_id = f"{platform}_{content_hash}"

        # 플랫폼별 상품코드 및 상품명
        platform_product_code = review_data.get("product_code")
        product_name = review_data.get("product_name")

        # product_id 결정: 2단계 매칭
        product_id = None

        if platform_product_code:
            # 1단계: product_review_sources에서 external_id 매칭
            if platform_product_code in source_cache:
                product_id = source_cache[platform_product_code]

            # 2단계: products 테이블에서 code 매칭
            if not product_id:
                if platform_product_code not in product_cache:
                    result = db.execute(
                        text("SELECT id FROM products WHERE code = :code LIMIT 1"),
                        {"code": platform_product_code}
                    ).fetchone()
                    product_cache[platform_product_code] = result[0] if result else None

                if product_cache[platform_product_code]:
                    product_id = product_cache[platform_product_code]

            if not product_id:
                logger.debug(f"상품코드 '{platform_product_code}' 매칭 대기")

        # 기존 리뷰 확인
        existing = db.query(ProductReview).filter(
            ProductReview.platform == platform,
            ProductReview.external_id == external_id
        ).first()

        if existing:
            existing.rating = review_data.get("rating", 5.0)
            existing.content = review_data.get("content", "")
            existing.platform_product_code = platform_product_code
            existing.product_name = product_name
            if product_id:  # 매칭된 경우에만 업데이트
                existing.product_id = product_id
            existing.updated_at = datetime.now()
            updated += 1
        else:
            review = ProductReview(
                product_id=product_id,
                review_source_id=None,
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

    db.commit()

    unmatched = sum(1 for r in reviews if r.get("product_code") and not product_cache.get(r.get("product_code")))
    if unmatched > 0:
        logger.info(f"상품코드 미매칭 {unmatched}개 (관리자에서 매칭 필요)")

    return added, updated


def start_scheduler():
    """스케줄러 시작"""
    global scheduler

    if not settings.SYNC_ENABLED:
        logger.info("스케줄러 비활성화됨")
        return

    scheduler = BackgroundScheduler()

    # 매일 지정 시간에 동기화
    scheduler.add_job(
        sync_all_platforms,
        CronTrigger(hour=settings.SYNC_HOUR, minute=settings.SYNC_MINUTE),
        id="daily_review_sync",
        replace_existing=True
    )

    scheduler.start()
    logger.info(f"스케줄러 시작: 매일 {settings.SYNC_HOUR:02d}:{settings.SYNC_MINUTE:02d} 동기화")


def stop_scheduler():
    """스케줄러 종료"""
    global scheduler
    if scheduler:
        scheduler.shutdown()
        logger.info("스케줄러 종료")
