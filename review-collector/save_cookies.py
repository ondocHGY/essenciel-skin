#!/usr/bin/env python3
"""
QSM 쿠키 저장 스크립트
브라우저가 열리면 수동으로 로그인하세요.
로그인 완료 후 Enter를 눌러 쿠키를 저장합니다.
"""

import json
import os
import undetected_chromedriver as uc
from pyvirtualdisplay import Display

COOKIE_FILE = "qsm_cookies.json"
QSM_LOGIN_URL = "https://qsm.qoo10.jp/GMKT.INC.Gsm.Web/Login.aspx"

def main():
    print("=== QSM 쿠키 저장 ===\n")

    # 가상 디스플레이 시작
    display = Display(visible=0, size=(1920, 1080))
    display.start()

    options = uc.ChromeOptions()
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--window-size=1920,1080")
    options.add_argument("--lang=ja-JP")

    driver = uc.Chrome(
        options=options,
        headless=False,
        use_subprocess=True,
        version_main=144
    )

    try:
        print(f"로그인 페이지 접속: {QSM_LOGIN_URL}")
        driver.get(QSM_LOGIN_URL)

        print("\n수동으로 로그인한 후 Enter를 눌러주세요...")
        print("(reCAPTCHA 포함 로그인 완료 후)")
        input()

        # 현재 URL 확인
        print(f"\n현재 URL: {driver.current_url}")

        if "Login.aspx" not in driver.current_url:
            # 쿠키 저장
            cookies = driver.get_cookies()
            with open(COOKIE_FILE, 'w') as f:
                json.dump(cookies, f, indent=2)
            print(f"\n✓ 쿠키 저장 완료: {COOKIE_FILE}")
            print(f"  저장된 쿠키 수: {len(cookies)}")
        else:
            print("\n✗ 로그인이 완료되지 않았습니다.")

    finally:
        driver.quit()
        display.stop()
        print("\n브라우저 종료")


if __name__ == "__main__":
    main()
