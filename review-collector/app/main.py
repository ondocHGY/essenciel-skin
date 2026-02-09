"""Review Collector FastAPI 애플리케이션"""

import logging
import os
import json
from datetime import datetime
from typing import List, Optional
from contextlib import asynccontextmanager

from fastapi import FastAPI, Depends, HTTPException, BackgroundTasks, UploadFile, File
from sqlalchemy.orm import Session
from sqlalchemy import func, desc

from app.config import settings
from app.database import get_db, engine, Base
from app.models import (
    ProductReviewSource, ProductReview, SyncLog,
    ReviewResponse, SyncResult, SyncRequest, SyncLogResponse, CookieStatus
)
from app.scrapers import get_scraper, get_supported_platforms
from app.scheduler import start_scheduler, stop_scheduler, sync_source, save_reviews

# 로깅 설정
logging.basicConfig(
    level=getattr(logging, settings.LOG_LEVEL),
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)


@asynccontextmanager
async def lifespan(app: FastAPI):
    """앱 시작/종료 시 실행"""
    logger.info(f"{settings.APP_NAME} v{settings.APP_VERSION} 시작")
    # sync_logs 테이블 자동 생성
    try:
        Base.metadata.create_all(bind=engine, tables=[SyncLog.__table__], checkfirst=True)
        logger.info("sync_logs 테이블 확인 완료")
    except Exception as e:
        logger.warning(f"sync_logs 테이블 생성 실패 (DB 연결 문제일 수 있음): {e}")
    start_scheduler()
    yield
    stop_scheduler()
    logger.info(f"{settings.APP_NAME} 종료")


app = FastAPI(
    title=settings.APP_NAME,
    description="다중 플랫폼 리뷰 수집 서비스",
    version=settings.APP_VERSION,
    lifespan=lifespan
)


# ============== 헬스체크 ==============

@app.get("/")
def root():
    """루트 엔드포인트"""
    return {
        "service": settings.APP_NAME,
        "version": settings.APP_VERSION,
        "status": "running",
        "supported_platforms": get_supported_platforms()
    }


@app.get("/health")
def health_check():
    """헬스체크"""
    return {"status": "healthy"}


# ============== 리뷰 조회 ==============

@app.get("/api/reviews", response_model=List[ReviewResponse])
def get_reviews(
    product_id: Optional[int] = None,
    platform: Optional[str] = None,
    limit: int = 100,
    offset: int = 0,
    db: Session = Depends(get_db)
):
    """저장된 리뷰 조회"""
    query = db.query(ProductReview).filter(ProductReview.is_visible == True)

    if product_id:
        query = query.filter(ProductReview.product_id == product_id)
    if platform:
        query = query.filter(ProductReview.platform == platform)

    reviews = query.order_by(ProductReview.reviewed_at.desc()) \
        .offset(offset).limit(limit).all()

    return reviews


@app.get("/api/reviews/stats")
def get_review_stats(
    product_id: Optional[int] = None,
    platform: Optional[str] = None,
    db: Session = Depends(get_db)
):
    """리뷰 통계 조회"""
    query = db.query(ProductReview).filter(ProductReview.is_visible == True)

    if product_id:
        query = query.filter(ProductReview.product_id == product_id)
    if platform:
        query = query.filter(ProductReview.platform == platform)

    total = query.count()
    avg_rating = query.with_entities(func.avg(ProductReview.rating)).scalar() or 0

    # 플랫폼별 통계
    platform_stats = {}
    for p in get_supported_platforms():
        p_query = query.filter(ProductReview.platform == p)
        p_count = p_query.count()
        p_avg = p_query.with_entities(func.avg(ProductReview.rating)).scalar() or 0
        platform_stats[p] = {
            "count": p_count,
            "average_rating": round(float(p_avg), 2)
        }

    return {
        "total_reviews": total,
        "average_rating": round(float(avg_rating), 2),
        "platforms": platform_stats
    }


# ============== 리뷰 동기화 ==============

@app.post("/api/reviews/sync", response_model=SyncResult)
def sync_reviews(
    request: SyncRequest,
    background_tasks: BackgroundTasks,
    db: Session = Depends(get_db)
):
    """리뷰 동기화 실행"""
    query = db.query(ProductReviewSource).filter(ProductReviewSource.is_active == True)

    if request.source_id:
        query = query.filter(ProductReviewSource.id == request.source_id)
    elif request.product_id:
        query = query.filter(ProductReviewSource.product_id == request.product_id)
    elif request.platform:
        query = query.filter(ProductReviewSource.platform == request.platform)

    sources = query.all()

    if not sources:
        raise HTTPException(status_code=404, detail="활성화된 리뷰 소스를 찾을 수 없습니다")

    # 첫 번째 소스 동기화 (나머지는 백그라운드)
    source = sources[0]
    result = _sync_single_source(source, db, trigger_type='manual')

    for s in sources[1:]:
        background_tasks.add_task(_sync_single_source, s, db, trigger_type='manual')

    return result


