"""Shopee 셀러센터 리뷰 스크래퍼 (seller.shopee.kr)"""

import os
import re
import time
import json
import hashlib
import logging
from datetime import datetime, timedelta
from typing import Optional, List, Dict, Any

from selenium.webdriver.common.by import By
import undetected_chromedriver as uc

from app.scrapers.base import BaseScraper
from app.config import settings

logger = logging.getLogger(__name__)

# item_id → product_id 매핑 (추후 설정)
SHOPEE_ITEM_MAP = {
    # '57350851971': 9,
    # '42619206405': 10,
}

SHOP_ID = 1628086323


class ShopeeScraper(BaseScraper):
    """Shopee 셀러센터 리뷰 스크래퍼"""

    platform = "shopee"

    SIGNIN_URL = "https://seller.shopee.kr/account/signin"
    SELLER_HOME_URL = "https://seller.shopee.kr/"
    REVIEW_URL = "https://seller.shopee.kr/portal/settings/shop/rating?cnsc_shop_id=1628086323"

    def __init__(self):
        self.download_path = settings.DOWNLOAD_PATH
        self.cookie_file = settings.SHOPEE_COOKIE_PATH
        self.driver = None

        os.makedirs(self.download_path, exist_ok=True)

    def _get_chrome_options(self) -> uc.ChromeOptions:
        options = uc.ChromeOptions()
        options.add_argument("--no-sandbox")
        options.add_argument("--disable-dev-shm-usage")
        options.add_argument("--disable-gpu")
        options.add_argument("--disable-software-rasterizer")
        options.add_argument("--window-size=1920,1080")
        options.add_argument("--lang=ko-KR")
        options.add_argument("--disable-extensions")
        options.add_argument("--disable-infobars")
        options.add_argument("--disable-browser-side-navigation")
        options.add_argument("--disable-features=VizDisplayCompositor")
        return options

    def start(self):
        options = self._get_chrome_options()
        driver_path = "/usr/local/bin/chromedriver"
        chrome_kwargs = dict(
            options=options,
            headless=settings.CHROME_HEADLESS,
            driver_executable_path=driver_path if os.path.exists(driver_path) else None,
        )
        if settings.CHROME_VERSION:
            chrome_kwargs['version_main'] = settings.CHROME_VERSION
        self.driver = uc.Chrome(**chrome_kwargs)
        self.driver.implicitly_wait(10)
        logger.info(f"Chrome 브라우저 시작 (headless={settings.CHROME_HEADLESS})")

    def stop(self):
        if self.driver:
            try:
                self.driver.quit()
            except Exception:
                pass
            self.driver = None
            logger.info("Chrome 브라우저 종료")

    def _load_cookies(self) -> Optional[List[Dict]]:
        try:
            if os.path.exists(self.cookie_file):
                with open(self.cookie_file, 'r') as f:
                    cookies = json.load(f)
                logger.info(f"쿠키 로드: {len(cookies)}개")
                self._check_cookie_expiry(cookies)
                return cookies
        except Exception as e:
            logger.error(f"쿠키 로드 실패: {e}")
        return None

    def _check_cookie_expiry(self, cookies: List[Dict]):
        """인증 쿠키 만료 임박 경고"""
        import time as _time
        now = _time.time()
        auth_names = {'CNSC_SSO', 'SPC_CNSC_SESSION', 'SPC_SC_OFFLINE_TOKEN'}
        for c in cookies:
            if c.get('name') in auth_names and c.get('expiry'):
                remaining_days = (c['expiry'] - now) / 86400
                if remaining_days < 0:
                    logger.error(f"[Shopee] 인증 쿠키 {c['name']} 만료됨! 재로그인 필요")
                elif remaining_days < 2:
                    logger.warning(f"[Shopee] 인증 쿠키 {c['name']} {remaining_days:.1f}일 후 만료 - 재로그인 권장")

    def _save_cookies(self):
        try:
            cookies = self.driver.get_cookies()
            os.makedirs(os.path.dirname(self.cookie_file), exist_ok=True)
            with open(self.cookie_file, 'w') as f:
                json.dump(cookies, f, indent=2)
            logger.info(f"쿠키 저장: {len(cookies)}개")
        except Exception as e:
            logger.error(f"쿠키 저장 실패: {e}")

    def login(self) -> bool:
        """셀러센터 쿠키 로그인"""
        try:
            cookies = self._load_cookies()
            if not cookies:
                logger.error("쿠키 파일 없음 - save_shopee_cookies_local.py로 쿠키 저장 필요")
                return False

            logger.info("쿠키로 로그인 시도...")
            self.driver.get(self.SIGNIN_URL)
            time.sleep(2)

            for cookie in cookies:
                try:
                    if 'sameSite' in cookie:
                        del cookie['sameSite']
                    self.driver.add_cookie(cookie)
                except Exception:
                    pass

            self.driver.get(self.SELLER_HOME_URL)
            time.sleep(5)

            if "/signin" not in self.driver.current_url:
                self._save_cookies()
                logger.info("쿠키 로그인 성공")
                return True
            else:
                logger.error("쿠키 만료됨 - 수동 재로그인 후 쿠키 업로드 필요")
                return False

        except Exception as e:
            logger.error(f"로그인 오류: {e}")
            return False

    def _extract_reviews_js(self) -> List[Dict[str, Any]]:
        """JavaScript로 현재 페이지 리뷰 추출"""
        js_code = '''
        var results = [];

        // ratingListWrap 찾기
        var wrap = null;
        var allDivs = document.querySelectorAll('div');
        for (var i = 0; i < allDivs.length; i++) {
            if ((allDivs[i].className || '').includes('ratingListWrap')) {
                wrap = allDivs[i]; break;
            }
        }
        if (!wrap) return JSON.stringify([]);

        // 리뷰 카드: border rounded div
        var cards = wrap.querySelectorAll('div.rounded.border-solid');
        if (cards.length === 0) {
            var container = wrap.querySelector('.flex.flex-col.gap-y-4');
            if (container) cards = container.children;
        }

        for (var c = 0; c < cards.length; c++) {
            try {
                var card = cards[c];
                if ((card.innerText || '').length < 10) continue;

                // 헤더: bg-[#fafafa] (유저명, Order ID)
                var header = card.querySelector('div[class*="fafafa"]');
                var author = '', orderId = '';
                if (header) {
                    var headerLines = (header.innerText || '').split('\\n').map(function(l){ return l.trim(); }).filter(Boolean);
                    if (headerLines.length > 0) author = headerLines[0];
                    var orderMatch = (header.innerText || '').match(/Order ID[:\\s]*([A-Z0-9]+)/i);
                    if (orderMatch) orderId = orderMatch[1];
                }

                // 3열 구조: 상품정보 | 리뷰 | 액션
                var body = card.querySelector('div.divide-x');
                if (!body) continue;
                var columns = body.children;

                // 열0: 상품명
                var productName = '';
                if (columns.length > 0) {
                    var nameEl = columns[0].querySelector('.font-medium.break-all');
                    if (nameEl) productName = nameEl.innerText.trim();
                    if (!productName) productName = (columns[0].innerText || '').trim();
                }

                // 열1: 별점 + 리뷰 내용 + 날짜
                var rating = 5, content = '', reviewedAt = '';
                if (columns.length > 1) {
                    var reviewCol = columns[1];

                    // 별점: eds-react-rate-star의 __front width 체크
                    var stars = reviewCol.querySelectorAll('.eds-react-rate-star');
                    if (stars.length > 0) {
                        var filled = 0;
                        for (var sv = 0; sv < stars.length; sv++) {
                            var front = stars[sv].querySelector('.eds-react-rate-star__front');
                            if (front) {
                                var w = front.style.width;
                                if (w && parseFloat(w) > 0) filled++;
                            }
                        }
                        rating = filled || 5;
                    }

                    // 리뷰 텍스트 + 날짜 추출
                    var reviewText = reviewCol.innerText || '';
                    var lines = reviewText.split('\\n').map(function(l){ return l.trim(); }).filter(Boolean);

                    // 날짜 패턴: DD/MM/YYYY HH:MM
                    var datePattern = /^(\\d{2}\\/\\d{2}\\/\\d{4}\\s+\\d{2}:\\d{2})$/;
                    var contentParts = [];

                    for (var li = 0; li < lines.length; li++) {
                        var line = lines[li];
                        if (datePattern.test(line)) {
                            reviewedAt = line;
                        } else if (line !== 'Reply' && line !== 'Report' &&
                                   !line.match(/^\\d+ Star/) && line !== '...More') {
                            contentParts.push(line);
                        }
                    }
                    content = contentParts.join(' ').trim();
                }

                if (!orderId && !author) continue;

                results.push({
                    external_id: orderId || (author + '_' + (content || '').substring(0, 20)),
                    rating: rating,
                    content: content,
                    author: author,
                    order_id: orderId,
                    product_name: productName,
                    reviewed_at: reviewedAt
                });
            } catch(e) {}
        }
        return JSON.stringify(results);
        '''

        try:
            result = self.driver.execute_script(js_code)
            if result:
                return json.loads(result)
        except Exception as e:
            logger.error(f"JS 리뷰 추출 오류: {e}")

        return []

    def _get_total_count(self) -> int:
        """총 리뷰 수 추출"""
        try:
            text = self.driver.execute_script('''
                var wrap = null;
                var divs = document.querySelectorAll('div');
                for (var i = 0; i < divs.length; i++) {
                    if ((divs[i].className || '').includes('ratingListWrap')) {
                        wrap = divs[i];
                        break;
                    }
                }
                if (!wrap) return '';
                // "All (1588)" 텍스트에서 숫자 추출
                var allSpans = wrap.querySelectorAll('span');
                for (var j = 0; j < allSpans.length; j++) {
                    var t = allSpans[j].innerText || '';
                    var m = t.match(/\\(\\s*(\\d+)\\s*\\)/);
                    if (m && allSpans[j].previousElementSibling &&
                        (allSpans[j].previousElementSibling.innerText || '').includes('All')) {
                        return m[1];
                    }
                }
                // fallback
                var text = wrap.innerText || '';
                var m2 = text.match(/All\\s*\\(\\s*(\\d+)\\s*\\)/);
                return m2 ? m2[1] : '0';
            ''')
            return int(text) if text else 0
        except Exception:
            return 0

    def _go_next_page(self) -> bool:
        """다음 페이지로 이동"""
        try:
            clicked = self.driver.execute_script('''
                // eds-react-pagination-pager__button-next 버튼
                var nextBtn = document.querySelector(
                    '.eds-react-pagination-pager__button-next'
                );
                if (nextBtn && !nextBtn.disabled &&
                    !nextBtn.className.includes('disabled')) {
                    nextBtn.click();
                    return true;
                }
                return false;
            ''')
            if clicked:
                time.sleep(3)
            return clicked or False
        except Exception as e:
            logger.debug(f"다음 페이지 이동 실패: {e}")
            return False

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
                result["message"] = "로그인 실패 - 쿠키 업로드 필요"
                return result

            # 리뷰 페이지 접속
            logger.info("리뷰 페이지 접속...")
            self.driver.get(self.REVIEW_URL)
            time.sleep(8)

            if "/signin" in self.driver.current_url:
                result["message"] = "리뷰 페이지 접근 시 로그인 요구"
                return result

            total_count = self._get_total_count()
            logger.info(f"총 리뷰 수: {total_count}")

            # 페이지별 DOM 스크래핑 (최근 N일 리뷰만)
            cutoff_date = datetime.now() - timedelta(days=settings.SYNC_DAYS)
            all_reviews = []
            page = 1
            max_pages = (total_count // 20) + 2 if total_count else 100

            while page <= max_pages:
                page_reviews = self._extract_reviews_js()

                if not page_reviews:
                    logger.info(f"페이지 {page}: 리뷰 없음, 종료")
                    break

                # 날짜 기반 조기 종료: 페이지에 기간 외 리뷰가 있으면 이 페이지까지만 수집
                has_old = False
                for r in page_reviews:
                    rat = r.get('reviewed_at', '')
                    if rat:
                        m = re.match(r'(\d{2})/(\d{2})/(\d{4})', rat)
                        if m:
                            try:
                                rd = datetime(int(m.group(3)), int(m.group(2)), int(m.group(1)))
                                if rd < cutoff_date:
                                    has_old = True
                                    break
                            except ValueError:
                                pass

                all_reviews.extend(page_reviews)
                logger.info(f"페이지 {page}: {len(page_reviews)}개 (누적: {len(all_reviews)})")

                if has_old:
                    logger.info(f"페이지 {page}: {settings.SYNC_DAYS}일 이전 리뷰 감지 → 수집 중단")
                    break

                if not self._go_next_page():
                    logger.info(f"마지막 페이지: {page}")
                    break

                page += 1

            # 정규화 + 중복 제거 (order_id + product_name 기준) + 날짜 필터
            reviews = []
            seen_keys = set()
            for r in all_reviews:
                order_id = r.get('order_id', '')
                product_name = r.get('product_name', '')
                name_key = hashlib.md5(product_name.encode()).hexdigest()[:8]
                dedup_key = f"{order_id}_{name_key}"

                if dedup_key in seen_keys:
                    continue
                seen_keys.add(dedup_key)

                # external_id: order_id + product_name hash
                name_hash = hashlib.md5(product_name.encode()).hexdigest()[:6]
                if order_id:
                    ext_id = f"shopee_{order_id}_{name_hash}"
                else:
                    fallback = r.get('content', '') + r.get('author', '')
                    ext_id = f"shopee_{hashlib.md5(fallback.encode()).hexdigest()[:12]}"

                # DD/MM/YYYY HH:MM → YYYY-MM-DD HH:MM:SS
                reviewed_at_str = r.get('reviewed_at', '')
                if reviewed_at_str:
                    m = re.match(r'(\d{2})/(\d{2})/(\d{4})\s+(\d{2}):(\d{2})', reviewed_at_str)
                    if m:
                        reviewed_at_str = f"{m.group(3)}-{m.group(2)}-{m.group(1)} {m.group(4)}:{m.group(5)}:00"
                        # 날짜 필터: 기간 외 리뷰 스킵
                        try:
                            rd = datetime(int(m.group(3)), int(m.group(2)), int(m.group(1)))
                            if rd < cutoff_date:
                                continue
                        except ValueError:
                            pass

                review = self.normalize_review({
                    "external_id": ext_id,
                    "rating": r.get('rating', 5),
                    "title": None,
                    "content": r.get('content', ''),
                    "author": r.get('author', ''),
                    "product_code": name_hash,
                    "product_name": product_name,
                    "reviewed_at": reviewed_at_str,
                })
                review["product_id"] = None
                reviews.append(review)

            result["reviews"] = reviews
            result["total_count"] = len(reviews)

            if reviews:
                total_rating = sum(r.get("rating", 5.0) for r in reviews)
                result["average_rating"] = round(total_rating / len(reviews), 2)

            result["success"] = True
            result["message"] = f"수집 완료: {len(reviews)}개"
            logger.info(f"Shopee 리뷰 수집 완료: {len(reviews)}개")

            # 수집 성공 → 브라우저 닫기 전 갱신된 쿠키 저장
            self._save_cookies()

        except Exception as e:
            result["message"] = str(e)
            logger.error(f"수집 오류: {e}")
            import traceback
            traceback.print_exc()

        finally:
            self.stop()

        return result
