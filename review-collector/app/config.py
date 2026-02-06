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

    # 데이터베이스 설정 (Laravel과 동일한 DB)
    DB_HOST: str = "localhost"
    DB_PORT: int = 3306
    DB_NAME: str = "qrcode"
    DB_USER: str = "root"
    DB_PASSWORD: str = ""

    # Chrome/Selenium 설정
    CHROME_HEADLESS: bool = True
    CHROME_VERSION: int = 144
    DOWNLOAD_PATH: str = "/tmp/review_downloads"
    COOKIE_PATH: str = "/app/data/qsm_cookies.json"

    # 스케줄러 설정
    SYNC_ENABLED: bool = True
    SYNC_HOUR: int = 3
    SYNC_MINUTE: int = 0

    # 로깅
    LOG_LEVEL: str = "INFO"

    @property
    def DATABASE_URL(self) -> str:
        return f"mysql+pymysql://{self.DB_USER}:{self.DB_PASSWORD}@{self.DB_HOST}:{self.DB_PORT}/{self.DB_NAME}"

    class Config:
        env_file = ".env"
        env_file_encoding = "utf-8"
        extra = "ignore"


settings = Settings()
