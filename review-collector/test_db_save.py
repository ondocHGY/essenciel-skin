#!/usr/bin/env python3
"""DB 저장 테스트"""

import os
import sys

sys.path.insert(0, '/var/www/essenciel_qrcode/review-collector')
os.environ['QSM_ID'] = 'medibiogen'
os.environ['QSM_PASSWORD'] = '78yuhjbn!!'
os.environ['CHROME_HEADLESS'] = 'true'
os.environ['DOWNLOAD_PATH'] = '/tmp/qoo10_downloads'
os.environ['DB_HOST'] = '172.17.0.1'
os.environ['DB_PORT'] = '3306'
os.environ['DB_NAME'] = 'qrcode'
os.environ['DB_USER'] = 'test'
os.environ['DB_PASSWORD'] = 'test'

from datetime import datetime
import hashlib
from app.scraper import Qoo10Scraper, parse_excel
from app.database import SessionLocal
from app.models import ProductReview

def main():
    print("=== DB 저장 테스트 ===\n")

    # 1. 리뷰 수집
    print("1. 리뷰 수집...")
    scraper = Qoo10Scraper()
    result = scraper.fetch_reviews()

    if not result["success"]:
        print(f"   ✗ 수집 실패: {result['message']}")
        return

    print(f"   ✓ 수집 성공")

    # 2. 파싱
    reviews = []
    if result.get("excel_path"):
        reviews = parse_excel(result["excel_path"])
    else:
        reviews = result.get("reviews", [])

    print(f"   ✓ {len(reviews)}개 리뷰 파싱\n")

    if not reviews:
        print("   리뷰가 없습니다.")
        return

    # 3. DB 저장
    print("2. DB 저장...")
    db = SessionLocal()
    added = 0
    updated = 0
    product_id = 10  # 실제 존재하는 product_id

    try:
        for review_data in reviews:
            # external_id 생성 (CSV에서 가져온 ID 사용 또는 생성)
            if review_data.get("external_id"):
                external_id = review_data["external_id"]
            else:
                external_id = f"qoo10_{hashlib.md5(review_data['content'].encode()).hexdigest()[:16]}"

            # 기존 리뷰 확인
            existing = db.query(ProductReview).filter(
                ProductReview.platform == "qoo10",
                ProductReview.external_id == external_id
            ).first()

            # 날짜 파싱
            reviewed_at = None
            if review_data.get("reviewed_at"):
                try:
                    date_str = review_data["reviewed_at"].strip()
                    for fmt in ["%Y-%m-%d", "%Y/%m/%d"]:
                        try:
                            reviewed_at = datetime.strptime(date_str, fmt)
                            break
                        except:
                            pass
                except:
                    pass

            if existing:
                existing.rating = review_data.get("rating", 5.0)
                existing.content = review_data["content"]
                existing.updated_at = datetime.now()
                updated += 1
            else:
                review = ProductReview(
                    product_id=product_id,
                    platform="qoo10",
                    external_id=external_id,
                    rating=review_data.get("rating", 5.0),
                    content=review_data["content"],
                    author=review_data.get("author"),
                    reviewed_at=reviewed_at,
                    is_visible=True
                )
                db.add(review)
                added += 1

        db.commit()
        print(f"   ✓ {added}개 추가, {updated}개 업데이트\n")

        # 4. 저장 확인
        print("3. 저장 확인...")
        total = db.query(ProductReview).filter(
            ProductReview.product_id == product_id,
            ProductReview.platform == "qoo10"
        ).count()

        print(f"   ✓ 총 {total}개 리뷰 저장됨\n")

        # 샘플 출력
        samples = db.query(ProductReview).filter(
            ProductReview.product_id == product_id,
            ProductReview.platform == "qoo10"
        ).order_by(ProductReview.reviewed_at.desc()).limit(3).all()

        print("=== 저장된 리뷰 샘플 ===")
        for i, r in enumerate(samples):
            print(f"\n[{i+1}] ID: {r.id}")
            print(f"    평점: {r.rating}")
            print(f"    내용: {r.content[:60]}...")
            print(f"    작성자: {r.author}")
            print(f"    날짜: {r.reviewed_at}")

    except Exception as e:
        print(f"   ✗ DB 오류: {e}")
        import traceback
        traceback.print_exc()
        db.rollback()

    finally:
        db.close()

    print("\n=== 테스트 완료 ===")


if __name__ == "__main__":
    main()
