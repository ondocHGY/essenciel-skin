#!/usr/bin/env python3
"""QSM 스크래퍼 테스트"""

import os
import sys

# 환경변수 설정
os.environ['QSM_ID'] = 'medibiogen'
os.environ['QSM_PASSWORD'] = '78yuhjbn!!'
os.environ['CHROME_HEADLESS'] = 'true'
os.environ['DOWNLOAD_PATH'] = '/tmp/qoo10_downloads'

from app.scraper import Qoo10Scraper, parse_excel

def main():
    print("=== Qoo10 QSM 스크래퍼 테스트 ===\n")

    scraper = Qoo10Scraper()

    print("1. 브라우저 시작...")
    scraper.start()

    try:
        print("2. QSM 로그인...")
        if not scraper.login():
            print("   ✗ 로그인 실패")
            return
        print("   ✓ 로그인 성공\n")

        print("3. 리뷰 관리 페이지 이동...")
        if not scraper.navigate_to_reviews():
            print("   ✗ 페이지 이동 실패")
            return
        print("   ✓ 페이지 이동 성공\n")

        print("4. 검색 실행...")
        if not scraper.search_reviews():
            print("   ✗ 검색 실패")
            return
        print("   ✓ 검색 성공\n")

        print("5. Excel 다운로드 시도...")
        excel_path = scraper.download_excel()

        if excel_path:
            print(f"   ✓ Excel 다운로드 완료: {excel_path}\n")

            print("6. Excel 파싱...")
            reviews = parse_excel(excel_path)
            print(f"   ✓ {len(reviews)}개 리뷰 파싱 완료\n")

            if reviews:
                print("=== 리뷰 샘플 (최대 3개) ===")
                for i, r in enumerate(reviews[:3]):
                    print(f"\n[{i+1}] 평점: {r.get('rating', 'N/A')}")
                    print(f"    내용: {r.get('content', '')[:80]}...")
                    if r.get('author'):
                        print(f"    작성자: {r['author']}")
        else:
            print("   ✗ Excel 다운로드 실패\n")

            print("5-1. 페이지에서 직접 추출 시도...")
            reviews = scraper.scrape_from_page()
            print(f"   ✓ {len(reviews)}개 리뷰 추출\n")

            if reviews:
                print("=== 리뷰 샘플 (최대 3개) ===")
                for i, r in enumerate(reviews[:3]):
                    print(f"\n[{i+1}] 평점: {r.get('rating', 'N/A')}")
                    print(f"    내용: {r.get('content', '')[:80]}...")

        print("\n=== 테스트 완료 ===")

    except Exception as e:
        print(f"\n오류: {e}")
        import traceback
        traceback.print_exc()

    finally:
        print("\n브라우저 종료...")
        scraper.stop()


if __name__ == "__main__":
    main()
