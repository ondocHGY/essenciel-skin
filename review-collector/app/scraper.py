import os
import time
import glob
from datetime import datetime
from typing import Optional, List, Dict, Any
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException, UnexpectedAlertPresentException
import undetected_chromedriver as uc
import pandas as pd
import logging

try:
    from pyvirtualdisplay import Display
    XVFB_AVAILABLE = True
except ImportError:
    XVFB_AVAILABLE = False

from app.config import settings

logger = logging.getLogger(__name__)


class Qoo10Scraper:
    """Qoo10 QSM 리뷰 스크래퍼"""

    QSM_LOGIN_URL = "https://qsm.qoo10.jp/GMKT.INC.Gsm.Web/Login.aspx"
    QSM_REVIEW_URL = "https://qsm.qoo10.jp/GMKT.INC.Gsm.Web/Seller/MyReviewMgt.aspx"
    QSM_HOME_URL = "https://qsm.qoo10.jp/GMKT.INC.Gsm.Web/Default.aspx"
    COOKIE_FILE = "/var/www/essenciel_qrcode/review-collector/qsm_cookies.json"

    def __init__(self, qsm_id: str = None, qsm_password: str = None):
        self.qsm_id = qsm_id or settings.QSM_ID
        self.qsm_password = qsm_password or settings.QSM_PASSWORD
        self.download_path = settings.DOWNLOAD_PATH
        self.driver = None
        self.display = None

        # 다운로드 디렉토리 생성
        os.makedirs(self.download_path, exist_ok=True)

    def _get_chrome_options(self) -> uc.ChromeOptions:
        """Chrome 옵션 설정"""
        options = uc.ChromeOptions()

        options.add_argument("--no-sandbox")
        options.add_argument("--disable-dev-shm-usage")
        options.add_argument("--disable-gpu")
        options.add_argument("--window-size=1920,1080")
        options.add_argument("--lang=ja-JP")

        # 다운로드 설정
        prefs = {
            "download.default_directory": self.download_path,
            "download.prompt_for_download": False,
            "download.directory_upgrade": True,
            "safebrowsing.enabled": True
        }
        options.add_experimental_option("prefs", prefs)

        return options

    def start(self):
        """브라우저 시작 (undetected-chromedriver + 가상 디스플레이)"""
        # 가상 디스플레이 시작 (headless 대신 사용)
        if XVFB_AVAILABLE and settings.CHROME_HEADLESS:
            self.display = Display(visible=0, size=(1920, 1080))
            self.display.start()
            logger.info("가상 디스플레이 시작")

        options = self._get_chrome_options()
        self.driver = uc.Chrome(
            options=options,
            headless=False,  # 가상 디스플레이 사용 시 headless 비활성화
            use_subprocess=True,
            version_main=144  # 설치된 Chrome 버전에 맞춤
        )
        self.driver.implicitly_wait(10)
        logger.info("Chrome 브라우저 시작 (undetected)")

    def stop(self):
        """브라우저 종료"""
        if self.driver:
            self.driver.quit()
            self.driver = None
            logger.info("Chrome 브라우저 종료")

        # 가상 디스플레이 종료
        if self.display:
            self.display.stop()
            self.display = None
            logger.info("가상 디스플레이 종료")

    def _load_cookies(self) -> bool:
        """저장된 쿠키 로드"""
        import json
        try:
            if os.path.exists(self.COOKIE_FILE):
                with open(self.COOKIE_FILE, 'r') as f:
                    cookies = json.load(f)
                logger.info(f"쿠키 파일 로드: {len(cookies)}개")
                return cookies
        except Exception as e:
            logger.error(f"쿠키 로드 실패: {e}")
        return None

    def _save_cookies(self):
        """현재 쿠키 저장"""
        import json
        try:
            cookies = self.driver.get_cookies()
            with open(self.COOKIE_FILE, 'w') as f:
                json.dump(cookies, f, indent=2)
            logger.info(f"쿠키 저장 완료: {len(cookies)}개")
        except Exception as e:
            logger.error(f"쿠키 저장 실패: {e}")

    def login(self) -> bool:
        """QSM 로그인 (쿠키 방식 우선)"""
        try:
            # 1. 쿠키가 있으면 쿠키로 로그인 시도
            cookies = self._load_cookies()
            if cookies:
                logger.info("저장된 쿠키로 로그인 시도...")

                # 도메인 접속 (쿠키 설정 전 필요)
                self.driver.get(self.QSM_LOGIN_URL)
                time.sleep(2)

                # 쿠키 설정
                for cookie in cookies:
                    try:
                        # sameSite 속성 제거 (호환성)
                        if 'sameSite' in cookie:
                            del cookie['sameSite']
                        self.driver.add_cookie(cookie)
                    except Exception as e:
                        logger.debug(f"쿠키 설정 실패: {cookie.get('name')} - {e}")

                # 홈페이지로 이동하여 세션 확인
                self.driver.get(self.QSM_HOME_URL)
                time.sleep(3)

                # 로그인 상태 확인
                if "Login.aspx" not in self.driver.current_url:
                    logger.info("쿠키 로그인 성공!")
                    return True
                else:
                    logger.warning("쿠키가 만료되었습니다. 수동 로그인이 필요합니다.")

            # 2. 쿠키가 없거나 만료된 경우 - ID/PW 로그인 시도
            logger.info("QSM ID/PW 로그인 시도...")
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

            logger.info("ID/PW 입력 완료")
            time.sleep(2)

            # 로그인 버튼 클릭
            login_btn = self.driver.find_element(By.CSS_SELECTOR, "button.g-recaptcha")
            from selenium.webdriver.common.action_chains import ActionChains
            actions = ActionChains(self.driver)
            actions.move_to_element(login_btn).pause(0.5).click().perform()

            logger.info("로그인 버튼 클릭")
            time.sleep(8)

            # alert 처리
            try:
                alert = self.driver.switch_to.alert
                alert_text = alert.text
                logger.warning(f"Alert 발생: {alert_text}")
                alert.accept()
                time.sleep(2)
            except:
                pass

            # 로그인 성공 확인
            current_url = self.driver.current_url
            logger.info(f"로그인 후 URL: {current_url}")

            if "Login.aspx" in current_url:
                self.driver.save_screenshot("/tmp/login_failed.png")
                logger.error("로그인 실패 - reCAPTCHA 확인 필요. save_cookies.py로 수동 로그인 후 쿠키를 저장하세요.")
                return False

            # 로그인 성공 - 쿠키 저장
            self._save_cookies()
            logger.info("QSM 로그인 성공")
            return True

        except UnexpectedAlertPresentException as e:
            logger.error(f"로그인 중 Alert 발생: {e.alert_text}")
            try:
                self.driver.switch_to.alert.accept()
            except:
                pass
            return False

        except Exception as e:
            logger.error(f"로그인 오류: {e}")
            import traceback
            traceback.print_exc()
            return False

    def navigate_to_reviews(self) -> bool:
        """리뷰 관리 페이지로 이동"""
        try:
            logger.info("리뷰 관리 페이지로 이동...")
            self.driver.get(self.QSM_REVIEW_URL)
            time.sleep(2)

            # 페이지 로드 확인
            if "MyReviewMgt" not in self.driver.current_url:
                logger.error("리뷰 페이지 이동 실패")
                return False

            logger.info("리뷰 관리 페이지 로드 완료")
            return True

        except Exception as e:
            logger.error(f"리뷰 페이지 이동 오류: {e}")
            return False

    def search_reviews(self) -> bool:
        """검색 버튼 클릭"""
        try:
            logger.info("리뷰 검색 실행...")

            # 검색 버튼 찾기 (onclick="btn_search_Onclick();")
            search_selectors = [
                (By.CSS_SELECTOR, "a[onclick*='btn_search']"),
                (By.CSS_SELECTOR, "a[onclick*='search']"),
                (By.XPATH, "//a[contains(text(), '검색')]"),
                (By.XPATH, "//a[contains(text(), '検索')]"),
                (By.CSS_SELECTOR, "button.btn_srch"),
            ]

            clicked = False
            for by, selector in search_selectors:
                try:
                    btn = self.driver.find_element(by, selector)
                    btn.click()
                    logger.info(f"검색 버튼 클릭: {selector}")
                    clicked = True
                    break
                except NoSuchElementException:
                    continue

            if not clicked:
                # JavaScript로 직접 호출
                self.driver.execute_script("btn_search_Onclick();")
                logger.info("검색 함수 직접 호출")

            # 결과 로딩 대기
            time.sleep(5)
            logger.info("검색 완료")
            return True

        except Exception as e:
            logger.error(f"검색 오류: {e}")
            return False

    def download_excel(self) -> Optional[str]:
        """Excel 다운로드"""
        try:
            logger.info("Excel 다운로드 시작...")

            # 기존 파일 삭제
            for f in glob.glob(os.path.join(self.download_path, "*.xls*")):
                os.remove(f)

            # Excel 버튼 찾기 (onclick="btn_excel_Onclick();")
            excel_selectors = [
                (By.CSS_SELECTOR, "a[onclick*='btn_excel']"),
                (By.CSS_SELECTOR, "a[onclick*='excel']"),
                (By.XPATH, "//a[contains(text(), 'Excel')]"),
                (By.CSS_SELECTOR, "button.btn_download"),
            ]

            clicked = False
            for by, selector in excel_selectors:
                try:
                    btn = self.driver.find_element(by, selector)
                    btn.click()
                    logger.info(f"Excel 버튼 클릭: {selector}")
                    clicked = True
                    break
                except NoSuchElementException:
                    continue

            if not clicked:
                # JavaScript로 직접 호출
                self.driver.execute_script("btn_excel_Onclick();")
                logger.info("Excel 함수 직접 호출")

            # 다운로드 완료 대기 (Excel 또는 CSV)
            timeout = 60
            start_time = time.time()
            downloaded_file = None

            while time.time() - start_time < timeout:
                # Excel과 CSV 모두 검색
                files = glob.glob(os.path.join(self.download_path, "*.xls*"))
                files += glob.glob(os.path.join(self.download_path, "*.csv"))
                # 임시 파일(.crdownload, .tmp) 제외
                files = [f for f in files if not f.endswith(('.crdownload', '.tmp'))]

                if files:
                    downloaded_file = max(files, key=os.path.getctime)
                    logger.info(f"파일 다운로드 완료: {downloaded_file}")
                    return downloaded_file

                time.sleep(1)

            logger.error("파일 다운로드 타임아웃")
            return None

        except Exception as e:
            logger.error(f"Excel 다운로드 오류: {e}")
            return None

    def scrape_from_page(self) -> List[Dict[str, Any]]:
        """페이지에서 직접 리뷰 데이터 추출 (Excel 실패 시 대안)"""
        reviews = []
        try:
            logger.info("페이지에서 리뷰 데이터 추출...")

            # 테이블 행 찾기
            rows = self.driver.find_elements(By.CSS_SELECTOR, "table tr")

            for row in rows[1:]:  # 헤더 스킵
                try:
                    cells = row.find_elements(By.TAG_NAME, "td")
                    if len(cells) < 3:
                        continue

                    cell_texts = [c.text.strip() for c in cells]

                    # 가장 긴 텍스트를 리뷰 내용으로
                    content = max(cell_texts, key=len) if cell_texts else ""
                    if len(content) < 20:
                        continue

                    # 평점 찾기
                    rating = 5.0
                    for text in cell_texts:
                        if text.isdigit() and 1 <= int(text) <= 5:
                            rating = float(text)
                            break

                    # 날짜 찾기
                    reviewed_at = None
                    import re
                    for text in cell_texts:
                        date_match = re.search(r'\d{4}[-/]\d{1,2}[-/]\d{1,2}', text)
                        if date_match:
                            reviewed_at = date_match.group()
                            break

                    reviews.append({
                        "content": content,
                        "rating": rating,
                        "reviewed_at": reviewed_at,
                        "author": None
                    })

                except Exception as e:
                    continue

            logger.info(f"페이지에서 {len(reviews)}개 리뷰 추출")

        except Exception as e:
            logger.error(f"페이지 파싱 오류: {e}")

        return reviews

    def fetch_reviews(self) -> Dict[str, Any]:
        """전체 리뷰 수집 프로세스"""
        result = {
            "success": False,
            "reviews": [],
            "total_count": 0,
            "average_rating": 0,
            "message": "",
            "excel_path": None
        }

        try:
            self.start()

            # 1. 로그인
            if not self.login():
                result["message"] = "로그인 실패"
                return result

            # 2. 리뷰 페이지 이동
            if not self.navigate_to_reviews():
                result["message"] = "리뷰 페이지 이동 실패"
                return result

            # 3. 검색
            if not self.search_reviews():
                result["message"] = "검색 실패"
                return result

            # 4. Excel 다운로드 시도
            excel_path = self.download_excel()

            if excel_path:
                result["excel_path"] = excel_path
                # Excel 파싱은 별도 함수에서 처리
            else:
                # Excel 실패 시 페이지에서 직접 추출
                logger.warning("Excel 다운로드 실패, 페이지에서 직접 추출")
                result["reviews"] = self.scrape_from_page()

            result["success"] = True
            result["message"] = "리뷰 수집 완료"

        except Exception as e:
            result["message"] = str(e)
            logger.error(f"리뷰 수집 오류: {e}")

        finally:
            self.stop()

        return result


