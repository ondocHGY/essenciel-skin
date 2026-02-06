"""베이스 스크래퍼 추상 클래스"""

from abc import ABC, abstractmethod
from typing import List, Dict, Any, Optional
from datetime import datetime
import logging

logger = logging.getLogger(__name__)


class BaseScraper(ABC):
    """모든 플랫폼 스크래퍼의 베이스 클래스"""

    platform: str = ""  # 플랫폼 이름 (qoo10, coupang, etc.)

    @abstractmethod
    def fetch_reviews(self, **kwargs) -> Dict[str, Any]:
        """
        리뷰 수집 실행

        Returns:
            {
                "success": bool,
                "reviews": List[Dict],
                "total_count": int,
                "average_rating": float,
                "message": str,
                "file_path": Optional[str]  # 다운로드된 파일 경로
            }
        """
        pass

    @abstractmethod
    def login(self) -> bool:
        """로그인 (필요한 경우)"""
        pass

    def start(self):
        """스크래퍼 시작 (브라우저 등)"""
        pass

    def stop(self):
        """스크래퍼 종료"""
        pass

    def parse_date(self, date_str: Optional[str]) -> Optional[datetime]:
        """날짜 문자열 파싱"""
        if not date_str:
            return None

        date_str = str(date_str).strip()
        formats = [
            "%Y-%m-%d",
            "%Y/%m/%d",
            "%Y.%m.%d",
            "%Y-%m-%d %H:%M:%S",
            "%Y/%m/%d %H:%M:%S",
        ]

        for fmt in formats:
            try:
                return datetime.strptime(date_str, fmt)
            except ValueError:
                continue

        return None

    def normalize_review(self, review_data: Dict[str, Any]) -> Dict[str, Any]:
        """리뷰 데이터 정규화"""
        return {
            "external_id": review_data.get("external_id"),
            "rating": float(review_data.get("rating", 5.0)),
            "title": review_data.get("title"),
            "content": review_data.get("content", ""),
            "author": review_data.get("author"),
            "purchased_option": review_data.get("purchased_option"),
            "product_code": review_data.get("product_code"),
            "product_name": review_data.get("product_name"),
            "images": review_data.get("images", []),
            "reviewed_at": self.parse_date(review_data.get("reviewed_at")),
            "platform": self.platform,
        }