@app.post("/api/reviews/sync/{platform}")
def sync_platform_reviews(
    platform: str,
    product_id: int,
    db: Session = Depends(get_db)
):
    """특정 플랫폼 리뷰 수동 동기화"""
    if platform not in get_supported_platforms():
        raise HTTPException(status_code=400, detail=f"지원하지 않는 플랫폼: {platform}")

    try:
        scraper = get_scraper(platform)
        result = scraper.fetch_reviews()

        if not result["success"]:
            raise HTTPException(status_code=500, detail=result["message"])

        reviews = result.get("reviews", [])

        # DB 저장
        added = 0
        updated = 0
        import hashlib

        for review_data in reviews:
            external_id = review_data.get("external_id")
            if not external_id:
                content_hash = hashlib.md5(review_data.get("content", "").encode()).hexdigest()[:16]
                external_id = f"{platform}_{content_hash}"

            existing = db.query(ProductReview).filter(
                ProductReview.platform == platform,
                ProductReview.external_id == external_id
            ).first()

            if existing:
                existing.rating = review_data.get("rating", 5.0)
                existing.content = review_data.get("content", "")
                existing.updated_at = datetime.now()
                updated += 1
            else:
                review = ProductReview(
                    product_id=product_id,
                    platform=platform,
                    external_id=external_id,
                    rating=review_data.get("rating", 5.0),
                    content=review_data.get("content", ""),
                    author=review_data.get("author"),
                    reviewed_at=review_data.get("reviewed_at"),
                    is_visible=True
                )
                db.add(review)
                added += 1

        db.commit()

        # 통계 계산
        total = db.query(ProductReview).filter(
            ProductReview.product_id == product_id,
            ProductReview.platform == platform
        ).count()

        avg_rating = db.query(func.avg(ProductReview.rating)).filter(
            ProductReview.product_id == product_id,
            ProductReview.platform == platform
        ).scalar() or 0

        return SyncResult(
            success=True,
            platform=platform,
            reviews_added=added,
            reviews_updated=updated,
            total_reviews=total,
            average_rating=round(float(avg_rating), 2),
            message=f"동기화 완료: {added}개 추가, {updated}개 업데이트",
            synced_at=datetime.now()
        )

    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"동기화 오류: {e}")
        raise HTTPException(status_code=500, detail=str(e))


# ============== 플랫폼 정보 ==============

@app.get("/api/platforms")
def get_platforms():
    """지원 플랫폼 목록"""
    return {
        "platforms": get_supported_platforms()
    }


# ============== Sync Logs ==============

@app.get("/api/sync-logs", response_model=List[SyncLogResponse])
def get_sync_logs(
    platform: Optional[str] = None,
    status: Optional[str] = None,
    limit: int = 50,
    offset: int = 0,
    db: Session = Depends(get_db)
):
    """실행 기록 목록"""
    query = db.query(SyncLog)

    if platform:
        query = query.filter(SyncLog.platform == platform)
    if status:
        query = query.filter(SyncLog.status == status)

    logs = query.order_by(desc(SyncLog.started_at)) \
        .offset(offset).limit(limit).all()

    return logs


@app.get("/api/sync-logs/stats")
def get_sync_log_stats(db: Session = Depends(get_db)):
    """실행 기록 요약 통계"""
    total = db.query(SyncLog).count()
    success = db.query(SyncLog).filter(SyncLog.status == 'success').count()
    failed = db.query(SyncLog).filter(SyncLog.status == 'failed').count()
    running = db.query(SyncLog).filter(SyncLog.status == 'running').count()

    last_sync = db.query(SyncLog).filter(
        SyncLog.status == 'success'
    ).order_by(desc(SyncLog.completed_at)).first()

    return {
        "total": total,
        "success": success,
        "failed": failed,
        "running": running,
        "last_success_at": last_sync.completed_at.isoformat() if last_sync and last_sync.completed_at else None,
    }


# ============== Cookie Management ==============

