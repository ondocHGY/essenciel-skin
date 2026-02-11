"""네이버 스마트스토어 스크래퍼"""

import os
import time
import glob
import json
import random
from datetime import datetime
from typing import Optional, List, Dict, Any
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.keys import Keys
from selenium.common.exceptions import (
    TimeoutException, NoSuchElementException, UnexpectedAlertPresentException
)
import undetected_chromedriver as uc
import pandas as pd
import logging

from app.scrapers.base import BaseScraper
from app.config import settings

logger = logging.getLogger(__name__)


def human_type(element, text):
    """사람처럼 한 글자씩 타이핑"""
    for char in text:
        element.send_keys(char)
        time.sleep(random.uniform(0.03, 0.08))


class NaverScraper(BaseScraper):
    """네이버 스마트스토어 리뷰 스크래퍼"""

    platform = "naver"

    # 스마트스토어 센터 URL
    NAVER_LOGIN_URL = "https://accounts.commerce.naver.com/login"
    SMARTSTORE_HOME_URL = "https://sell.smartstore.naver.com/"
    SMARTSTORE_REVIEW_URL = "https://sell.smartstore.naver.com/#/review/search"

    def __init__(self):
        self.naver_id = settings.NAVER_ID
        self.naver_password = settings.NAVER_PASSWORD
        self.download_path = settings.DOWNLOAD_PATH
        self.cookie_file = settings.NAVER_COOKIE_PATH
        self.driver = None

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
        options.add_argument("--lang=ko-KR")

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
        """브라우저 시작"""
        options = self._get_chrome_options()
        self.driver = uc.Chrome(
            options=options,
            headless=settings.CHROME_HEADLESS,
            version_main=settings.CHROME_VERSION,
        )
        self.driver.implicitly_wait(10)
        logger.info(f"Chrome 브라우저 시작 (headless={settings.CHROME_HEADLESS})")

    def stop(self):
        """브라우저 종료"""
        if self.driver:
            self.driver.quit()
            self.driver = None
            logger.info("Chrome 브라우저 종료")

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
        """네이버 커머스 로그인"""
        try:
            # 쿠키 로그인 시도
            cookies = self._load_cookies()
            if cookies:
                logger.info("쿠키로 로그인 시도...")
                self.driver.get(self.NAVER_LOGIN_URL)
                time.sleep(2)

                for cookie in cookies:
                    try:
                        if 'sameSite' in cookie:
                            del cookie['sameSite']
                        self.driver.add_cookie(cookie)
                    except Exception:
                        pass

                self.driver.get(self.SMARTSTORE_HOME_URL)
                time.sleep(3)

                # 로그인 상태 확인 (로그인 페이지로 리다이렉트 안되면 성공)
                if "login" not in self.driver.current_url.lower():
                    self._save_cookies()  # 갱신된 쿠키 재저장 (수명 연장)
                    logger.info("쿠키 로그인 성공 (쿠키 갱신 저장)")
                    return True
                else:
                    logger.warning("쿠키 만료됨, ID/PW 로그인 시도")

            # ID/PW 로그인 시도
            logger.info("ID/PW 로그인 시도...")
            self.driver.get(self.NAVER_LOGIN_URL)
            time.sleep(5)

            logger.info(f"현재 URL: {self.driver.current_url}")

            wait = WebDriverWait(self.driver, 20)

            # 네이버 커머스 로그인 폼 - ID 입력창
            id_selectors = [
                (By.CSS_SELECTOR, "input[type='text']"),
                (By.CSS_SELECTOR, "input[placeholder*='아이디']"),
                (By.CSS_SELECTOR, "input[placeholder*='이메일']"),
                (By.CSS_SELECTOR, "input[name='id']"),
                (By.ID, "id"),
            ]

            pw_selectors = [
                (By.CSS_SELECTOR, "input[type='password']"),
                (By.CSS_SELECTOR, "input[name='pw']"),
                (By.ID, "pw"),
            ]

            id_input = None
            for by, selector in id_selectors:
                try:
                    id_input = self.driver.find_element(by, selector)
                    logger.info(f"ID 입력창 발견: {selector}")
                    break
                except:
                    continue

            if not id_input:
                logger.error("ID 입력창을 찾을 수 없음")
                self.driver.save_screenshot("login_page_debug.png")
                return False

            pw_input = None
            for by, selector in pw_selectors:
                try:
                    pw_input = self.driver.find_element(by, selector)
                    logger.info(f"PW 입력창 발견: {selector}")
                    break
                except:
                    continue

            if not pw_input:
                logger.error("PW 입력창을 찾을 수 없음")
                return False

            # 사람처럼 한 글자씩 타이핑 (JS injection 대신)
            id_input.click()
            time.sleep(0.3)
            human_type(id_input, self.naver_id)
            time.sleep(0.5)

            id_value = id_input.get_attribute('value')
            logger.info(f"ID 입력 완료 (길이: {len(id_value)})")

            pw_input.click()
            time.sleep(0.3)
            human_type(pw_input, self.naver_password)
            time.sleep(1)

            pw_value = pw_input.get_attribute('value')
            logger.info(f"PW 입력 완료 (길이: {len(pw_value)})")

            if not id_value or not pw_value:
                logger.error("입력값이 비어있음!")
                self.driver.save_screenshot("login_input_empty.png")
                return False

            # 로그인 버튼 클릭
            login_selectors = [
                (By.XPATH, "//button[text()='로그인']"),
                (By.XPATH, "//button[contains(@class, 'Button_btn')]"),
                (By.CSS_SELECTOR, "button[class*='Button_btn']"),
                (By.CSS_SELECTOR, "button[type='submit']"),
                (By.XPATH, "//button[contains(text(), '로그인')]"),
                (By.CSS_SELECTOR, "button[class*='login']"),
            ]

            clicked = False
            for by, selector in login_selectors:
                try:
                    login_btn = self.driver.find_element(by, selector)
                    logger.info(f"로그인 버튼 발견: {selector}, text='{login_btn.text}'")
                    login_btn.click()
                    logger.info(f"로그인 버튼 클릭 완료")
                    clicked = True
                    break
                except Exception as e:
                    logger.debug(f"셀렉터 실패: {selector} - {e}")
                    continue

            if not clicked:
                logger.error("로그인 버튼을 찾을 수 없음")
                self.driver.save_screenshot("login_button_not_found.png")
                return False

            time.sleep(8)

            # 로그인 결과 확인
            current_url = self.driver.current_url
            logger.info(f"로그인 후 URL: {current_url}")

            if "login" in current_url.lower() or "captcha" in current_url.lower():
                logger.error("로그인 실패 - 캡챠 또는 추가 인증 필요")
                self.driver.save_screenshot("login_failed_debug.png")
                return False

            # 스마트스토어 센터로 이동
            self.driver.get(self.SMARTSTORE_HOME_URL)
            time.sleep(3)

            # 최종 확인
            if "login" in self.driver.current_url.lower():
                logger.error("스마트스토어 접근 실패")
                return False

            self._save_cookies()
            logger.info("로그인 성공")
            return True

        except Exception as e:
            logger.error(f"로그인 오류: {e}")
            import traceback
            traceback.print_exc()
            return False

    def _navigate_to_reviews(self) -> bool:
        """리뷰 검색 페이지 이동 및 필터 설정"""
        try:
            self.driver.get(self.SMARTSTORE_REVIEW_URL)
            time.sleep(5)  # SPA 로딩 대기

            # 리뷰 페이지에서 로그인 요구 여부 확인
            if "login" in self.driver.current_url.lower():
                logger.error("리뷰 페이지 접근 시 로그인 페이지로 리다이렉트됨")
                return False

            try:
                login_btns = self.driver.find_elements(By.XPATH, "//button[contains(text(), '로그인')]")
                if login_btns:
                    logger.error(f"리뷰 페이지에서 로그인 버튼 발견 ({len(login_btns)}개) - 인증 실패")
                    return False
            except Exception:
                pass

            wait = WebDriverWait(self.driver, 15)

            # 리뷰 작성일 기간 설정 - "1개월" 버튼 클릭
            period_selectors = [
                (By.XPATH, "//button[text()='1개월']"),
                (By.XPATH, "//button[contains(text(), '1개월')]"),
                (By.XPATH, "//button[text()='3개월']"),
                (By.XPATH, "//button[contains(text(), '3개월')]"),
            ]

            for by, selector in period_selectors:
                try:
                    btn = self.driver.find_element(by, selector)
                    btn.click()
                    logger.info(f"기간 설정 클릭: {selector}")
                    time.sleep(3)
                    break
                except Exception as e:
                    logger.debug(f"기간 셀렉터 실패: {selector}")
                    continue

            logger.info("리뷰 페이지 이동 완료")
            return True
        except Exception as e:
            logger.error(f"리뷰 페이지 이동 오류: {e}")
            return False

    def _download_file(self) -> Optional[str]:
        """Excel 다운로드 (엑셀다운 버튼 → 모달 확인)"""
        try:
            # 기존 파일 삭제
            for f in glob.glob(os.path.join(self.download_path, "*.xls*")):
                os.remove(f)
            for f in glob.glob(os.path.join(self.download_path, "*.csv")):
                os.remove(f)

            wait = WebDriverWait(self.driver, 10)

            # 페이지 디버깅: 모든 버튼 텍스트 출력
            try:
                buttons = self.driver.find_elements(By.TAG_NAME, "button")
                logger.info(f"페이지 내 버튼 수: {len(buttons)}")
                for btn in buttons[:10]:
                    logger.info(f"  버튼: '{btn.text}' class='{btn.get_attribute('class')}'")
            except:
                pass

            # 엑셀 다운로드 버튼 클릭
            excel_selectors = [
                (By.XPATH, "//button[contains(text(), '엑셀다운')]"),
                (By.XPATH, "//button[contains(text(), '엑셀 다운')]"),
                (By.XPATH, "//button[contains(text(), '엑셀')]"),
                (By.XPATH, "//button[contains(., '엑셀')]"),
                (By.XPATH, "//span[contains(text(), '엑셀')]/parent::button"),
                (By.XPATH, "//button[contains(text(), 'Excel')]"),
                (By.XPATH, "//button[contains(text(), '다운로드')]"),
                (By.XPATH, "//a[contains(text(), '엑셀')]"),
                (By.CSS_SELECTOR, "button[class*='excel']"),
                (By.CSS_SELECTOR, "button[class*='Excel']"),
                (By.CSS_SELECTOR, "button[class*='download']"),
            ]

            clicked = False
            for by, selector in excel_selectors:
                try:
                    btn = self.driver.find_element(by, selector)
                    btn.click()
                    clicked = True
                    logger.info(f"엑셀 다운로드 버튼 클릭: {selector}")
                    break
                except Exception as e:
                    logger.debug(f"엑셀 셀렉터 실패: {selector}")
                    continue

            if not clicked:
                logger.warning("엑셀 다운로드 버튼을 찾을 수 없음")
                return None

            time.sleep(3)

            # 모달 확인 버튼 클릭
            confirm_selectors = [
                (By.XPATH, "//div[contains(@class, 'modal')]//button[contains(text(), '확인')]"),
                (By.XPATH, "//div[contains(@class, 'Modal')]//button[contains(text(), '확인')]"),
                (By.XPATH, "//div[@role='dialog']//button[contains(text(), '확인')]"),
                (By.XPATH, "//button[contains(text(), '확인')]"),
                (By.XPATH, "//button[contains(text(), '다운로드')]"),
                (By.CSS_SELECTOR, "[class*='modal'] button[class*='primary']"),
                (By.CSS_SELECTOR, "[class*='Modal'] button[class*='primary']"),
                (By.CSS_SELECTOR, "[role='dialog'] button"),
            ]

            for by, selector in confirm_selectors:
                try:
                    confirm_btn = wait.until(EC.element_to_be_clickable((by, selector)))
                    confirm_btn.click()
                    logger.info(f"모달 확인 버튼 클릭: {selector}")
                    break
                except Exception as e:
                    logger.debug(f"모달 셀렉터 실패: {selector}")
                    continue

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

    def _parse_page(self) -> List[Dict[str, Any]]:
        """페이지에서 직접 리뷰 파싱"""
        reviews = []

        try:
            wait = WebDriverWait(self.driver, 10)

            # 리뷰 목록 컨테이너 찾기
            review_items = self.driver.find_elements(
                By.CSS_SELECTOR, "[class*='review-item'], [class*='ReviewItem'], tr[class*='review']"
            )

            for item in review_items:
                try:
                    review = {
                        "external_id": None,
                        "content": "",
                        "rating": 5.0,
                        "reviewed_at": None,
                        "author": None,
                        "title": None,
                    }

                    # 리뷰 내용
                    try:
                        content_el = item.find_element(
                            By.CSS_SELECTOR, "[class*='content'], [class*='Content'], td:nth-child(3)"
                        )
                        review["content"] = content_el.text.strip()
                    except:
                        pass

                    # 별점
                    try:
                        rating_el = item.find_element(
                            By.CSS_SELECTOR, "[class*='rating'], [class*='Rating'], [class*='star']"
                        )
                        rating_text = rating_el.get_attribute("class") or rating_el.text
                        # 별점 파싱 (예: "star5", "rating-5" 등)
                        import re
                        rating_match = re.search(r'(\d)', rating_text)
                        if rating_match:
                            review["rating"] = float(rating_match.group(1))
                    except:
                        pass

                    # 작성자
                    try:
                        author_el = item.find_element(
                            By.CSS_SELECTOR, "[class*='author'], [class*='Author'], [class*='user']"
                        )
                        review["author"] = author_el.text.strip()
                    except:
                        pass

                    # 작성일
                    try:
                        date_el = item.find_element(
                            By.CSS_SELECTOR, "[class*='date'], [class*='Date'], [class*='time']"
                        )
                        review["reviewed_at"] = date_el.text.strip()
                    except:
                        pass

                    if review["content"] and len(review["content"]) > 5:
                        # external_id 생성
                        review["external_id"] = f"naver_{hash(review['content'][:50])}"
                        reviews.append(self.normalize_review(review))

                except Exception as e:
                    logger.debug(f"리뷰 아이템 파싱 실패: {e}")
                    continue

            logger.info(f"페이지 파싱 완료: {len(reviews)}개 리뷰")

        except Exception as e:
            logger.error(f"페이지 파싱 오류: {e}")

        return reviews

    def _parse_file(self, file_path: str) -> List[Dict[str, Any]]:
        """Excel/CSV 파싱"""
        reviews = []

        try:
            if file_path.endswith('.csv'):
                df = pd.read_csv(file_path, encoding='utf-8')
            elif file_path.endswith('.xls'):
                df = pd.read_excel(file_path, engine='xlrd')
            else:
                df = pd.read_excel(file_path, engine='openpyxl')

            df.columns = df.columns.str.strip()

            # 네이버 스마트스토어 컬럼 매핑
            column_mapping = {
                # 리뷰 내용
                '리뷰상세내용': 'content',
                '리뷰내용': 'content',
                '리뷰 내용': 'content',
                '내용': 'content',
                # 평점
                '구매자평점': 'rating',
                '평점': 'rating',
                '별점': 'rating',
                # 작성자
                '등록자': 'author',
                '작성자': 'author',
                '구매자': 'author',
                # 작성일
                '리뷰등록일': 'reviewed_at',
                '작성일': 'reviewed_at',
                '등록일': 'reviewed_at',
                # 리뷰 ID
                '리뷰글번호': 'external_id',
                '리뷰번호': 'external_id',
                '리뷰 번호': 'external_id',
                # 상품 정보
                '상품번호': 'product_code',
                '상품명': 'product_name',
                '옵션': 'purchased_option',
                '구매옵션': 'purchased_option',
            }

            for idx, row in df.iterrows():
                review = {
                    "external_id": None,
                    "content": "",
                    "rating": 5.0,
                    "reviewed_at": None,
                    "author": None,
                    "purchased_option": None,
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
                                # Format: "2026.02.06. 12:56:17" -> "2026-02-06 12:56:17"
                                date_str = str(value).strip()
                                # Replace dots with dashes for date part
                                date_str = date_str.replace('. ', ' ').replace('.', '-')
                                review[field] = date_str
                            elif field == "external_id":
                                review[field] = f"naver_{value}"
                            elif field == "product_code":
                                review[field] = str(int(value)) if isinstance(value, float) else str(value).strip()
                            else:
                                review[field] = str(value).strip()

                # external_id 없으면 생성
                if not review["external_id"] and review["content"]:
                    review["external_id"] = f"naver_{idx}_{hash(review['content'][:30])}"

                if review["content"] and len(review["content"]) > 5:
                    reviews.append(self.normalize_review(review))

            logger.info(f"파일 파싱 완료: {len(reviews)}개 리뷰")

        except Exception as e:
            logger.error(f"파일 파싱 오류: {e}")

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

            # 파일 다운로드 시도
            file_path = self._download_file()
            reviews = []

            if file_path:
                result["file_path"] = file_path
                reviews = self._parse_file(file_path)
            else:
                # 다운로드 실패 시 페이지 파싱
                logger.info("파일 다운로드 실패, 페이지 직접 파싱")
                reviews = self._parse_page()

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
