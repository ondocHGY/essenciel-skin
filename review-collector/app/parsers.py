"""엑셀 업로드 파서 (W컨셉, 쿠팡 등 수동 업로드 플랫폼)"""

import hashlib
import logging
from datetime import datetime
from typing import List, Dict, Any, Optional

import pandas as pd

logger = logging.getLogger(__name__)

# W컨셉 상품명 키워드 → product_id 매핑
WCONCEPT_PRODUCT_MAP = {
    '브라이트': 9,
    '하이드라': 10,
    '부스팅': 11,
    '수더': 12,
}

# 쿠팡 상품명 키워드 → product_id 매핑 (추후 추가)
COUPANG_PRODUCT_MAP = {}


def _match_product_id(product_name: str, product_map: dict) -> Optional[int]:
    """상품명에서 키워드를 찾아 product_id 반환"""
    if not product_name:
        return None
    for keyword, product_id in product_map.items():
        if keyword in product_name:
            return product_id
    return None


def parse_wconcept_excel(file_path: str) -> List[Dict[str, Any]]:
    """W컨셉 상품평 엑셀 파싱

    엑셀 구조:
    - 1행: 메타데이터 (기간, 건수)
    - 2행: 컬럼 헤더 (작성일, 유형, 작성자, 주문번호, 주문상태, 전시여부, 동영상리뷰, 카테고리, 브랜드, 상품명, 제목, 등급, 평점, 상품공급사)
    - 3행~: 데이터
    """
    reviews = []

    try:
        if file_path.endswith('.xls'):
            df = pd.read_excel(file_path, header=1, engine='xlrd')
        else:
            df = pd.read_excel(file_path, header=1, engine='openpyxl')

        df.columns = df.columns.str.strip()
        logger.info(f"[wconcept] 엑셀 컬럼: {list(df.columns)}, {len(df)}행")

        for idx, row in df.iterrows():
            product_name = str(row.get('상품명', '')).strip() if pd.notna(row.get('상품명')) else ''
            product_id = _match_product_id(product_name, WCONCEPT_PRODUCT_MAP)

            rating = 5.0
            if pd.notna(row.get('평점')):
                try:
                    rating = float(row['평점'])
                except (ValueError, TypeError):
                    pass

            reviewed_at = None
            if pd.notna(row.get('작성일')):
                reviewed_at = str(row['작성일']).strip()

            author = str(row.get('작성자', '')).strip() if pd.notna(row.get('작성자')) else None
            title = str(row.get('제목', '')).strip() if pd.notna(row.get('제목')) else ''

            # 주문번호를 external_id로 사용
            order_no = str(row.get('주문번호', '')).strip() if pd.notna(row.get('주문번호')) else ''
            if order_no:
                external_id = f"wconcept_{order_no}"
            else:
                content_hash = hashlib.md5(f"{title}{author}{reviewed_at}".encode()).hexdigest()[:12]
                external_id = f"wconcept_{content_hash}"

            reviews.append({
                "external_id": external_id,
                "rating": rating,
                "title": title,
                "content": title,  # W컨셉은 본문 없이 제목만 있음
                "author": author,
                "product_name": product_name,
                "product_code": None,
                "reviewed_at": reviewed_at,
                "platform": "wconcept",
                "product_id": product_id,
            })

        matched = sum(1 for r in reviews if r.get('product_id'))
        logger.info(f"[wconcept] 파싱 완료: {len(reviews)}개 (매칭: {matched}개, 미매칭: {len(reviews) - matched}개)")

    except Exception as e:
        logger.error(f"[wconcept] 파싱 오류: {e}")
        import traceback
        traceback.print_exc()

    return reviews


def parse_coupang_excel(file_path: str) -> List[Dict[str, Any]]:
    """쿠팡 상품평 엑셀 파싱 (추후 구현)"""
    reviews = []
    logger.warning("[coupang] 파서 미구현")
    return reviews


# 플랫폼별 파서 레지스트리
UPLOAD_PARSERS = {
    'wconcept': parse_wconcept_excel,
    'coupang': parse_coupang_excel,
}


def get_upload_platforms() -> list:
    """업로드 지원 플랫폼 목록"""
    return list(UPLOAD_PARSERS.keys())
