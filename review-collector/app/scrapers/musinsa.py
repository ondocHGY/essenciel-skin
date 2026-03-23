"""무신사 파트너 스크래퍼 - SSO API 로그인 + OTP 방식"""

import logging
from datetime import datetime, timedelta
from typing import Dict, Any, List

import pyotp
import requests

from app.config import settings
from app.scrapers.base import BaseScraper

logger = logging.getLogger(__name__)

# SSO 인증 API
SSO_API = "https://api.one.musinsa.com/api2/partner/oauth/v3/authentication"

# bizest.musinsa.com: 레거시 백엔드 (리뷰 관리 iframe)
REVIEW_PAGE_URL = "https://bizest.musinsa.com/po/csm/csm07?&LAYOUT_TYPE=popup"
REVIEW_API_URL = "https://bizest.musinsa.com/po/api/csm/csm07/search"
IMAGE_BASE_URL = "https://image.msscdn.net"


class MusinsaScraper(BaseScraper):
    """무신사 리뷰 스크래퍼 (SSO API 로그인 + OTP)

    인증 흐름:
    1. POST /login/password → sso-pre-auth-token 쿠키 발급
    2. POST /login/otp (pyotp로 TOTP 생성) → 전체 인증 쿠키 발급
       (pp-auth, pp-auth-rtk, partner-platform-atk, partner-platform-rtk 등)
    3. GET bizest.musinsa.com 리뷰 페이지 → PHPSESSID 세션 쿠키 획득
    4. POST bizest API → 리뷰 JSON 반환
    """

    platform = "musinsa"

    def __init__(self):
        self.session = None

    REQUEST_TIMEOUT = 30  # HTTP 요청 타임아웃 (초)

    def start(self):
        self.session = requests.Session()
        self.session.headers.update({
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
                          "(KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36",
            "Accept-Language": "ko-KR,ko;q=0.9",
        })

    def stop(self):
        if self.session:
            self.session.close()
            self.session = None

    def _is_login_page(self, resp) -> bool:
        """응답이 로그인 페이지인지 확인"""
        if "login" in resp.url.lower() or "oauth" in resp.url.lower():
            return True
        if "form1.submit()" in resp.text and "/po/login" in resp.text:
            return True
        return False

    def login(self) -> bool:
        """SSO API 로그인 (ID/PW + OTP)

        1. password 로그인 → sso-pre-auth-token
        2. OTP 인증 → 전체 인증 쿠키 발급
        3. bizest 리뷰 페이지 접속 → 세션 쿠키 획득
        """
        musinsa_id = settings.MUSINSA_ID
        musinsa_pw = settings.MUSINSA_PASSWORD
        otp_secret = settings.MUSINSA_OTP_SECRET

        if not musinsa_id or not musinsa_pw:
            logger.error("MUSINSA_ID 또는 MUSINSA_PASSWORD 미설정")
            return False

        if not otp_secret:
            logger.error("MUSINSA_OTP_SECRET 미설정")
            return False

        try:
            # Step 1: 비밀번호 로그인
            logger.info("Step 1: 비밀번호 로그인...")
            resp = self.session.post(f"{SSO_API}/login/password", json={
                "id": musinsa_id,
                "password": musinsa_pw,
                "clientId": "MUSINSA_PARTNER",
                "platform": "mss",
                "redirectUri": "https://partner.musinsa.com",
            }, timeout=self.REQUEST_TIMEOUT)

            if resp.status_code != 200:
                logger.error(f"비밀번호 로그인 실패: HTTP {resp.status_code} - {resp.text[:200]}")
                return False

            body = resp.json()
            auth_status = body.get("data", {}).get("authenticationStatus", "")

            if auth_status != "OTP_VERIFICATION_REQUIRED":
                logger.error(f"예상치 못한 인증 상태: {auth_status}")
                return False

            logger.info("비밀번호 인증 성공 → OTP 단계 진입")

            # Step 2: OTP 인증
            totp = pyotp.TOTP(otp_secret)
            otp_code = totp.now()
            logger.info("Step 2: OTP 인증...")

            resp = self.session.post(f"{SSO_API}/login/otp", json={
                "id": musinsa_id,
                "code": otp_code,
                "twoFactorType": "OTP",
                "platform": "mss",
                "clientId": "MUSINSA_PARTNER",
                "redirectUri": "https://partner.musinsa.com",
            }, timeout=self.REQUEST_TIMEOUT)

            if resp.status_code != 200:
                logger.error(f"OTP 인증 실패: HTTP {resp.status_code} - {resp.text[:200]}")
                return False

            body = resp.json()
            auth_status = body.get("data", {}).get("authenticationStatus", "")

            if auth_status != "AUTHENTICATED":
                logger.error(f"OTP 인증 실패 - 상태: {auth_status}")
                return False

            # 쿠키 확인
            cookie_names = [c.name for c in self.session.cookies]
            logger.info(f"인증 쿠키 발급: {cookie_names}")

            if "pp-auth" not in cookie_names:
                logger.warning("pp-auth 쿠키 미발급 - 인증 불완전")

            # Step 3: bizest 리뷰 페이지 접속 → PHPSESSID 획득
            logger.info("Step 3: bizest 리뷰 페이지 접속...")
            resp = self.session.get(REVIEW_PAGE_URL, allow_redirects=True, timeout=self.REQUEST_TIMEOUT)

            if resp.status_code != 200:
                logger.error(f"리뷰 페이지 접속 실패: {resp.status_code}")
                return False

            if self._is_login_page(resp):
                logger.error("인증 완료했으나 bizest 로그인 페이지로 리다이렉트됨")
                return False

            logger.info("인증 성공 - 리뷰 수집 준비 완료")
            return True

        except Exception as e:
            logger.error(f"로그인 오류: {e}")
            return False

    def _fetch_api(self, page: int = 1, page_size: int = 500) -> Dict:
        """리뷰 API 호출 (form-encoded, 대문자 파라미터)"""
        resp = self.session.post(REVIEW_API_URL, data={
            "PAGE": str(page),
            "LIMIT": str(page_size),
            "PAGE_CNT": "10",
            "MENU_ID": "/po/csm/csm07",
        }, timeout=self.REQUEST_TIMEOUT)
        resp.raise_for_status()
        return resp.json()

    def _parse_reviews(self, api_data: List[Dict]) -> List[Dict[str, Any]]:
        """API 응답을 표준 리뷰 형식으로 변환"""
        reviews = []

        for item in api_data:
            # 이미지 URL 목록
            images = []
            for img in (item.get("images") or []):
                img_path = img.get("image", "")
                if img_path:
                    if img_path.startswith("http"):
                        images.append(img_path)
                    else:
                        images.append(f"{IMAGE_BASE_URL}{img_path}")

            # 별점: 문자열 "5" → float 5.0
            try:
                rating = float(item.get("goods_est", 5))
            except (ValueError, TypeError):
                rating = 5.0

            review = {
                "external_id": f"musinsa_{item.get('no', '')}",
                "rating": rating,
                "title": None,
                "content": item.get("goods_text", ""),
                "author": item.get("nic"),
                "purchased_option": item.get("style_no"),
                "product_code": str(item.get("goods_no", "")),
                "product_name": item.get("goods_nm"),
                "images": images,
                "reviewed_at": item.get("regi_date"),
                "platform": self.platform,
            }

            # 내용이 있는 리뷰만 수집
            if review["content"] and len(review["content"].strip()) > 0:
                reviews.append(self.normalize_review(review))

        return reviews

    def fetch_reviews(self, **kwargs) -> Dict[str, Any]:
        """리뷰 수집 실행"""
        result = {
            "success": False,
            "reviews": [],
            "total_count": 0,
            "average_rating": 0.0,
            "message": "",
        }

        try:
            self.start()

            if not self.login():
                result["message"] = "로그인 실패 - MUSINSA_ID/PASSWORD/OTP_SECRET 확인 필요"
                return result

            # 최근 N일 리뷰만 수집
            cutoff_str = (datetime.now() - timedelta(days=settings.SYNC_DAYS)).strftime("%Y-%m-%d")

            def _has_old(data):
                """페이지 데이터에 기간 외 리뷰가 있는지 확인"""
                return any(str(item.get("regi_date", "") or "")[:10] < cutoff_str for item in data)

            # 첫 호출로 전체 리뷰 수 확인 (최대 1000개씩)
            api_resp = self._fetch_api(page=1, page_size=1000)
            total = api_resp.get("total", 0)
            all_data = api_resp.get("data", [])
            stop_pagination = _has_old(all_data)

            logger.info(f"전체 리뷰 수: {total}, 1페이지: {len(all_data)}개")

            # 추가 페이지 요청 (기간 외 리뷰 발견 시 중단)
            page = 2
            while len(all_data) < total and not stop_pagination:
                api_resp = self._fetch_api(page=page, page_size=1000)
                page_data = api_resp.get("data", [])
                if not page_data:
                    break
                all_data.extend(page_data)
                stop_pagination = _has_old(page_data)
                logger.info(f"  페이지 {page}: +{len(page_data)}개 (누적 {len(all_data)}/{total})")
                page += 1

            if stop_pagination:
                logger.info(f"{settings.SYNC_DAYS}일 이전 리뷰 감지 → 페이지네이션 중단")

            # 최근 N일 리뷰만 필터링
            before_count = len(all_data)
            all_data = [
                item for item in all_data
                if str(item.get("regi_date", "") or "")[:10] >= cutoff_str
            ]
            logger.info(f"최근 {settings.SYNC_DAYS}일 필터: {before_count}개 → {len(all_data)}개")

            reviews = self._parse_reviews(all_data)

            result["reviews"] = reviews
            result["total_count"] = len(reviews)
            result["success"] = True
            result["message"] = "수집 완료"

            if reviews:
                total_rating = sum(r.get("rating", 5.0) for r in reviews)
                result["average_rating"] = round(total_rating / len(reviews), 2)

            logger.info(f"수집 완료: {len(reviews)}개 리뷰, 평균 평점 {result['average_rating']}")

        except requests.exceptions.HTTPError as e:
            if e.response is not None and e.response.status_code in (401, 403):
                result["message"] = "인증 만료 - 재로그인 필요"
            else:
                result["message"] = f"API 오류: {e}"
            logger.error(f"API 오류: {e}")

        except Exception as e:
            result["message"] = str(e)
            logger.error(f"수집 오류: {e}")

        finally:
            self.stop()

        return result