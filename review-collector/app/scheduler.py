"""스케줄러 관리"""

import logging
from datetime import datetime
from apscheduler.schedulers.background import BackgroundScheduler
from apscheduler.triggers.cron import CronTrigger

from app.config import settings
from app.database import SessionLocal
from app.models import ProductReviewSource, ProductReview
from app.scrapers import get_scraper, get_supported_platforms

logger = logging.getLogger(__name__)

scheduler: BackgroundScheduler = None


def sync_all_sources():
    """모든 활성 소스 동기화"""
    logger.info("=== 스케줄된 동기화 시작 ===")

    db = SessionLocal()
    try:
        sources = db.query(ProductReviewSource).filter(
            ProductReviewSource.is_active == True
        ).all()

        logger.info(f"활성 소스 수: {len(sources)}")

        for source in sources:
            try:
                sync_source(source, db)
            except Exception as e:
                logger.error(f"[{source.platform}] 동기화 실패: {e}")

    finally:
        db.close()

    logger.info("=== 스케줄된 동기화 완료 ===")


def sync_source(source: ProductReviewSource, db):
    """단일 소스 동기화"""
    platform = source.platform
    logger.info(f"[{platform}] 동기화 시작 (source_id={source.id})")

    if platform not in get_supported_platforms():
        logger.warning(f"[{platform}] 지원하지 않는 플랫폼")
        return

    try:
        scraper = get_scraper(platform)
        result = scraper.fetch_reviews()

        if not result["success"]:
            logger.error(f"[{platform}] 수집 실패: {result['message']}")
            return

        reviews = result.get("reviews", [])
        added, updated = save_reviews(source, reviews, db)

        # 소스 통계 업데이트
        source.review_count = len(reviews)
        if reviews:
            source.average_rating = sum(r.get("rating", 5) for r in reviews) / len(reviews)
        source.synced_at = datetime.now()
        db.commit()

        logger.info(f"[{platform}] 동기화 완료: {added}개 추가, {updated}개 업데이트")

    except Exception as e:
        logger.error(f"[{platform}] 동기화 오류: {e}")
        db.rollback()


def save_reviews(source: ProductReviewSource, reviews: list, db) -> tuple:
    """리뷰 저장"""
    import hashlib

    added = 0
    updated = 0

    for review_data in reviews:
        # external_id 생성
        external_id = review_data.get("external_id")
        if not external_id:
            content_hash = hashlib.md5(review_data.get("content", "").encode()).hexdigest()[:16]
            external_id = f"{source.platform}_{content_hash}"

        # 기존 리뷰 확인
        existing = db.query(ProductReview).filter(
            ProductReview.platform == source.platform,
            ProductReview.external_id == external_id
        ).first()

        if existing:
            existing.rating = review_data.get("rating", 5.0)
            existing.content = review_data.get("content", "")
            existing.updated_at = datetime.now()
            updated += 1
        else:
            review = ProductReview(
                product_id=source.product_id,
                review_source_id=source.id,
                platform=source.platform,
                external_id=external_id,
                rating=review_data.get("rating", 5.0),
                content=review_data.get("content", ""),
                author=review_data.get("author"),
                purchased_option=review_data.get("purchased_option"),
                reviewed_at=review_data.get("reviewed_at"),
                is_visible=True
            )
            db.add(review)
            added += 1

    db.commit()
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
        sync_all_sources,
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
