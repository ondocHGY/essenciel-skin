# Essenciel Skin - QR 스킨케어 개인화 분석 서비스

## 프로젝트 개요
QR 코드 기반의 개인화 피부 분석 서비스. 사용자가 제품 QR을 스캔하면 설문을 통해 맞춤형 피부 개선 예측 결과를 제공합니다.

## 기술 스택
- **Backend**: Laravel 12, PHP 8.2+
- **Frontend**: Blade, Alpine.js, Tailwind CSS 4, Chart.js
- **Database**: SQLite (기본), MySQL 8 지원
- **Build**: Vite 7

## 주요 디렉토리 구조
```
app/
├── Http/Controllers/
│   ├── ProductController.php      # 제품 정보 표시
│   ├── SurveyController.php       # 설문 처리
│   ├── ResultController.php       # 결과 표시
│   └── Admin/                     # 관리자 컨트롤러
├── Models/
│   ├── Product.php                # 제품 (code, base_curve, ingredients)
│   ├── UserProfile.php            # 사용자 프로필 (설문 데이터)
│   ├── AnalysisResult.php         # 분석 결과 (timeline, metrics)
│   ├── SurveyOptionCategory.php   # 설문 카테고리
│   └── SurveyOption.php           # 설문 옵션 (modifier 값)
└── Services/
    ├── AnalysisService.php        # 개인화 분석 알고리즘 (476줄)
    └── QrGeneratorService.php     # QR 코드 생성

resources/views/
├── product/show.blade.php         # 제품 정보 페이지
├── survey/index.blade.php         # 3단계 설문 페이지
├── result/show.blade.php          # 결과 시각화 페이지 (535줄)
└── admin/                         # 관리자 페이지
```

## 사용자 플로우
1. QR 스캔 → `/p/{product-code}` 제품 페이지
2. 3단계 설문 진행 (연령, 피부타입, 생활습관, 피부고민)
3. AI 분석 애니메이션
4. 12주 예상 효과 그래프 및 상세 결과 표시

## 핵심 기능
- **개인화 분석 알고리즘**: base_curve × 다중 보정계수 (연령, 피부타입, 생활습관, 규칙성, 고민매칭)
- **5가지 피부 지표**: 수분, 탄력, 피부톤, 모공, 주름
- **차트 시각화**: 라인차트(주차별), 바차트(평균비교), 레이더차트(Before/After)
- **동적 설문 관리**: 관리자에서 설문 옵션 및 modifier 값 조정 가능

## 라우트 구조
```
# 사용자
GET  /p/{code}           # 제품 정보
GET  /p/{code}/survey    # 설문 페이지
POST /p/{code}/survey    # 설문 제출
GET  /p/{code}/result    # 결과 페이지

# 관리자 (/admin)
GET  /login              # 로그인
GET  /                   # 대시보드
     /products           # 제품 CRUD
     /surveys            # 설문 결과 관리
     /survey-options     # 설문 옵션 관리
```

## 데이터베이스 주요 테이블
- **products**: code, name, brand, ingredients(JSON), base_curve(JSON), qr_path
- **user_profiles**: session_id, age_group, skin_type, gender, concerns(JSON), lifestyle(JSON)
- **analysis_results**: product_id, profile_id, timeline(JSON), metrics(JSON)
- **survey_option_categories**: key, name, has_icon, is_multiple
- **survey_options**: category_id, value, label, modifier

## 개발 명령어
```bash
# 설치 및 설정
composer setup           # 전체 설정 자동화

# 개발 실행
composer dev             # Laravel + Vite + Queue 동시 실행
php artisan serve        # 개별 서버 실행

# 데이터베이스
php artisan migrate      # 마이그레이션
php artisan db:seed      # 시더 실행

# 빌드
npm run build            # 프로덕션 빌드
```

## 주요 서비스 로직

