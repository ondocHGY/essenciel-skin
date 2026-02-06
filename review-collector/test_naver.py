#!/usr/bin/env python3
"""네이버 스크래퍼 테스트"""

import os
import sys
import logging
import platform

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

# 로깅 설정
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.StreamHandler(),
        logging.FileHandler('test_naver.log', encoding='utf-8'),
    ]
)

# 환경변수 설정
os.environ['NAVER_ID'] = 'medibiogen@naver.com'
os.environ['NAVER_PASSWORD'] = '7856pass!!'

# 플랫폼에 따라 경로 설정
if platform.system() == 'Windows':
    os.environ['CHROME_HEADLESS'] = 'false'  # Windows: 브라우저 보이게
    os.environ['DOWNLOAD_PATH'] = 'C:/temp/naver_downloads'
    os.makedirs('C:/temp/naver_downloads', exist_ok=True)
else:
    os.environ['CHROME_HEADLESS'] = 'true'  # Linux/Docker: headless
    os.environ['DOWNLOAD_PATH'] = '/tmp/naver_downloads'
    os.makedirs('/tmp/naver_downloads', exist_ok=True)

os.environ['NAVER_COOKIE_PATH'] = './data/naver_cookies.json'
os.makedirs('./data', exist_ok=True)

from app.scrapers import get_scraper

def main():
    print("=== Naver SmartStore Test ===\n", flush=True)

    scraper = get_scraper('naver')

    print("Fetching reviews...", flush=True)
    result = scraper.fetch_reviews()

    print(f"\n=== 결과 ===")
    print(f"성공: {result['success']}")
    print(f"메시지: {result['message']}")
    print(f"총 리뷰 수: {result['total_count']}")
    print(f"평균 평점: {result['average_rating']}")

    reviews = result.get('reviews', [])
    if reviews:
        print(f"\n=== 리뷰 샘플 (최대 3개) ===")
        for i, r in enumerate(reviews[:3]):
            print(f"\n[{i+1}]")
            print(f"  평점: {r.get('rating')}")
            print(f"  상품코드: {r.get('product_code')}")
            print(f"  작성자: {r.get('author')}")

    print("\n=== 테스트 완료 ===")


if __name__ == "__main__":
    main()