def parse_excel(file_path: str) -> List[Dict[str, Any]]:
    """QSM Excel/CSV 파일 파싱"""
    reviews = []

    try:
        # 파일 형식에 따라 읽기
        if file_path.endswith('.csv'):
            df = pd.read_csv(file_path, encoding='utf-8')
        elif file_path.endswith('.xls'):
            df = pd.read_excel(file_path, engine='xlrd')
        else:
            df = pd.read_excel(file_path, engine='openpyxl')

        logger.info(f"파일 컬럼: {df.columns.tolist()}")
        logger.info(f"파일 행 수: {len(df)}")

        # 컬럼명 정규화
        df.columns = df.columns.str.strip()

        # QSM CSV 컬럼명 매핑
        # "상품평번호_h","댓글","상품명","상품코드","작성자ID","작성일","주문번호","만족도","포토"
        column_mapping = {
            # QSM CSV 형식 (한국어)
            '댓글': 'content',
            '상품평번호_h': 'external_id',
            '작성자ID': 'author',
            '작성일': 'reviewed_at',
            '만족도': 'rating',
            '상품코드': 'product_code',
            '상품명': 'product_name',
            '포토': 'has_photo',
            # QSM Excel 형식 (일본어)
            'レビュー内容': 'content',
            '内容': 'content',
            'Review': 'content',
            '評価': 'rating',
            '星': 'rating',
            'Rating': 'rating',
            '登録日': 'reviewed_at',
            '日付': 'reviewed_at',
            'Date': 'reviewed_at',
            '購入者': 'author',
            'ニックネーム': 'author',
            'Buyer': 'author',
            'オプション': 'purchased_option',
            'Option': 'purchased_option'
        }

        for _, row in df.iterrows():
            review = {
                "external_id": None,
                "content": "",
                "rating": 5.0,
                "reviewed_at": None,
                "author": None,
                "purchased_option": None,
                "product_code": None,
            }

            for file_col, field in column_mapping.items():
                if file_col in df.columns:
                    value = row[file_col]
                    if pd.notna(value):
                        if field == "rating":
                            try:
                                review[field] = float(value)
                            except:
                                review[field] = 5.0
                        elif field == "reviewed_at":
                            try:
                                if isinstance(value, datetime):
                                    review[field] = value.strftime("%Y-%m-%d")
                                else:
                                    # "2026/01/27 " 형식 처리
                                    date_str = str(value).strip()
                                    review[field] = date_str.replace('/', '-')
                            except:
                                pass
                        elif field == "external_id":
                            review[field] = f"qoo10_{value}"
                        else:
                            review[field] = str(value).strip()

            # 리뷰 내용이 있는 경우만 추가
            if review["content"] and len(review["content"]) > 5:
                reviews.append(review)

        logger.info(f"파일에서 {len(reviews)}개 리뷰 파싱")

    except Exception as e:
        logger.error(f"파일 파싱 오류: {e}")
        import traceback
        traceback.print_exc()

    return reviews
