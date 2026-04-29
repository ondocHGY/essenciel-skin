#!/usr/bin/env python3
"""
Shopee 셀러센터 쿠키 저장 스크립트 (Windows 로컬용)
브라우저가 열리면 수동으로 로그인하세요.
로그인이 감지되면 자동으로 쿠키를 저장합니다.
"""

import json
import os
import time
import undetected_chromedriver as uc

COOKIE_FILE = os.path.join(os.path.dirname(__file__), "data", "shopee_cookies.json")
LOGIN_URL = "https://seller.shopee.kr/account/signin"
SELLER_HOME_URL = "https://seller.shopee.kr/"
TIMEOUT = 300  # 5분 대기


def main():
    print("=== Shopee 셀러센터 쿠키 저장 (Windows) ===\n")
    print("브라우저에서 로그인을 완료하세요.")
    print(f"대기 시간: {TIMEOUT}초\n")

    options = uc.ChromeOptions()
    options.add_argument("--window-size=1920,1080")
    options.add_argument("--lang=ko-KR")

    driver = uc.Chrome(
        options=options,
        headless=False,
        use_subprocess=True,
        version_main=146,
    )

    try:
        print(f"로그인 페이지 접속: {LOGIN_URL}")
        driver.get(LOGIN_URL)

        print("\n브라우저에서 로그인을 완료하세요.")
        print("로그인 완료가 감지되면 자동으로 쿠키를 저장합니다...\n")

        start = time.time()
        saved = False

        while time.time() - start < TIMEOUT:
            try:
                current_url = driver.current_url
                # 로그인 성공하면 signin 페이지에서 벗어남
                if "seller.shopee.kr" in current_url and "/signin" not in current_url:
                    print(f"\n로그인 감지! URL: {current_url}")
                    time.sleep(5)

                    # 셀러센터 홈에서 쿠키 확보
                    driver.get(SELLER_HOME_URL)
                    time.sleep(5)

                    os.makedirs(os.path.dirname(COOKIE_FILE), exist_ok=True)
                    cookies = driver.get_cookies()
                    with open(COOKIE_FILE, 'w', encoding='utf-8') as f:
                        json.dump(cookies, f, indent=2, ensure_ascii=False)

                    print(f"쿠키 저장 완료: {COOKIE_FILE}")
                    print(f"저장된 쿠키 수: {len(cookies)}")

                    domains = set(c.get('domain', '') for c in cookies)
                    print(f"도메인: {domains}")
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
