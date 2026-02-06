# Review Collector

다중 플랫폼 리뷰 자동 수집 서비스 (FastAPI + Selenium)

## 지원 플랫폼

- ✅ **Qoo10** (QSM 판매자 관리)
- 🔜 Coupang (예정)
- 🔜 Musinsa (예정)
- 🔜 Amazon (예정)

## 구조

```
review-collector/
├── app/
│   ├── __init__.py
│   ├── main.py              # FastAPI 앱
│   ├── config.py            # 설정
│   ├── database.py          # DB 연결
│   ├── models.py            # SQLAlchemy/Pydantic 모델
│   ├── scheduler.py         # 스케줄러
│   └── scrapers/            # 플랫폼별 스크래퍼
│       ├── __init__.py
│       ├── base.py          # 베이스 클래스
│       └── qoo10.py         # Qoo10 스크래퍼
├── data/                    # 쿠키 등 데이터 (볼륨)
├── downloads/               # 다운로드 파일
├── Dockerfile
├── docker-compose.yml
├── requirements.txt
└── .env
```

## 빠른 시작

### 1. 환경 설정

```bash
cp .env.example .env
# .env 파일 수정
```

### 2. Docker로 실행

```bash
docker-compose up -d
```

### 3. 로그 확인

```bash
docker-compose logs -f
```

## QSM 쿠키 설정 (중요!)

QSM은 reCAPTCHA를 사용하여 자동 로그인을 차단합니다.
**최초 1회 수동 로그인 후 쿠키를 저장**해야 합니다.

### 로컬에서 쿠키 생성

```bash
# 필요한 패키지 설치
pip install selenium webdriver-manager

# 쿠키 저장 스크립트 실행
python local_save_cookies.py
```

1. 브라우저가 열리면 QSM에 로그인 (reCAPTCHA 완료)
2. 로그인 완료 후 터미널에서 Enter
3. 생성된 `qsm_cookies.json`을 Docker 볼륨에 복사

```bash
# Docker 볼륨에 쿠키 복사
docker cp qsm_cookies.json review-collector:/app/data/
```

## API 엔드포인트

| Method | Endpoint | 설명 |
|--------|----------|------|
| GET | `/` | 상태 및 지원 플랫폼 |
| GET | `/health` | 헬스체크 |
| GET | `/api/platforms` | 지원 플랫폼 목록 |
| GET | `/api/reviews` | 리뷰 조회 |
| GET | `/api/reviews/stats` | 리뷰 통계 |
| POST | `/api/reviews/sync` | 소스 기반 동기화 |
| POST | `/api/reviews/sync/{platform}` | 플랫폼별 수동 동기화 |

### 사용 예시

```bash
# 상태 확인
curl http://localhost:8001/

# Qoo10 리뷰 수동 동기화
curl -X POST "http://localhost:8001/api/reviews/sync/qoo10?product_id=10"

# 리뷰 조회
curl "http://localhost:8001/api/reviews?product_id=10&platform=qoo10"

# 통계 조회
curl "http://localhost:8001/api/reviews/stats?product_id=10"
```

## 스케줄러

매일 지정 시간에 자동으로 모든 활성 소스의 리뷰를 동기화합니다.

```bash
# .env에서 설정
SYNC_ENABLED=true
SYNC_HOUR=3      # 새벽 3시
SYNC_MINUTE=0
```

## 새 플랫폼 추가

1. `app/scrapers/` 에 새 스크래퍼 파일 생성 (예: `coupang.py`)
2. `BaseScraper` 상속하여 구현
3. `app/scrapers/__init__.py`의 `SCRAPERS`에 등록

```python
# app/scrapers/coupang.py
from app.scrapers.base import BaseScraper

class CoupangScraper(BaseScraper):
    platform = "coupang"

    def fetch_reviews(self, **kwargs):
        # 구현
        pass

    def login(self):
        # 구현
        pass
```

```python
# app/scrapers/__init__.py
from app.scrapers.coupang import CoupangScraper

SCRAPERS = {
    'qoo10': Qoo10Scraper,
    'coupang': CoupangScraper,  # 추가
}
```

## AWS EC2 배포

### 1. Docker 설치

```bash
sudo yum install -y docker
sudo service docker start
sudo usermod -a -G docker ec2-user
```

### 2. Docker Compose 설치

```bash
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```

### 3. 실행

```bash
# 프로젝트 클론 또는 복사
cd review-collector

# 환경 설정
cp .env.example .env
vi .env  # DB_HOST, QSM 정보 등 수정

# 실행
docker-compose up -d

# 쿠키 복사 (로컬에서 생성한 쿠키)
docker cp qsm_cookies.json review-collector:/app/data/
```

## Laravel 연동

이 서비스는 Laravel의 `product_reviews` 테이블에 직접 데이터를 저장합니다.

```php
// Laravel에서 리뷰 조회
$reviews = $product->reviews()
    ->visible()
    ->where('platform', 'qoo10')
    ->latest('reviewed_at')
    ->get();

// 평균 평점
$avgRating = $product->reviews()
    ->visible()
    ->avg('rating');
```

## 트러블슈팅

### 쿠키 만료

```bash
# 새 쿠키 생성 후 복사
python local_save_cookies.py
docker cp qsm_cookies.json review-collector:/app/data/
docker-compose restart
```

### Chrome 버전 불일치

```bash
# .env에서 Chrome 버전 설정
CHROME_VERSION=144  # 설치된 Chrome 버전에 맞춤
```

### 메모리 부족

```yaml
# docker-compose.yml에서 shm_size 증가
shm_size: '4gb'
```
