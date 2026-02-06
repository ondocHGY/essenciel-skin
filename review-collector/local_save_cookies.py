#!/usr/bin/env python3
"""
로컬 PC에서 QSM 쿠키 저장
Windows/Mac에서 실행 후 생성된 qsm_cookies.json을 서버에 업로드하세요.

사용법:
1. pip install selenium webdriver-manager
2. python local_save_cookies.py
3. 브라우저에서 QSM에 로그인
4. 로그인 완료 후 터미널에서 Enter
5. 생성된 qsm_cookies.json을 서버의 review-collector 폴더에 복사
"""

import json
import time
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from webdriver_manager.chrome import ChromeDriverManager

COOKIE_FILE = "qsm_cookies.json"
QSM_LOGIN_URL = "https://qsm.qoo10.jp/GMKT.INC.Gsm.Web/Login.aspx"

def main():
    print("=== QSM 쿠키 저장 (로컬용) ===\n")

    options = Options()
    options.add_argument("--lang=ja-JP")
    options.add_argument("--window-size=1400,900")

    service = Service(ChromeDriverManager().install())
    driver = webdriver.Chrome(service=service, options=options)

    try:
        print(f"로그인 페이지 열기: {QSM_LOGIN_URL}")
        driver.get(QSM_LOGIN_URL)

        print("\n" + "=" * 50)
        print("브라우저에서 QSM에 로그인하세요.")
        print("reCAPTCHA를 포함한 모든 인증을 완료한 후")
        print("이 터미널에서 Enter를 눌러주세요.")
        print("=" * 50 + "\n")

        input("로그인 완료 후 Enter >>> ")

        # 현재 URL 확인
        current_url = driver.current_url
        print(f"\n현재 URL: {current_url}")

        if "Login.aspx" not in current_url:
            # 쿠키 저장
            cookies = driver.get_cookies()
            with open(COOKIE_FILE, 'w', encoding='utf-8') as f:
                json.dump(cookies, f, indent=2, ensure_ascii=False)

            print(f"\n✓ 쿠키 저장 완료!")
            print(f"  파일: {COOKIE_FILE}")
            print(f"  쿠키 수: {len(cookies)}개")
            print(f"\n이 파일을 서버의 review-collector 폴더에 복사하세요.")
        else:
            print("\n✗ 로그인이 완료되지 않았습니다.")
            print("  브라우저에서 로그인 후 다시 시도하세요.")

    except Exception as e:
        print(f"\n오류: {e}")

    finally:
        print("\n브라우저를 닫습니다...")
        time.sleep(2)
        driver.quit()


if __name__ == "__main__":
    main()
