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