@app.get("/api/cookies", response_model=List[CookieStatus])
def get_cookie_status():
    """플랫폼별 쿠키 상태 조회"""
    result = []
    for platform, path in settings.COOKIE_PATHS.items():
        label = settings.COOKIE_LABELS.get(platform, platform)
        exists = os.path.isfile(path)

        file_size = None
        modified_at = None
        if exists:
            stat = os.stat(path)
            file_size = stat.st_size
            modified_at = datetime.fromtimestamp(stat.st_mtime).isoformat()

        result.append(CookieStatus(
            platform=platform,
            platform_label=label,
            exists=exists,
            file_size=file_size,
            modified_at=modified_at,
            file_path=path,
        ))

    return result


@app.post("/api/cookies/{platform}")
async def upload_cookie(platform: str, file: UploadFile = File(...)):
    """쿠키 JSON 파일 업로드"""
    if platform not in settings.COOKIE_PATHS:
        raise HTTPException(status_code=400, detail=f"지원하지 않는 플랫폼: {platform}")

    path = settings.COOKIE_PATHS[platform]

    try:
        content = await file.read()

        # JSON 유효성 검증
        json.loads(content)

        # 디렉토리 생성
        os.makedirs(os.path.dirname(path), exist_ok=True)

        with open(path, 'wb') as f:
            f.write(content)

        stat = os.stat(path)
        return {
            "success": True,
            "message": f"{platform} 쿠키 업로드 완료",
            "file_size": stat.st_size,
            "modified_at": datetime.fromtimestamp(stat.st_mtime).isoformat(),
        }

    except json.JSONDecodeError:
        raise HTTPException(status_code=400, detail="유효한 JSON 파일이 아닙니다")
    except Exception as e:
        logger.error(f"쿠키 업로드 오류: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@app.delete("/api/cookies/{platform}")
def delete_cookie(platform: str):
    """쿠키 파일 삭제"""
    if platform not in settings.COOKIE_PATHS:
        raise HTTPException(status_code=400, detail=f"지원하지 않는 플랫폼: {platform}")

    path = settings.COOKIE_PATHS[platform]

    if not os.path.isfile(path):
        raise HTTPException(status_code=404, detail="쿠키 파일이 존재하지 않습니다")

    try:
        os.remove(path)
        return {"success": True, "message": f"{platform} 쿠키 삭제 완료"}
    except Exception as e:
        logger.error(f"쿠키 삭제 오류: {e}")
        raise HTTPException(status_code=500, detail=str(e))


# ============== 내부 함수 ==============

def _sync_single_source(source: ProductReviewSource, db: Session, trigger_type='manual') -> SyncResult:
    """단일 소스 동기화"""
    platform = source.platform

    if platform not in get_supported_platforms():
        return SyncResult(
            success=False,
            platform=platform,
            reviews_added=0,
            reviews_updated=0,
            total_reviews=0,
            average_rating=0,
            message=f"지원하지 않는 플랫폼: {platform}",
            synced_at=datetime.now()
        )

    # SyncLog 생성
    sync_log = SyncLog(
        review_source_id=source.id,
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
            _complete_sync_log(db, sync_log, 'failed', error_message=result["message"])
            return SyncResult(
                success=False,
                platform=platform,
                reviews_added=0,
                reviews_updated=0,
                total_reviews=0,
                average_rating=0,
                message=result["message"],
                synced_at=datetime.now()
            )

        reviews = result.get("reviews", [])
        added, updated = save_reviews(source, reviews, db)

        source.review_count = len(reviews)
        if reviews:
            source.average_rating = sum(r.get("rating", 5) for r in reviews) / len(reviews)
        source.synced_at = datetime.now()
        db.commit()

        _complete_sync_log(db, sync_log, 'success',
                           reviews_added=added, reviews_updated=updated,
                           total_reviews=source.review_count)

        return SyncResult(
            success=True,
            platform=platform,
            reviews_added=added,
            reviews_updated=updated,
            total_reviews=source.review_count,
            average_rating=round(source.average_rating, 2),
            message="동기화 완료",
            synced_at=datetime.now()
        )

    except Exception as e:
        logger.error(f"[{platform}] 동기화 오류: {e}")
        try:
            _complete_sync_log(db, sync_log, 'failed', error_message=str(e))
        except Exception:
            pass
        return SyncResult(
            success=False,
            platform=platform,
            reviews_added=0,
            reviews_updated=0,
            total_reviews=0,
            average_rating=0,
            message=str(e),
            synced_at=datetime.now()
        )


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
