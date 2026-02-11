from sqlalchemy import Column, Integer, String, Text, Float, Boolean, DateTime, JSON, ForeignKey
from sqlalchemy.sql import func
from app.database import Base
from pydantic import BaseModel
from typing import Optional, List
from datetime import datetime


# SQLAlchemy Models (Laravel 테이블과 매핑)
# ForeignKey 제거 - Laravel에서 관리하는 테이블이므로 단순 Integer로 처리
class ProductReviewSource(Base):
    __tablename__ = "product_review_sources"

    id = Column(Integer, primary_key=True, index=True)
    product_id = Column(Integer)  # Laravel products.id 참조
    platform = Column(String(50))
    platform_name = Column(String(100))
    external_url = Column(String(500))
    external_id = Column(String(100))
    review_count = Column(Integer, default=0)
    average_rating = Column(Float, default=0)
    recent_reviews = Column(JSON)
    api_config = Column(Text)  # encrypted in Laravel
    is_active = Column(Boolean, default=True)
    synced_at = Column(DateTime)
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, server_default=func.now(), onupdate=func.now())


class ProductReview(Base):
    __tablename__ = "product_reviews"

    id = Column(Integer, primary_key=True, index=True)
    product_id = Column(Integer, nullable=True)  # Laravel products.id 참조 (매칭 후 설정)
    review_source_id = Column(Integer, nullable=True)  # product_review_sources.id 참조
    platform = Column(String(50))
    platform_product_code = Column(String(100), nullable=True)  # 플랫폼별 상품코드
    product_name = Column(String(500), nullable=True)  # 플랫폼에서 가져온 상품명
    external_id = Column(String(100))
    rating = Column(Float, default=5.0)
    title = Column(String(255))
    content = Column(Text)
    author = Column(String(100))
    purchased_option = Column(String(255))
    images = Column(JSON)
    is_featured = Column(Boolean, default=False)
    is_visible = Column(Boolean, default=True)
    reviewed_at = Column(DateTime)
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, server_default=func.now(), onupdate=func.now())


# Pydantic Schemas
class ReviewBase(BaseModel):
    rating: float = 5.0
    title: Optional[str] = None
    content: str
    author: Optional[str] = None
    purchased_option: Optional[str] = None
    images: Optional[List[str]] = None
    reviewed_at: Optional[datetime] = None


class ReviewCreate(ReviewBase):
    product_id: int
    platform: str
    external_id: Optional[str] = None


class ReviewResponse(ReviewBase):
    id: int
    product_id: Optional[int] = None
    platform: str
    is_featured: bool
    is_visible: bool
    created_at: datetime

    class Config:
        from_attributes = True


class SyncResult(BaseModel):
    success: bool
    platform: str
    reviews_added: int
    reviews_updated: int
    total_reviews: int
    average_rating: float
    message: str
    synced_at: datetime


class SyncRequest(BaseModel):
    source_id: Optional[int] = None
    product_id: Optional[int] = None
    platform: Optional[str] = None


# ============== Sync Log ==============

class SyncLog(Base):
    __tablename__ = 'sync_logs'

    id = Column(Integer, primary_key=True, autoincrement=True)
    review_source_id = Column(Integer, nullable=True)
    platform = Column(String(50))
    trigger_type = Column(String(20))   # 'scheduled' | 'manual'
    status = Column(String(20))         # 'running' | 'success' | 'failed'
    started_at = Column(DateTime)
    completed_at = Column(DateTime, nullable=True)
    duration_seconds = Column(Integer, nullable=True)
    reviews_added = Column(Integer, default=0)
    reviews_updated = Column(Integer, default=0)
    total_reviews = Column(Integer, default=0)
    error_message = Column(Text, nullable=True)
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, server_default=func.now(), onupdate=func.now())


class SyncLogResponse(BaseModel):
    id: int
    review_source_id: Optional[int] = None
    platform: str
    trigger_type: str
    status: str
    started_at: datetime
    completed_at: Optional[datetime] = None
    duration_seconds: Optional[int] = None
    reviews_added: int = 0
    reviews_updated: int = 0
    total_reviews: int = 0
    error_message: Optional[str] = None
    created_at: Optional[datetime] = None

    class Config:
        from_attributes = True


class CookieStatus(BaseModel):
    platform: str
    platform_label: str
    exists: bool
    file_size: Optional[int] = None
    modified_at: Optional[str] = None
    file_path: str
