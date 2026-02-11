#!/usr/bin/env python3
"""
네이버 스마트스토어 쿠키 저장 스크립트 (Windows 로컬용)
브라우저가 열리면 수동으로 로그인하세요.
로그인이 감지되면 자동으로 쿠키를 저장합니다.
"""

import json
import os
import time
import undetected_chromedriver as uc

COOKIE_FILE = os.path.join(os.path.dirname(__file__), "data", "naver_cookies.json")
NAVER_LOGIN_URL = "https://accounts.commerce.naver.com/login"
SMARTSTORE_HOME_URL = "https://sell.smartstore.naver.com/"
TIMEOUT = 180  # 3분 대기


def main():
    print("=== 네이버 스마트스토어 쿠키 저장 (Windows) ===\n")

    options = uc.ChromeOptions()
    options.add_argument("--window-size=1920,1080")
    options.add_argument("--lang=ko-KR")

    driver = uc.Chrome(
        options=options,
        headless=False,
        use_subprocess=True,
        version_main=144,
    )

    try:
        print(f"로그인 페이지 접속: {NAVER_LOGIN_URL}")
        driver.get(NAVER_LOGIN_URL)

        print("\n브라우저에서 수동으로 로그인하세요. (3분 대기)")
        print("로그인 완료가 감지되면 자동으로 쿠키를 저장합니다...\n")

        start = time.time()
        saved = False

        while time.time() - start < TIMEOUT:
            try:
                current_url = driver.current_url
                # 로그인 성공하면 login 페이지에서 벗어남
                if "login" not in current_url and "accounts.commerce" not in current_url:
                    print(f"로그인 감지! URL: {current_url}")

                    # 여러 도메인을 방문하여 모든 쿠키 수집
                    all_cookies = {}

                    # 1) nid.naver.com - 핵심 인증 쿠키 (NID_AUT, NID_SES)
                    print("nid.naver.com 쿠키 수집...")
                    driver.get("https://nid.naver.com/nidlogin.login")
                    time.sleep(2)
                    for c in driver.get_cookies():
                        all_cookies[c['name']] = c

                    # 2) 스마트스토어 센터
                    print("스마트스토어 센터 쿠키 수집...")
                    driver.get(SMARTSTORE_HOME_URL)
                    time.sleep(3)
                    for c in driver.get_cookies():
                        all_cookies[c['name']] = c

                    # 3) 리뷰 페이지
                    print("리뷰 페이지 쿠키 수집...")
                    driver.get("https://sell.smartstore.naver.com/#/review/search")
                    time.sleep(3)
                    for c in driver.get_cookies():
                        all_cookies[c['name']] = c

                    cookies = list(all_cookies.values())
                    os.makedirs(os.path.dirname(COOKIE_FILE), exist_ok=True)
                    with open(COOKIE_FILE, 'w', encoding='utf-8') as f:
                        json.dump(cookies, f, indent=2, ensure_ascii=False)

                    print(f"\n쿠키 저장 완료: {COOKIE_FILE}")
                    print(f"저장된 쿠키 수: {len(cookies)}")

                    # 주요 쿠키 확인
                    important = [c['name'] for c in cookies if c['name'] in ['NID_AUT', 'NID_SES', 'NID_JKL']]
                    print(f"인증 쿠키: {important}")
                    if not important:
                        print("⚠ NID_AUT/NID_SES 미발견 - 인증 쿠키가 부족할 수 있습니다")
                    saved = True
                    break
            except Exception:
                pass

            time.sleep(2)
            elapsed = int(time.time() - start)
            print(f"\r대기중... {elapsed}s / {TIMEOUT}s", end="", flush=True)

        if not saved:
            print("\n\n타임아웃: 로그인이 감지되지 않았습니다.")

    finally:
        driver.quit()
        print("\n브라우저 종료")


if __name__ == "__main__":
    main()
