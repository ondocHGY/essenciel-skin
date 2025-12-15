# Essenciel Skin - QR 스킨케어 시각화 서비스

화장품 QR코드 스캔 후 개인 피부정보를 입력하면 사용기간별 피부개선 효과를 동적 그래프로 시각화하는 모바일 웹 서비스입니다.

## 주요 기능

- **QR 코드 스캔**: 제품별 고유 QR 코드로 서비스 접근
- **개인화 설문**: 피부타입, 고민, 생활습관 등 4단계 설문
- **맞춤형 분석**: 개인 프로필 기반 피부개선 예측 알고리즘
- **시각화 차트**: Chart.js 기반 타임라인, 레이더, 비교 차트
- **관리자 대시보드**: 제품/설문 관리 및 통계

## 기술 스택

| 구분 | 기술 |
|------|------|
| Backend | Laravel 12, PHP 8.2+ |
| Frontend | Blade + Alpine.js + Tailwind CSS 4 |
| Chart | Chart.js |
| Database | MySQL 8 / SQLite |
| QR 생성 | simplesoftwareio/simple-qrcode |
| 빌드 도구 | Vite |

## 설치 방법

### 요구사항
- PHP 8.2 이상
- Composer
- Node.js 18+
- MySQL 8 또는 SQLite

### 설치

```bash
# 저장소 클론
git clone https://github.com/ondocHGY/essenciel-skin.git
cd essenciel-skin

# Composer 설정 스크립트 실행 (의존성 설치, 환경설정, 마이그레이션, 빌드 포함)
composer setup
```

또는 수동 설치:

```bash
# 의존성 설치
composer install
npm install

# 환경 설정
cp .env.example .env
php artisan key:generate

# 데이터베이스 마이그레이션
php artisan migrate

# 시더 실행 (테스트 데이터)
php artisan db:seed

# 스토리지 링크
php artisan storage:link

# 프론트엔드 빌드
npm run build
```

## 실행

### 개발 환경

```bash
composer dev
```

위 명령어로 Laravel 서버, 큐, 로그, Vite가 동시에 실행됩니다.

### 개별 실행

```bash
php artisan serve    # Laravel 서버
npm run dev          # Vite 개발 서버
```

## 사용 방법

### 사용자 플로우

1. **QR 스캔**: `http://localhost:8000/p/{product-code}` 접속
2. **설문 진행**: 4단계 개인정보 입력
   - Step 1: 기본 정보 (연령대, 피부타입, 성별)
   - Step 2: 피부 고민 (복수 선택)
   - Step 3: 생활환경 (수면, 자외선, 스트레스 등)
   - Step 4: 스킨케어 습관 (케어 단계, 규칙성, 만족도)
3. **결과 확인**: 맞춤형 피부개선 예측 그래프 확인

### 관리자 페이지

- URL: `http://localhost:8000/admin`
- 기능: 제품 관리, QR 생성, 설문 결과 조회/내보내기

## 프로젝트 구조

```
app/
├── Http/Controllers/
│   ├── ProductController.php      # 제품 정보 표시
│   ├── SurveyController.php       # 설문 처리
│   ├── ResultController.php       # 결과 표시
│   └── Admin/                     # 관리자 컨트롤러
├── Models/
│   ├── Product.php                # 제품 모델
│   ├── UserProfile.php            # 사용자 프로필
│   └── AnalysisResult.php         # 분석 결과
├── Services/
│   ├── AnalysisService.php        # 개인화 분석 로직
│   └── QrGeneratorService.php     # QR 코드 생성
resources/views/
├── product/show.blade.php         # 제품 페이지
├── survey/index.blade.php         # 설문 페이지
├── result/show.blade.php          # 결과 페이지
└── admin/                         # 관리자 페이지
```

## 라우트

| Method | URI | 설명 |
|--------|-----|------|
| GET | `/p/{code}` | 제품 정보 페이지 |
| GET | `/p/{code}/survey` | 설문 페이지 |
| POST | `/p/{code}/survey` | 설문 제출 |
| GET | `/p/{code}/result` | 결과 페이지 |
| GET | `/admin` | 관리자 대시보드 |
| GET | `/admin/products` | 제품 관리 |
| GET | `/admin/surveys` | 설문 결과 관리 |

## 개인화 알고리즘

사용자 프로필을 기반으로 제품의 기본 효과 곡선에 보정 계수를 적용합니다:

```
personalizedScore = baseCurve × ageModifier × skinTypeModifier × lifestyleModifier × consistencyModifier
```

### 보정 계수
- **연령대**: 10대(1.2) ~ 50대 이상(0.7)
- **피부타입**: 중성(1.1) ~ 민감성(0.8)
- **생활습관**: 0.75 ~ 1.15
- **케어 규칙성**: always(1.3) ~ sometimes(0.6)

## 라이선스

MIT License