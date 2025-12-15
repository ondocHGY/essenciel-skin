# QR Skincare Visualizer - Claude Code 개발 프롬프트

## 프로젝트 개요
화장품 QR코드 스캔 → 개인 피부정보 입력 → 사용기간별 피부개선 효과를 동적 그래프로 시각화하는 모바일 웹 서비스

## 기술 스택
- Backend: Laravel 11, PHP 8.2+
- Frontend: Blade + Alpine.js + Tailwind CSS
- Chart: Chart.js
- Database: MySQL 8
- QR: simplesoftwareio/simple-qrcode

---

## DB 스키마

### products
```
id, code (unique), name, brand, category,
ingredients (JSON), base_curve (JSON),
qr_path, created_at, updated_at
```

### user_profiles
```
id, session_id, age_group, skin_type, gender,
concerns (JSON), lifestyle (JSON), skincare_habit (JSON),
satisfaction (int 1-10), created_at
```

### analysis_results
```
id, session_id, product_id (FK), profile_id (FK),
timeline (JSON), milestones (JSON), comparison (JSON),
created_at
```

---

## 개인화 입력 항목

### Step 1: 기본 정보
| 필드 | 값 |
|------|-----|
| age_group | 10대 \| 20대초반 \| 20대후반 \| 30대 \| 40대 \| 50대이상 |
| skin_type | 건성 \| 지성 \| 복합성 \| 민감성 \| 중성 |
| gender | female \| male \| other |

### Step 2: 피부 고민 (복수선택)
```
concerns[]: wrinkle | elasticity | pigmentation | pore | acne | dryness | redness | dullness
```

### Step 3: 생활환경
| 필드 | 값 |
|------|-----|
| sleep_hours | under6 \| 6to8 \| over8 |
| uv_exposure | indoor \| normal \| outdoor |
| stress_level | low \| medium \| high |
| water_intake | under1L \| 1to2L \| over2L |
| smoking_drinking | none \| sometimes \| often |

### Step 4: 스킨케어 습관
| 필드 | 값 |
|------|-----|
| care_steps | 3이하 \| 5단계 \| 7이상 |
| consistency | sometimes \| regular \| always |
| satisfaction | 1~10 (range slider) |

---

## 개인화 알고리즘

### 보정 계수
```php
$modifiers = [
  'age' => ['10대'=>1.2, '20대초반'=>1.15, '20대후반'=>1.1, '30대'=>1.0, '40대'=>0.85, '50대이상'=>0.7],
  'skin_type' => ['중성'=>1.1, '지성'=>1.0, '건성'=>0.95, '복합성'=>0.9, '민감성'=>0.8],
  'consistency' => ['always'=>1.3, 'regular'=>1.0, 'sometimes'=>0.6],
  'lifestyle' => 0.75 ~ 1.15 (복합계산)
];
```

### 계산 공식
```
personalizedScore[week] = baseCurve[week] * ageModifier * skinTypeModifier * lifestyleModifier * consistencyModifier * concernMatch
```

---

## 라우트 구조
```
GET  /p/{code}              → ProductController@show
GET  /p/{code}/survey       → SurveyController@index
POST /p/{code}/survey       → SurveyController@store
GET  /p/{code}/result       → ResultController@show

[Admin]
GET  /admin/products        → Admin\ProductController@index
POST /admin/products        → Admin\ProductController@store
GET  /admin/products/{id}/qr → Admin\ProductController@generateQR
```

---

## 차트 시각화

### 타임라인 라인차트
- X축: 1주, 2주, 4주, 8주, 12주
- Y축: 0-100
- 라인: moisture, elasticity, tone, pore, wrinkle
- 애니메이션 적용

### 레이더차트
- 5축: moisture, elasticity, tone, pore, wrinkle
- Before/After 오버레이

### 비교 바차트
- 평균 vs 나의 예상 수평 바

---

## 디렉토리 구조
```
app/
├── Http/Controllers/
│   ├── ProductController.php
│   ├── SurveyController.php
│   ├── ResultController.php
│   └── Admin/ProductController.php
├── Models/
│   ├── Product.php
│   ├── UserProfile.php
│   └── AnalysisResult.php
├── Services/
│   ├── AnalysisService.php
│   └── QrGeneratorService.php
resources/views/
├── product/show.blade.php
├── survey/index.blade.php
├── result/show.blade.php
```

