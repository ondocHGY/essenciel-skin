#!/usr/bin/env python3
"""리뷰 페이지 디버깅"""

import os
import sys
import time
import json

sys.path.insert(0, '/var/www/essenciel_qrcode/review-collector')
os.environ['QSM_ID'] = 'medibiogen'
os.environ['QSM_PASSWORD'] = '78yuhjbn!!'
os.environ['CHROME_HEADLESS'] = 'true'
os.environ['DOWNLOAD_PATH'] = '/tmp/qoo10_downloads'

from app.scraper import Qoo10Scraper

def main():
    print("=== 리뷰 페이지 디버깅 ===\n")

    scraper = Qoo10Scraper()
    scraper.start()

    try:
        # 쿠키 로그인
        if not scraper.login():
            print("로그인 실패")
            return

        print("로그인 성공!\n")

        # 리뷰 페이지 이동
        if not scraper.navigate_to_reviews():
            print("페이지 이동 실패")
            return

        print("리뷰 페이지 이동 성공!\n")

        # 페이지 분석
        time.sleep(3)

        # 스크린샷
        scraper.driver.save_screenshot("/tmp/review_page.png")
        print("스크린샷: /tmp/review_page.png")

        # HTML 저장
        with open("/tmp/review_page.html", 'w', encoding='utf-8') as f:
            f.write(scraper.driver.page_source)
        print("HTML: /tmp/review_page.html")

        # 버튼/입력 요소 찾기
        from selenium.webdriver.common.by import By

        print("\n=== 버튼 요소 ===")
        buttons = scraper.driver.find_elements(By.TAG_NAME, "button")
        for btn in buttons[:10]:
            text = btn.text.strip()
            cls = btn.get_attribute("class")
            onclick = btn.get_attribute("onclick")
            print(f"  - text='{text}', class='{cls}', onclick='{onclick}'")

        print("\n=== input[type=button] ===")
        inputs = scraper.driver.find_elements(By.CSS_SELECTOR, "input[type='button'], input[type='submit']")
        for inp in inputs[:10]:
            val = inp.get_attribute("value")
            cls = inp.get_attribute("class")
            onclick = inp.get_attribute("onclick")
            print(f"  - value='{val}', class='{cls}', onclick='{onclick}'")

        print("\n=== a 태그 (검색 관련) ===")
        links = scraper.driver.find_elements(By.TAG_NAME, "a")
        for link in links:
            text = link.text.strip()
            onclick = link.get_attribute("onclick") or ""
            if "検索" in text or "search" in onclick.lower() or "Search" in onclick:
                href = link.get_attribute("href")
                print(f"  - text='{text}', onclick='{onclick[:80]}', href='{href}'")

        print("\n=== Excel 다운로드 버튼 ===")
        for elem in scraper.driver.find_elements(By.XPATH, "//*[contains(text(), 'Excel') or contains(@onclick, 'Excel') or contains(@class, 'excel')]"):
            tag = elem.tag_name
            text = elem.text.strip()
            onclick = elem.get_attribute("onclick")
            print(f"  - tag={tag}, text='{text}', onclick='{onclick}'")

    finally:
        scraper.stop()
        print("\n브라우저 종료")


if __name__ == "__main__":
    main()
