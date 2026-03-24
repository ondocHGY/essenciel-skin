from airflow import DAG
from airflow.operators.python import PythonOperator
from datetime import datetime, timedelta
import requests

BASE_URL = "http://review-collector:8000"
PLATFORMS = ["qoo10", "naver", "musinsa", "shopee"]
KEEP_ALIVE_PLATFORMS = ["qoo10", "naver", "shopee"]

default_args = {
    'owner': 'airflow',
    'retries': 2,
    'retry_delay': timedelta(minutes=5),
}


def sync_platform(platform: str):
    """플랫폼별 리뷰 동기화 API 호출"""
    response = requests.post(
        f"{BASE_URL}/api/reviews/sync/{platform}",
        timeout=600,
    )
    print(f"[{platform}] Status: {response.status_code}")
    print(f"[{platform}] Response: {response.text}")

    if response.status_code != 200:
        raise Exception(f"[{platform}] API 호출 실패: {response.status_code} - {response.text}")

    result = response.json()

    if not result.get("success"):
        raise Exception(f"[{platform}] 동기화 실패: {result.get('message')}")

    print(f"[{platform}] +{result['reviews_added']}개 추가, {result['reviews_updated']}개 업데이트, 총 {result['total_reviews']}개")
    return result


def trigger_keep_alive(platform: str):
    """플랫폼별 쿠키 keep-alive API 호출"""
    response = requests.post(
        f"{BASE_URL}/api/cookies/keep-alive/{platform}",
        timeout=120,
    )
    print(f"[keep-alive:{platform}] Status: {response.status_code}")
    print(f"[keep-alive:{platform}] Response: {response.text}")

    if response.status_code != 200:
        raise Exception(f"[keep-alive:{platform}] 실패: {response.status_code} - {response.text}")

    return response.json()


# ============== 리뷰 동기화 DAG ==============
with DAG(
    dag_id='review_sync',
    default_args=default_args,
    description='플랫폼별 리뷰 수집',
    schedule='0 4,16 * * *',
    start_date=datetime(2026, 3, 23),
    catchup=False,
    tags=['review', 'sync'],
) as sync_dag:

    for platform in PLATFORMS:
        PythonOperator(
            task_id=f'sync_{platform}',
            python_callable=sync_platform,
            op_args=[platform],
        )


# ============== 쿠키 Keep-Alive DAG ==============
with DAG(
    dag_id='cookie_keep_alive',
    default_args={
        'owner': 'airflow',
        'retries': 1,
        'retry_delay': timedelta(minutes=3),
    },
    description='쿠키 세션 유지',
    schedule='0 */2 * * *',
    start_date=datetime(2026, 3, 23),
    catchup=False,
    tags=['review', 'cookie'],
) as keep_alive_dag:

    for platform in KEEP_ALIVE_PLATFORMS:
        PythonOperator(
            task_id=f'keep_alive_{platform}',
            python_callable=trigger_keep_alive,
            op_args=[platform],
        )