### AnalysisService (app/Services/AnalysisService.php)
- `calculate()`: 전체 분석 실행
- `calculateTimeline()`: 1, 2, 4, 8, 12주 효과 값 계산
- `calculateQuantitativeMetrics()`: 정량적 지표 (수분%, 탄력mg/cm², 등)
- `calculateLifestyleModifier()`: 생활습관 보정계수 계산
- modifier 값 1시간 캐싱, DB 없을 때 fallback 사용

### QrGeneratorService (app/Services/QrGeneratorService.php)
- SimpleSoftwareIO 라이브러리 사용
- 300x300 PNG 생성, storage/app/public/qrcodes 저장

## 개발 환경
- **Docker**: Laradock (C:\ondoc\laradock)
- **프로젝트 경로**: /var/www/essenciel_qrcode
- **PHP 실행**: `docker-compose -f C:\ondoc\laradock\docker-compose.yml exec workspace php`
- **Artisan 실행**: `docker-compose -f C:\ondoc\laradock\docker-compose.yml exec workspace php artisan`
- **NPM 실행**: `docker-compose -f C:\ondoc\laradock\docker-compose.yml exec workspace npm`

## 환경 설정
```env
DB_CONNECTION=sqlite
SESSION_DRIVER=database
CACHE_STORE=database
```

## 주의사항
- 세션 기반 사용자 추적 (회원가입 불필요)
- JSON 컬럼 사용 (ingredients, base_curve, timeline, metrics 등)
- 설문 옵션 변경 시 캐시 클리어 필요 (`php artisan cache:clear`)

---

## 변경 이력

### 2026-01-08

#### 관리자 기능
- **커스텀 QR 코드 생성** 메뉴 추가 (`/admin/custom-qr`)
  - URL 입력 → QR 이미지 생성 (DB 저장 없음)
  - `CustomQrCodeController`, `QrGeneratorService::generateFromUrl()` 추가

#### 결과 페이지 (result/show.blade.php)

**마일스톤 카드 슬라이드**
- 1.5개 노출 + 무한 자동 슬라이드 구현
- 왼쪽 오버레이로 이전 슬라이드 완전 가림
- `milestoneCarousel()` Alpine.js 함수 추가

**피부 반응 프로파일 요약**
- 게이지-텍스트 불일치 수정: level 값 기반으로 description 동적 생성
- 게이지 라벨 항목별 차별화:
  - 피부재생속도: 느림/보통/빠름
  - 피부 수분유지력: 적음/보통/많음
  - 피부 색소 반응성: 낮음/보통/높음