---

# Claude Code 프롬프트

## Prompt 1: 프로젝트 초기화
```
Laravel 11 프로젝트 생성해줘. 프로젝트명은 skincare-visualizer.
필요 패키지: simplesoftwareio/simple-qrcode
Tailwind CSS, Alpine.js 설정까지 완료해줘.
```

## Prompt 2: DB 마이그레이션
```
마이그레이션 파일 3개 만들어줘:

1. products: id, code(unique), name, brand, category, ingredients(json), base_curve(json), qr_path(nullable), timestamps

2. user_profiles: id, session_id, age_group, skin_type, gender, concerns(json), lifestyle(json), skincare_habit(json), satisfaction(tinyint), created_at

3. analysis_results: id, session_id, product_id(fk), profile_id(fk), timeline(json), milestones(json), comparison(json), created_at
```

## Prompt 3: 모델 생성
```
Product, UserProfile, AnalysisResult 모델 만들어줘.
JSON 컬럼들은 cast 설정하고, 관계 설정해줘.
Product hasMany AnalysisResult,
UserProfile hasOne AnalysisResult
```

## Prompt 4: 분석 서비스
```
app/Services/AnalysisService.php 만들어줘.

기능:
1. calculate(Product $product, UserProfile $profile): array
2. 보정계수: age(0.7~1.2), skinType(0.8~1.1), lifestyle(0.75~1.15), consistency(0.6~1.3)
3. product->base_curve 값에 보정계수 곱해서 timeline 배열 생성 (1,2,4,8,12주)
4. 주요 마일스톤 자동 생성 (효과 10%,30%,50% 도달 시점)
5. 평균 대비 비교값 계산
```

## Prompt 5: 컨트롤러
```
컨트롤러 3개 만들어줘:

1. ProductController
- show($code): 제품 정보 표시, 세션 시작

2. SurveyController  
- index($code): 4단계 설문 폼
- store($code): 프로필 저장 후 분석 실행, 결과 페이지로 redirect

3. ResultController
- show($code): 분석 결과 + 차트 데이터 전달
```

## Prompt 6: 설문 뷰 (Alpine.js)
```
resources/views/survey/index.blade.php 만들어줘.

Alpine.js로 4단계 스텝 폼 구현:
- Step 1: age_group(라디오), skin_type(라디오), gender(라디오)
- Step 2: concerns(체크박스 복수선택, 최소1개)
- Step 3: sleep_hours, uv_exposure, stress_level, water_intake, smoking_drinking (각각 라디오)
- Step 4: care_steps(라디오), consistency(라디오), satisfaction(range slider 1-10)

진행바 표시, 이전/다음 버튼, 마지막에 제출
Tailwind로 모바일 최적화 UI
```

## Prompt 7: 결과 뷰 (Chart.js)
```
resources/views/result/show.blade.php 만들어줘.

Chart.js로 3개 차트:
1. 타임라인 라인차트: X축(1,2,4,8,12주), Y축(0-100), 5개 라인(moisture,elasticity,tone,pore,wrinkle), 애니메이션 있게
2. 레이더차트: 5축, before/after 두 데이터셋 오버레이
3. 수평 바차트: 평균 vs 나의예상 비교

마일스톤 표시, SNS 공유버튼
모바일 반응형으로 만들어줘
```

## Prompt 8: QR 생성
```
app/Services/QrGeneratorService.php 만들어줘.
simplesoftwareio/simple-qrcode 사용해서
generate(Product $product): string (저장경로 반환)
QR 내용: config('app.url')/p/{product->code}
저장: storage/app/public/qrcodes/{code}.png

Admin/ProductController에 generateQR 액션 추가
```

## Prompt 9: Seeder
```
ProductSeeder 만들어서 테스트 제품 3개 넣어줘.

각 제품마다 base_curve 예시:
{
  "moisture": [10,25,40,65,80],
  "elasticity": [5,15,30,50,70],
  "tone": [8,20,35,55,75],
  "pore": [3,10,20,35,50],
  "wrinkle": [2,8,18,30,45]
}
배열은 [1주,2주,4주,8주,12주] 시점 값
```

---

## 완료 후 실행
```bash
php artisan migrate
php artisan db:seed --class=ProductSeeder
php artisan storage:link
php artisan serve

# 브라우저: http://localhost:8000/p/PROD-001
```
