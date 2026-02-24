"""설정 관리"""

import os
from pydantic_settings import BaseSettings
from typing import Optional


class Settings(BaseSettings):
    """애플리케이션 설정"""

    # 앱 설정
    APP_NAME: str = "Review Collector"
    APP_VERSION: str = "1.0.0"
    DEBUG: bool = False

    # QSM 설정 (Qoo10)
    QSM_ID: str = ""
    QSM_PASSWORD: str = ""

    # 네이버 스마트스토어 설정
    NAVER_ID: str = ""
    NAVER_PASSWORD: str = ""
    NAVER_COOKIE_PATH: str = "/app/data/naver_cookies.json"

    # 데이터베이스 설정 (Laravel과 동일한 DB)
    DB_HOST: str = "localhost"
    DB_PORT: int = 3306
    DB_DATABASE: str = "qrcode"
    DB_USERNAME: str = "root"
    DB_PASSWORD: str = ""

    # Chrome/Selenium 설정
    CHROME_HEADLESS: bool = True
    CHROME_VERSION: int = 144
    DOWNLOAD_PATH: str = "/tmp/review_downloads"
    COOKIE_PATH: str = "/app/data/qsm_cookies.json"

    # 스케줄러 설정
    SYNC_ENABLED: bool = True
    SYNC_HOURS: str = "4,16"  # 실행 시각 (쉼표 구분, 예: "4,16" → 오전4시, 오후4시)
    SYNC_MINUTE: int = 0
    SYNC_DAYS: int = 30  # 리뷰 수집 기간 제한 (일)
    KEEP_ALIVE_INTERVAL_HOURS: int = 4  # 쿠키 keep-alive 간격 (시간)

    # 로깅
    LOG_LEVEL: str = "INFO"

    # 무신사 파트너 설정
    MUSINSA_ID: str = ""
    MUSINSA_PASSWORD: str = ""
    MUSINSA_OTP_SECRET: str = ""
    MUSINSA_COOKIE_PATH: str = "/app/data/musinsa_cookies.json"

    # Shopee 셀러센터 설정
    SHOPEE_COOKIE_PATH: str = "/app/data/shopee_cookies.json"

    # 쿠키 경로 매핑
    COOKIE_PATHS: dict = {
        'qoo10': '/app/data/qsm_cookies.json',
        'naver': '/app/data/naver_cookies.json',
        'musinsa': '/app/data/musinsa_cookies.json',
        'shopee': '/app/data/shopee_cookies.json',
    }

    # 쿠키 플랫폼 라벨
    COOKIE_LABELS: dict = {
        'qoo10': 'Qoo10 (QSM)',
        'naver': '네이버 스마트스토어',
        'musinsa': '무신사 파트너',
        'shopee': 'Shopee 셀러센터',
    }

    # 쿠키 keep-alive URL (requests로 접속하여 세션 유지)
    # 무신사는 SSO API 로그인이라 keep-alive 불필요
    KEEP_ALIVE_URLS: dict = {
        'qoo10': 'https://qsm.qoo10.jp/GMKT.INC.Gsm.Web/Default.aspx',
        'naver': 'https://sell.smartstore.naver.com/',
        'shopee': 'https://seller.shopee.kr/',
    }

    # keep-alive 시 로그인 페이지 감지 패턴
    KEEP_ALIVE_LOGIN_PATTERNS: dict = {
        'qoo10': ['Login.aspx', 'login.aspx', 'txtLoginID'],
        'naver': ['/login', 'accounts.commerce.naver.com/login'],
        'shopee': ['/account/signin', 'account/login'],
    }

    @property
    def DATABASE_URL(self) -> str:
        return f"mysql+pymysql://{self.DB_USERNAME}:{self.DB_PASSWORD}@{self.DB_HOST}:{self.DB_PORT}/{self.DB_DATABASE}"

    class Config:
        env_file = ".env"
        env_file_encoding = "utf-8"
        extra = "ignore"


settings = Settings()