**효능 발현 예측**
- 텍스트 포인트 컬러 변환 공식 수정 (#acdda5 → #369755 기준)
  - R×0.314, G×0.683, B×0.515 비율 적용

**추가 효과 향상 방법**
- actionShort 텍스트에 흰색 그라데이션 배경 추가
  - `linear-gradient(to right, rgba(255,255,255,0.8), rgba(255,255,255,0.2))`
- 이미지 z-index 조정 (`z-0`)으로 텍스트가 이미지 위에 표시

**분석 완료 버튼**
- 채워지는 애니메이션 추가
  - 흰색 배경에서 검은색이 왼쪽→오른쪽으로 채워짐
  - 완료 시 "분석 완료" 텍스트 표시
  - `analysisCompleteBtn()` Alpine.js 함수 추가

### 2026-01-19

#### 관리자 제품 편집 페이지 (admin/products/edit.blade.php)

**집중 효능 타입 라디오 버튼 수정**
- `:checked` 바인딩에서 `x-model="selected"`로 변경하여 선택값 저장 문제 해결
- `window.currentEfficacyType` 전역 변수로 현재 선택된 효능 타입 추적

**효능 타입별 기본값 적용 기능 강화**
- 모든 설정 섹션에 효능 타입별 개별 버튼 추가 (수분 공급, 탄력 개선, 피부톤 개선, 모공 케어, 피부 진정)
- `@efficacy-type-changed.window` 이벤트로 효능 타입 변경 시 자동 프리셋 적용
- 적용 대상 섹션:
  - 효능 발현 예측 설정 (`efficacySettings()`)
  - 효능 측정 기준값 (`efficacyMetricsSettings()`)
  - 제품 소개 페이지 설정 (`introPageSettings()`)

**최적 사용 시간 설정 추가**
- 새로운 UI 섹션 추가: 사용 이유, 아침 효과(%), 저녁 효과(%) 입력
- `optimalTimingSettings()` Alpine.js 함수 추가
- 효능 타입별 기본값 적용 버튼 포함

#### Product 모델 (app/Models/Product.php)
- `optimal_timing` 필드 추가 (`$fillable`, `$casts`)
- `getOptimalTiming()` 메서드 추가: 효능 타입별 기본값 반환
  - moisture: 아침/저녁 동일 (100%)
  - elasticity: 저녁 131%
  - tone: 저녁 123%
  - pore: 저녁 122%
  - soothing: 저녁 125%

#### 마이그레이션
- `2026_01_19_000000_add_optimal_timing_to_products_table.php` 생성
- `optimal_timing` JSON 컬럼 추가

#### ProductController (app/Http/Controllers/Admin/ProductController.php)
- `optimal_timing` 유효성 검사 규칙 추가
- 저장 로직 추가 (정수 변환, 빈 값 필터링)

#### AnalysisService (app/Services/AnalysisService.php)

**효능 측정 지표 퍼센트 계산 수정**
- 단위가 `%`인 경우: 절대 개선량 사용 (2% → 23% = 21% 개선)
- 단위가 `%`가 아닌 경우 (L*, R 등): 상대적 변화율 계산
- 기존 1096% 오류 → 21% 정상 표시

**최적 사용 시간 제품 설정 연동**
- `generateUsageGuide()`, `calculateOptimalUsage()`에 `$product` 파라미터 추가
- 제품에 설정된 `optimal_timing` 값 우선 사용, 없으면 효능 타입별 기본값
- `best_time` 자동 결정: 아침/저녁 효과 비교하여 "아침", "저녁", "아침 & 저녁" 중 선택

#### 결과 페이지 (result/show.blade.php)

**그래프 게이지 그라데이션 색상 수정**
- 끝부분 밝기 증가: R×0.92, G×0.88, B×0.78 비율 적용

**효능 발현 예측 텍스트 색상 수정**
- 녹색 톤 과다 문제 해결
- 균일한 0.55 비율 적용으로 원본 색조 유지

### 2026-01-20

#### 제품별 강조 컬러 추가

**Product 모델 (app/Models/Product.php)**
- `accent_color` 필드 추가 (`$fillable`)

**마이그레이션**
- `2026_01_20_000000_add_accent_color_to_products_table.php` 생성
- `accent_color` 문자열 컬럼 추가 (nullable)

**관리자 제품 편집 페이지 (admin/products/edit.blade.php)**
- 포인트 컬러 & 강조 컬러를 2열 그리드로 배치
- 강조 컬러 입력 UI 추가 (컬러 피커, 텍스트 입력, 프리셋 버튼)
- "포인트 컬러에서 자동 생성" 버튼 추가 (55% 어둡게 계산)
- 미리보기 영역에 그라데이션 프리뷰 추가
- `colorSettings()` Alpine.js 함수 추가

**ProductController (app/Http/Controllers/Admin/ProductController.php)**
- `accent_color` 유효성 검사 규칙 추가

**결과 페이지 (result/show.blade.php)**
- 제품에 `accent_color`가 설정되어 있으면 해당 값을 `$darkerPointColor`, `$textPointColor`로 사용
- 설정되지 않은 경우 기존 변환 함수(자동 계산)를 fallback으로 사용

### 2026-02-06

#### 리뷰 수집 기능 추가

**새로운 모델**
- `ProductReviewSource`: 리뷰 소스 (플랫폼별 연동 정보)
  - 지원 플랫폼: Shopee, 네이버, Qoo10, 쿠팡, 무신사, 화해, W컨셉, 아마존
- `ProductReview`: 개별 리뷰 데이터

**마이그레이션**
- `2026_02_05_000000_create_product_review_sources_table.php`
- `2026_02_05_000001_create_product_reviews_table.php`

**리뷰 어댑터 서비스** (`app/Services/Review/`)
- `ReviewAdapterInterface`: 리뷰 어댑터 인터페이스
- `ShopeeReviewAdapter`: Shopee 리뷰 연동
- `NaverReviewAdapter`: 네이버 스마트스토어 리뷰 연동
- `Qoo10ReviewAdapter`: Qoo10 리뷰 연동
- `ReviewSyncService`: 리뷰 동기화 서비스 (전체/개별 소스 동기화)

**관리자 기능**
- `ReviewController`: 리뷰 관리 컨트롤러
- `resources/views/admin/reviews/`: 리뷰 관리 뷰
- 제품 편집 페이지에 리뷰 소스 관리 UI 추가

**Artisan 명령어**
- `php artisan reviews:sync`: 리뷰 동기화 명령어

**라우트 추가**
```
# 관리자 (/admin)
     /reviews            # 리뷰 관리
```

**데이터베이스 테이블 추가**
- **product_review_sources**: product_id, platform, platform_name, external_url, review_count, average_rating, api_config(encrypted), synced_at
- **product_reviews**: product_id, review_source_id(string), platform, platform_product_code, product_name, rating, title, content, author, images(JSON), reviewed_at
  - `review_source_id`: 플랫폼 상품코드 문자열 (FK 아님, product_review_sources.external_id와 매칭)
  - `platform_product_code`: 플랫폼별 상품코드 (네이버 상품번호, Qoo10 상품코드, Shopee name_hash 등)
  - `product_id`: 매칭된 제품 ID (NULL 허용, 나중에 관리자에서 매칭 가능)

---

## Review Collector (Python)

별도 Python 프로젝트로 리뷰 크롤링/수집 담당. Docker로 EC2에 배포.

### 디렉토리 구조
```
review-collector/
├── app/
│   ├── config.py           # 설정 관리 (pydantic-settings)
│   ├── database.py         # SQLAlchemy DB 연결
│   ├── main.py             # FastAPI 앱 진입점
│   ├── models.py           # DB 모델 (ProductReviewSource, ProductReview)
│   ├── scheduler.py        # APScheduler 기반 스케줄러
│   └── scrapers/
│       ├── __init__.py     # 스크래퍼 팩토리
│       ├── base.py         # BaseScraper 추상 클래스
│       ├── qoo10.py        # Qoo10 QSM 스크래퍼
│       ├── naver.py        # 네이버 스마트스토어 스크래퍼
│       ├── musinsa.py      # 무신사 파트너 스크래퍼 (SSO API + OTP)
│       └── shopee.py       # Shopee 셀러센터 스크래퍼 (DOM 스크래핑)
│   ├── parsers.py          # 엑셀 업로드 파서 (W컨셉, 쿠팡)
├── data/
│   ├── qsm_cookies.json    # Qoo10 쿠키 (gitignore)
│   ├── naver_cookies.json  # 네이버 쿠키 (gitignore)
│   └── shopee_cookies.json # Shopee 쿠키 (gitignore)
├── Dockerfile
├── docker-compose.yml
├── requirements.txt
└── test_*.py               # 테스트 스크립트
```

### 지원 플랫폼
- **Qoo10**: QSM(판매자 센터) 엑셀 다운로드 방식
- **Naver**: 스마트스토어 센터 엑셀 다운로드 방식 (쿠키 로그인)
- **Musinsa**: SSO API 로그인 + Google OTP 자동 인증 (쿠키 불필요)
- **Shopee**: 셀러센터(seller.shopee.kr) DOM 스크래핑 (쿠키 로그인)

### 실행 방법
```bash
# Docker로 실행 (권장)
cd review-collector
docker-compose up -d

# 로컬 테스트 (Laradock workspace)
cd /var/www/essenciel_qrcode/review-collector
python3 test_naver.py
python3 test_qoo10.py
```

### 환경변수
```env
# Qoo10
QSM_ID=your_qsm_id
QSM_PASSWORD=your_qsm_password

# 네이버 스마트스토어
NAVER_ID=your_naver_id
NAVER_PASSWORD=your_naver_password

# 무신사 파트너
MUSINSA_ID=your_musinsa_id
MUSINSA_PASSWORD=your_musinsa_password
MUSINSA_OTP_SECRET=your_google_otp_secret_key

# Chrome
CHROME_HEADLESS=true
DOWNLOAD_PATH=/tmp/review_downloads

# DB (Laravel과 동일한 MySQL)
DB_HOST=mysql
DB_PORT=3306
DB_NAME=qrcode
DB_USER=root
DB_PASSWORD=

# 스케줄러
SYNC_ENABLED=true
SYNC_HOURS=4,16
SYNC_MINUTE=0
```

### API 엔드포인트
- `GET /health`: 헬스체크
- `GET /api/reviews/stats`: 리뷰 통계 조회
- `POST /api/reviews/sync`: 전체 플랫폼 동기화 (순차 처리, 결과 리스트 반환)
- `POST /api/reviews/sync/{platform}`: 특정 플랫폼 동기화
- `GET /api/sync-logs`: 동기화 실행 기록
- `GET /api/cookies`: 쿠키 상태 조회
- `POST /api/cookies/{platform}`: 쿠키 업로드
- `POST /api/reviews/upload/{platform}`: 엑셀 파일 업로드 (W컨셉, 쿠팡)

### 네이버 쿠키 로그인
네이버 커머스는 ID/PW 로그인 시 캡챠가 발생하므로 쿠키 로그인 사용:
1. Windows에서 `test_naver.py` 실행 (CHROME_HEADLESS=false)
2. 브라우저에서 수동 로그인
3. `data/naver_cookies.json` 파일 자동 저장
4. 이후 쿠키로 자동 로그인

### 무신사 인증 흐름
쿠키 기반이 아닌 SSO API 직접 호출 + pyotp 자동 OTP 생성 방식:
1. `POST api.one.musinsa.com/.../login/password` → `sso-pre-auth-token` 쿠키
2. `POST api.one.musinsa.com/.../login/otp` (pyotp로 TOTP 코드 생성) → 전체 인증 쿠키 발급
3. `GET bizest.musinsa.com` 리뷰 페이지 → PHPSESSID 세션 쿠키
4. `POST bizest.musinsa.com/po/api/csm/csm07/search` → 리뷰 JSON (form-encoded, 대문자 파라미터: `PAGE`, `LIMIT`, `MENU_ID`)

토큰 수명: pp-auth(5분), pp-auth-rtk(4시간), partner-platform-rtk(24시간)
→ 매 동기화 시 ID/PW+OTP로 새로 로그인하므로 토큰 만료 무관

### 주의사항
- `data/*.json` (쿠키 파일)은 gitignore
- `__pycache__/`, `.env`는 gitignore
- Docker에서는 headless 모드 필수 (`CHROME_HEADLESS=true`)
- 무신사 SSO는 짧은 시간 내 연속 로그인 시 간헐적 401 발생 가능 (5회 실패 시 30분 잠금)

---

### 2026-02-11

#### 무신사 파트너 스크래퍼 추가 (SSO API + OTP 자동 로그인)

**`review-collector/app/scrapers/musinsa.py`** (신규)
- 쿠키 기반 → SSO API 직접 호출 + pyotp OTP 자동 생성 방식으로 구현
- 인증: `login/password` → `login/otp` → bizest 리뷰 페이지 → 리뷰 API
- 리뷰 API: form-encoded POST, 대문자 파라미터 (`PAGE`, `LIMIT`, `MENU_ID`)
- 페이지네이션: LIMIT=1000으로 649개 리뷰 1회 호출로 전체 수집

**`review-collector/app/config.py`**
- `MUSINSA_OTP_SECRET` 설정 추가

**`review-collector/docker-compose.yml`**
- `MUSINSA_ID`, `MUSINSA_PASSWORD`, `MUSINSA_OTP_SECRET` 환경변수 추가

**`review-collector/requirements.txt`**
- `pyotp==2.9.0` 추가

**`review-collector/app/main.py`**
- `POST /api/reviews/sync` 전체 동기화 시 백그라운드 → 순차 처리로 변경
- 각 플랫폼별 시작/완료 로그 출력, 응답에 전체 플랫폼 결과 리스트 반환

### 2026-02-12

#### Shopee 셀러센터 스크래퍼 추가

**`review-collector/app/scrapers/shopee.py`** (신규)
- seller.shopee.kr 셀러센터 리뷰 페이지 DOM 스크래핑
- 쿠키 로그인 방식 (인증 쿠키 유효기간 ~7일, 수동 재로그인 필요)
- JavaScript `execute_script`로 리뷰 카드 파싱:
  - `ratingListWrap` → `div.rounded.border-solid` 카드 → header(fafafa) + body(divide-x 3열)
  - 별점: `eds-react-rate-star__front` width 체크
  - 날짜: DD/MM/YYYY HH:MM → YYYY-MM-DD HH:MM:SS 변환
  - 페이지네이션: `eds-react-pagination-pager__button-next` 클릭
- 중복 제거: `order_id + md5(product_name)[:8]` 조합
- external_id: `shopee_{order_id}_{md5(product_name)[:6]}`
- 인증 쿠키 만료 임박 시 WARNING/ERROR 로그 출력 (`_check_cookie_expiry()`)

**`review-collector/save_shopee_cookies_local.py`** (신규)
- Windows 로컬에서 Shopee 셀러센터 쿠키 저장용 스크립트
- undetected_chromedriver로 수동 로그인 후 쿠키 자동 저장

**`review-collector/app/config.py`**
- `COOKIE_PATHS`에 `shopee` 추가
- `COOKIE_LABELS`에 `shopee: 'Shopee 셀러센터'` 추가

**`review-collector/docker-compose.yml`**
- `SHOPEE_COOKIE_PATH=/app/data/shopee_cookies.json` 환경변수 추가

#### review_source_id 타입 변경 (Integer → String)

**`database/migrations/2026_02_12_000000_change_review_source_id_to_string.php`** (신규)
- `product_reviews.review_source_id`: FK 제약조건 제거, `unsignedBigInteger` → `string(100)` 변경
- 플랫폼 상품코드를 직접 저장 (product_review_sources.external_id 값)

**`app/Models/ProductReview.php`**
- `reviewSource()` 관계: `review_source_id` → `product_review_sources.external_id` 기준 매칭으로 변경

**`review-collector/app/models.py`**
- `review_source_id`: `Column(Integer)` → `Column(String(100))`

**`review-collector/app/scheduler.py`**
- `save_reviews()`: 리뷰 저장/업데이트 시 `review_source_id = platform_product_code` 설정
- Shopee는 external_id에서 추출한 `name_hash`(md5[:6]), 다른 플랫폼은 기존 상품코드

#### 엑셀 업로드 파서

**`review-collector/app/parsers.py`** (신규)
- W컨셉, 쿠팡 엑셀 파일 파서
- `POST /api/reviews/upload/{platform}` API로 엑셀 업로드 → 리뷰 등록

**`review-collector/app/main.py`**
- 엑셀 업로드 엔드포인트 추가 (`/api/reviews/upload/{platform}`)

#### 관리자 플랫폼별 동기화 UI

**`app/Http/Controllers/Admin/ScraperController.php`**
- 동기화 API 타임아웃: 120s → 600s (Shopee 80페이지 수집 소요)
- API 응답 리스트 형식 처리 (플랫폼별 결과 합산 표시)

**`resources/views/admin/scraper/index.blade.php`**
- 드롭다운 → 플랫폼별 개별 동기화 버튼 UI 변경
- Qoo10(빨강), 네이버(초록), 무신사(회색), Shopee(주황), 전체(파랑)
- `syncManager()` Alpine.js 함수: 동기화 중 로딩 상태 표시