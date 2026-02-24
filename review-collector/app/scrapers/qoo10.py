"""Qoo10 QSM 스크래퍼"""

import os
import time
import glob
import json
from datetime import datetime
from typing import Optional, List, Dict, Any
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.action_chains import ActionChains
from selenium.common.exceptions import (
    TimeoutException, NoSuchElementException, UnexpectedAlertPresentException
)
import undetected_chromedriver as uc
import pandas as pd
import logging

try:
    from pyvirtualdisplay import Display
    XVFB_AVAILABLE = True
except ImportError:
    XVFB_AVAILABLE = False

from app.scrapers.base import BaseScraper
from app.config import settings

logger = logging.getLogger(__name__)


class Qoo10Scraper(BaseScraper):
    """Qoo10 QSM 리뷰 스크래퍼"""

    platform = "qoo10"

    QSM_LOGIN_URL = "https://qsm.qoo10.jp/GMKT.INC.Gsm.Web/Login.aspx"
    QSM_REVIEW_URL = "https://qsm.qoo10.jp/GMKT.INC.Gsm.Web/Seller/MyReviewMgt.aspx"
    QSM_HOME_URL = "https://qsm.qoo10.jp/GMKT.INC.Gsm.Web/Default.aspx"

    def __init__(self):
        self.qsm_id = settings.QSM_ID
        self.qsm_password = settings.QSM_PASSWORD
        self.download_path = settings.DOWNLOAD_PATH
        self.cookie_file = settings.COOKIE_PATH
        self.driver = None
        self.display = None

        os.makedirs(self.download_path, exist_ok=True)

    def _get_chrome_options(self) -> uc.ChromeOptions:
        """Chrome 옵션 설정"""
        options = uc.ChromeOptions()

        # 필수 Docker 옵션
        options.add_argument("--no-sandbox")
        options.add_argument("--disable-dev-shm-usage")
        options.add_argument("--disable-gpu")
        options.add_argument("--disable-software-rasterizer")

        # 창 설정
        options.add_argument("--window-size=1920,1080")
        options.add_argument("--lang=ja-JP")

        # 추가 안정성 옵션
        options.add_argument("--disable-extensions")
        options.add_argument("--disable-infobars")
        options.add_argument("--disable-browser-side-navigation")
        options.add_argument("--disable-features=VizDisplayCompositor")

        prefs = {
            "download.default_directory": self.download_path,
            "download.prompt_for_download": False,
            "download.directory_upgrade": True,
            "safebrowsing.enabled": True
        }
        options.add_experimental_option("prefs", prefs)
        return options

    def start(self):
        """브라우저 시작 (reCAPTCHA 때문에 headless=False + Xvfb 사용)"""
        if XVFB_AVAILABLE and settings.CHROME_HEADLESS:
            self.display = Display(visible=0, size=(1920, 1080))
            self.display.start()
            logger.info("가상 디스플레이(Xvfb) 시작")

        options = self._get_chrome_options()
        self.driver = uc.Chrome(
            options=options,
            headless=False,  # reCAPTCHA v2는 headless 감지 → Xvfb로 대체
            use_subprocess=True,
            version_main=settings.CHROME_VERSION
        )
        self.driver.implicitly_wait(10)
        logger.info("Chrome 브라우저 시작 (headless=False + Xvfb)")

    def stop(self):
        """브라우저 종료"""
        if self.driver:
            self.driver.quit()
            self.driver = None
            logger.info("Chrome 브라우저 종료")

        if self.display:
            self.display.stop()
            self.display = None

    def _load_cookies(self) -> Optional[List[Dict]]:
        """저장된 쿠키 로드"""
        try:
            if os.path.exists(self.cookie_file):
                with open(self.cookie_file, 'r') as f:
                    cookies = json.load(f)
                logger.info(f"쿠키 로드: {len(cookies)}개")
                return cookies
        except Exception as e:
            logger.error(f"쿠키 로드 실패: {e}")
        return None

    def _save_cookies(self):
        """쿠키 저장"""
        try:
            cookies = self.driver.get_cookies()
            with open(self.cookie_file, 'w') as f:
                json.dump(cookies, f, indent=2)
            logger.info(f"쿠키 저장: {len(cookies)}개")
        except Exception as e:
            logger.error(f"쿠키 저장 실패: {e}")

    def login(self) -> bool:
        """QSM 로그인"""
        try:
            # 쿠키 로그인 시도
            cookies = self._load_cookies()
            if cookies:
                logger.info("쿠키로 로그인 시도...")
                self.driver.get(self.QSM_LOGIN_URL)
                time.sleep(2)

                for cookie in cookies:
                    try:
                        if 'sameSite' in cookie:
                            del cookie['sameSite']
                        self.driver.add_cookie(cookie)
                    except Exception:
                        pass

                self.driver.get(self.QSM_HOME_URL)
                time.sleep(3)

                if "login.aspx" not in self.driver.current_url.lower():
                    self._save_cookies()  # 갱신된 쿠키 재저장 (수명 연장)
                    logger.info("쿠키 로그인 성공 (쿠키 갱신 저장)")
                    return True
                else:
                    logger.warning("쿠키 만료됨")

            # ID/PW 로그인 시도
            logger.info("ID/PW 로그인 시도...")
            self.driver.get(self.QSM_LOGIN_URL)
            time.sleep(3)

            wait = WebDriverWait(self.driver, 20)
            id_input = wait.until(EC.presence_of_element_located((By.ID, "txtLoginID")))
            pw_input = self.driver.find_element(By.ID, "txtLoginPwd")

            # 자연스러운 입력
            id_input.click()
            time.sleep(0.5)
            for char in self.qsm_id:
                id_input.send_keys(char)
                time.sleep(0.05)

            time.sleep(0.3)
            pw_input.click()
            time.sleep(0.5)
            for char in self.qsm_password:
                pw_input.send_keys(char)
                time.sleep(0.05)

            time.sleep(2)

            # 로그인 버튼 클릭
            login_btn = self.driver.find_element(By.CSS_SELECTOR, "button.g-recaptcha")
            actions = ActionChains(self.driver)
            actions.move_to_element(login_btn).pause(0.5).click().perform()

            time.sleep(8)

            # Alert 처리
            try:
                alert = self.driver.switch_to.alert
                logger.warning(f"Alert: {alert.text}")
                alert.accept()
                time.sleep(2)
            except:
                pass

            if "login.aspx" in self.driver.current_url.lower():
                logger.error("로그인 실패 - 쿠키 필요")
                return False

            self._save_cookies()
            logger.info("로그인 성공")
            return True

        except UnexpectedAlertPresentException as e:
            logger.error(f"Alert 발생: {e.alert_text}")
            try:
                self.driver.switch_to.alert.accept()
            except:
                pass
            return False

        except Exception as e:
            logger.error(f"로그인 오류: {e}")
            return False

    def _navigate_to_reviews(self) -> bool:
        """리뷰 페이지 이동"""
        try:
            self.driver.get(self.QSM_REVIEW_URL)
            time.sleep(2)

            if "MyReviewMgt" not in self.driver.current_url:
                return False

            logger.info("리뷰 페이지 이동 완료")
            return True
        except Exception as e:
            logger.error(f"리뷰 페이지 이동 오류: {e}")
            return False

    def _search_reviews(self) -> bool:
        """검색 실행"""
        try:
            selectors = [
                (By.CSS_SELECTOR, "a[onclick*='btn_search']"),
                (By.XPATH, "//a[contains(text(), '검색')]"),
                (By.XPATH, "//a[contains(text(), '検索')]"),
            ]

            clicked = False
            for by, selector in selectors:
                try:
                    btn = self.driver.find_element(by, selector)
                    btn.click()
                    clicked = True
                    break
                except NoSuchElementException:
                    continue

            if not clicked:
                self.driver.execute_script("btn_search_Onclick();")

            time.sleep(5)
            logger.info("검색 완료")
            return True
        except Exception as e:
            logger.error(f"검색 오류: {e}")
            return False

    def _download_file(self) -> Optional[str]:
        """CSV/Excel 다운로드"""
        try:
            # 기존 파일 삭제
            for f in glob.glob(os.path.join(self.download_path, "*.xls*")):
                os.remove(f)
            for f in glob.glob(os.path.join(self.download_path, "*.csv")):
                os.remove(f)

            selectors = [
                (By.CSS_SELECTOR, "a[onclick*='btn_excel']"),
                (By.XPATH, "//a[contains(text(), 'Excel')]"),
            ]

            clicked = False
            for by, selector in selectors:
                try:
                    btn = self.driver.find_element(by, selector)
                    btn.click()
                    clicked = True
                    break
                except NoSuchElementException:
                    continue

            if not clicked:
                self.driver.execute_script("btn_excel_Onclick();")

            # 다운로드 대기
            timeout = 60
            start_time = time.time()

            while time.time() - start_time < timeout:
                files = glob.glob(os.path.join(self.download_path, "*.xls*"))
                files += glob.glob(os.path.join(self.download_path, "*.csv"))
                files = [f for f in files if not f.endswith(('.crdownload', '.tmp'))]

                if files:
                    downloaded_file = max(files, key=os.path.getctime)
                    logger.info(f"다운로드 완료: {downloaded_file}")
                    return downloaded_file

                time.sleep(1)

            logger.error("다운로드 타임아웃")
            return None
        except Exception as e:
            logger.error(f"다운로드 오류: {e}")
            return None

    def _parse_file(self, file_path: str) -> List[Dict[str, Any]]:
        """CSV/Excel 파싱"""
        reviews = []

        try:
            if file_path.endswith('.csv'):
                df = pd.read_csv(file_path, encoding='utf-8')
            elif file_path.endswith('.xls'):
                df = pd.read_excel(file_path, engine='xlrd')
            else:
                df = pd.read_excel(file_path, engine='openpyxl')

            df.columns = df.columns.str.strip()

            # 디버깅: 실제 컬럼명 로깅
            logger.info(f"파일 컬럼: {list(df.columns)}")

            column_mapping = {
                # 한국어
                '댓글': 'content',
                '상품평번호_h': 'external_id',
                '작성자ID': 'author',
                '작성일': 'reviewed_at',
                '만족도': 'rating',
                '상품코드': 'product_code',
                '상품명': 'product_name',
                # 일본어
                'レビュー内容': 'content',
                '評価': 'rating',
                '登録日': 'reviewed_at',
                '購入者': 'author',
                '商品コード': 'product_code',
                '商品番号': 'product_code',
                '商品名': 'product_name',
                'GdNo': 'product_code',
                # 영어
                'Product Code': 'product_code',
                'Item Code': 'product_code',
                'Product Name': 'product_name',
            }

            for _, row in df.iterrows():
                review = {
                    "external_id": None,
                    "content": "",
                    "rating": 5.0,
                    "reviewed_at": None,
                    "author": None,
                    "product_code": None,
                    "product_name": None,
                }

                for file_col, field in column_mapping.items():
                    if file_col in df.columns:
                        value = row[file_col]
                        if pd.notna(value):
                            if field == "rating":
                                try:
                                    review[field] = float(value)
                                except:
                                    pass
                            elif field == "reviewed_at":
                                date_str = str(value).strip().replace('/', '-')
                                review[field] = date_str
                            elif field == "external_id":
                                review[field] = f"qoo10_{value}"
                            else:
                                review[field] = str(value).strip()

                if review["content"] and len(review["content"]) > 5:
                    reviews.append(self.normalize_review(review))

            logger.info(f"파싱 완료: {len(reviews)}개 리뷰")

        except Exception as e:
            logger.error(f"파싱 오류: {e}")

        return reviews

    def fetch_reviews(self, **kwargs) -> Dict[str, Any]:
        """리뷰 수집 실행"""
        result = {
            "success": False,
            "reviews": [],
            "total_count": 0,
            "average_rating": 0.0,
            "message": "",
            "file_path": None
        }

        try:
            self.start()

            if not self.login():
                result["message"] = "로그인 실패"
                return result

            if not self._navigate_to_reviews():
                result["message"] = "리뷰 페이지 이동 실패"
                return result

            if not self._search_reviews():
                result["message"] = "검색 실패"
                return result

            file_path = self._download_file()
            if file_path:
                result["file_path"] = file_path
                reviews = self._parse_file(file_path)
                result["reviews"] = reviews
                result["total_count"] = len(reviews)

                if reviews:
                    total_rating = sum(r.get("rating", 5.0) for r in reviews)
                    result["average_rating"] = round(total_rating / len(reviews), 2)

            result["success"] = True
            result["message"] = "수집 완료"

        except Exception as e:
            result["message"] = str(e)
            logger.error(f"수집 오류: {e}")

        finally:
            self.stop()

        return result
