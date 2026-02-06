"""플랫폼별 스크래퍼 모듈"""

from app.scrapers.base import BaseScraper
from app.scrapers.qoo10 import Qoo10Scraper

# 지원하는 플랫폼 스크래퍼 레지스트리
SCRAPERS = {
    'qoo10': Qoo10Scraper,
    # 'coupang': CoupangScraper,  # 나중에 추가
    # 'musinsa': MusinsaScraper,
    # 'amazon': AmazonScraper,
}


def get_scraper(platform: str) -> BaseScraper:
    """플랫폼에 맞는 스크래퍼 인스턴스 반환"""
    scraper_class = SCRAPERS.get(platform)
    if not scraper_class:
        raise ValueError(f"지원하지 않는 플랫폼: {platform}")
    return scraper_class()


def get_supported_platforms() -> list:
    """지원하는 플랫폼 목록 반환"""
    return list(SCRAPERS.keys())
