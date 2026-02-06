#!/usr/bin/env python3
"""Qoo10 스크래퍼 테스트"""

import os
import sys

# 프로젝트 루트를 path에 추가
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

# 환경변수 설정 (테스트용)
os.environ.setdefault('QSM_ID', 'your_qsm_id')
os.environ.setdefault('QSM_PASSWORD', 'your_password')
os.environ.setdefault('CHROME_HEADLESS', 'true')
os.environ.setdefault('CHROME_VERSION', '131')
os.environ.setdefault('DOWNLOAD_PATH', '/tmp/qoo10_downloads')
os.environ.setdefault('COOKIE_PATH', './data/qsm_cookies.json')

from app.scrapers import get_scraper

def main():
    print("=== Qoo10 스크래퍼 테스트 ===\n")

    scraper = get_scraper('qoo10')

    print("리뷰 수집 시작...")
    result = scraper.fetch_reviews()

    print(f"\n=== 결과 ===")
    print(f"성공: {result['success']}")
    print(f"메시지: {result['message']}")
    print(f"총 리뷰 수: {result['total_count']}")
    print(f"평균 평점: {result['average_rating']}")

    if result.get('file_path'):
        print(f"다운로드 파일: {result['file_path']}")

    reviews = result.get('reviews', [])
    if reviews:
        print(f"\n=== 리뷰 샘플 (최대 5개) ===")
        for i, r in enumerate(reviews[:5]):
            print(f"\n[{i+1}]")
            print(f"  평점: {r.get('rating', 'N/A')}")
            print(f"  상품코드: {r.get('product_code', 'N/A')}")
            print(f"  작성자: {r.get('author', 'N/A')}")
            print(f"  작성일: {r.get('reviewed_at', 'N/A')}")
            content = r.get('content', '')[:100]
            print(f"  내용: {content}...")

    print("\n=== 테스트 완료 ===")


if __name__ == "__main__":
    main()
