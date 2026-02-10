#!/usr/bin/env python3
"""
QSM 쿠키 저장 스크립트 (Windows 로컬용)
브라우저가 열리면 수동으로 로그인하세요.
로그인이 감지되면 자동으로 쿠키를 저장합니다.
"""

import json
import os
import time
import undetected_chromedriver as uc

COOKIE_FILE = os.path.join(os.path.dirname(__file__), "data", "qsm_cookies.json")
QSM_LOGIN_URL = "https://qsm.qoo10.jp/GMKT.INC.Gsm.Web/Login.aspx"
TIMEOUT = 180  # 3분 대기


def main():
    print("=== QSM 쿠키 저장 (Windows) ===\n")

    options = uc.ChromeOptions()
    options.add_argument("--window-size=1920,1080")
    options.add_argument("--lang=ja-JP")

    driver = uc.Chrome(
        options=options,
        headless=False,
        use_subprocess=True,
        version_main=144,
    )

    try:
        print(f"로그인 페이지 접속: {QSM_LOGIN_URL}")
        driver.get(QSM_LOGIN_URL)

        print("\n브라우저에서 수동으로 로그인하세요. (3분 대기)")
        print("로그인 완료가 감지되면 자동으로 쿠키를 저장합니다...\n")

        start = time.time()
        saved = False

        while time.time() - start < TIMEOUT:
            try:
                current_url = driver.current_url
                if "Login.aspx" not in current_url:
                    print(f"로그인 감지! URL: {current_url}")
                    time.sleep(3)  # 페이지 로딩 대기

                    os.makedirs(os.path.dirname(COOKIE_FILE), exist_ok=True)
                    cookies = driver.get_cookies()
                    with open(COOKIE_FILE, 'w') as f:
                        json.dump(cookies, f, indent=2)
                    print(f"\n쿠키 저장 완료: {COOKIE_FILE}")
                    print(f"저장된 쿠키 수: {len(cookies)}")
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
