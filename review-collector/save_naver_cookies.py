#!/usr/bin/env python3
"""네이버 쿠키 저장 스크립트
수동 로그인 후 쿠키를 저장합니다.
"""

import os
import sys
import json
import time

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

os.environ.setdefault('CHROME_HEADLESS', 'false')
os.environ.setdefault('CHROME_VERSION', '144')

import undetected_chromedriver as uc

NAVER_LOGIN_URL = "https://accounts.commerce.naver.com/login"
SMARTSTORE_HOME_URL = "https://sell.smartstore.naver.com/"
COOKIE_PATH = "./data/naver_cookies.json"

def main():
    print("=== 네이버 쿠키 저장 스크립트 ===\n")

    os.makedirs("./data", exist_ok=True)

    options = uc.ChromeOptions()
    options.add_argument("--window-size=1920,1080")
    options.add_argument("--lang=ko-KR")

    print("Chrome 브라우저 시작...")
    driver = uc.Chrome(
        options=options,
        headless=False,
        use_subprocess=True,
        version_main=144
    )

    try:
        print(f"\n1. 로그인 페이지로 이동: {NAVER_LOGIN_URL}")
        driver.get(NAVER_LOGIN_URL)

        print("\n" + "="*50)
        print("브라우저에서 수동으로 로그인하세요!")
        print("로그인 완료 후 60초 대기합니다...")
        print("="*50 + "\n")

        # 60초 대기 (수동 로그인 시간)
        for i in range(60, 0, -5):
            print(f"  남은 시간: {i}초")
            time.sleep(5)

        # 스마트스토어 홈으로 이동해서 쿠키 확보
        print("2. 스마트스토어 센터로 이동...")
        driver.get(SMARTSTORE_HOME_URL)
        time.sleep(3)

        # 쿠키 저장
        cookies = driver.get_cookies()

        with open(COOKIE_PATH, 'w', encoding='utf-8') as f:
            json.dump(cookies, f, indent=2, ensure_ascii=False)

        print(f"\n✓ 쿠키 저장 완료: {COOKIE_PATH}")
        print(f"  저장된 쿠키 수: {len(cookies)}개")

        # 주요 쿠키 확인
        important_cookies = ['NID_AUT', 'NID_SES', 'NID_JKL']
        found = [c['name'] for c in cookies if c['name'] in important_cookies]
        print(f"  인증 쿠키: {found}")

    except Exception as e:
        print(f"\n오류: {e}")

    finally:
        print("\n브라우저 종료...")
        driver.quit()

    print("\n=== 완료 ===")


if __name__ == "__main__":
    main()
