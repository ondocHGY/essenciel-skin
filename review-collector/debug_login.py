#!/usr/bin/env python3
"""로그인 페이지 디버깅"""

import os
import time
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from webdriver_manager.chrome import ChromeDriverManager

QSM_ID = 'medibiogen'
QSM_PASSWORD = '78yuhjbn!!'

def main():
    print("=== QSM 로그인 디버깅 ===\n")

    options = Options()
    options.add_argument("--headless=new")
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--disable-gpu")
    options.add_argument("--window-size=1920,1080")
    options.add_argument("--lang=ja-JP")

    service = Service(ChromeDriverManager().install())
    driver = webdriver.Chrome(service=service, options=options)

    try:
        print("1. 로그인 페이지 접속...")
        driver.get("https://qsm.qoo10.jp/GMKT.INC.Gsm.Web/Login.aspx")
        time.sleep(3)

        print(f"   현재 URL: {driver.current_url}")

        # 스크린샷 저장
        screenshot_path = "/tmp/qoo10_login_debug.png"
        driver.save_screenshot(screenshot_path)
        print(f"   스크린샷: {screenshot_path}")

        # HTML 저장
        html_path = "/tmp/qoo10_login_debug.html"
        with open(html_path, 'w', encoding='utf-8') as f:
            f.write(driver.page_source)
        print(f"   HTML: {html_path}")

        # 폼 요소 찾기
        print("\n2. 폼 요소 탐색...")

        # input 요소들 출력
        inputs = driver.find_elements(By.TAG_NAME, "input")
        print(f"   input 요소 수: {len(inputs)}")
        for inp in inputs[:10]:
            inp_type = inp.get_attribute("type")
            inp_id = inp.get_attribute("id")
            inp_name = inp.get_attribute("name")
            print(f"   - type={inp_type}, id={inp_id}, name={inp_name}")

        # ID 필드 찾기
        print("\n3. 로그인 필드 찾기...")
        id_field = None
        pw_field = None

        for inp in inputs:
            inp_type = inp.get_attribute("type")
            inp_name = inp.get_attribute("name") or ""
            inp_id = inp.get_attribute("id") or ""

            if inp_type == "text" and ("login" in inp_name.lower() or "id" in inp_name.lower()):
                id_field = inp
                print(f"   ID 필드 발견: id={inp_id}, name={inp_name}")

            if inp_type == "password":
                pw_field = inp
                print(f"   PW 필드 발견: id={inp_id}, name={inp_name}")

        if id_field and pw_field:
            print("\n4. 로그인 시도...")
            id_field.clear()
            id_field.send_keys(QSM_ID)
            pw_field.clear()
            pw_field.send_keys(QSM_PASSWORD)
            print("   ID/PW 입력 완료")

            # 로그인 버튼 찾기
            buttons = driver.find_elements(By.CSS_SELECTOR, "input[type='image'], input[type='submit'], button[type='submit']")
            print(f"   버튼 수: {len(buttons)}")
            for btn in buttons:
                print(f"   - {btn.get_attribute('outerHTML')[:100]}")

            if buttons:
                buttons[0].click()
                print("   로그인 버튼 클릭")
                time.sleep(5)

                print(f"\n5. 로그인 후 URL: {driver.current_url}")

                # 로그인 후 스크린샷
                driver.save_screenshot("/tmp/qoo10_after_login.png")
                print("   스크린샷: /tmp/qoo10_after_login.png")

                if "Login" not in driver.current_url:
                    print("\n   ✓ 로그인 성공!")

                    # 리뷰 페이지로 이동
                    print("\n6. 리뷰 페이지 이동...")
                    driver.get("https://qsm.qoo10.jp/GMKT.INC.Gsm.Web/Seller/MyReviewMgt.aspx")
                    time.sleep(3)
                    print(f"   URL: {driver.current_url}")
                    driver.save_screenshot("/tmp/qoo10_review_page.png")
                    print("   스크린샷: /tmp/qoo10_review_page.png")
                else:
                    print("\n   ✗ 로그인 실패 - 여전히 로그인 페이지")
        else:
            print("\n   ✗ 로그인 필드를 찾을 수 없음")

    except Exception as e:
        print(f"\n오류: {e}")
        import traceback
        traceback.print_exc()

    finally:
        driver.quit()
        print("\n브라우저 종료")


if __name__ == "__main__":
    main()
