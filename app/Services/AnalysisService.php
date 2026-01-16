<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SurveyOption;
use App\Models\UserProfile;

class AnalysisService
{
    // 28일 기준 일자 (day 1, 3, 7, 14, 21, 28)
    private array $days = [1, 3, 7, 14, 21, 28];

    // DB에서 로드된 modifier (캐시됨)
    private ?array $dbModifiers = null;

    // 연령대 modifier (피부 턴오버 속도) - 폴백용
    private array $ageModifiers = [
        '10대' => 1.25,
        '20대' => 1.15,
        '30대' => 1.0,
        '40대' => 0.85,
        '50대이상' => 0.7,
    ];

    // 수면 시간 modifier (재생 능력) - 폴백용
    private array $sleepModifiers = [
        'under6' => 0.8,
        '6to8' => 1.0,
        'over8' => 1.15,
    ];

    // 자외선 노출 modifier (멜라닌 활성도) - 폴백용
    private array $uvModifiers = [
        'indoor' => 1.15,
        'normal' => 1.0,
        'outdoor' => 0.8,
    ];

    // 스트레스 modifier (염증 반응) - 폴백용
    private array $stressModifiers = [
        'low' => 1.1,
        'medium' => 1.0,
        'high' => 0.8,
    ];

    // 수분 섭취 modifier (수분 유지 메커니즘) - 폴백용
    private array $waterModifiers = [
        'under1L' => 0.8,
        '1to2L' => 1.0,
        'over2L' => 1.15,
    ];

    // 음주 modifier (장벽 손상 빈도) - 폴백용
    private array $alcoholModifiers = [
        'none' => 1.15,
        'sometimes' => 1.0,
        'often' => 0.8,
    ];

    // 흡연 modifier (산화 스트레스) - 폴백용
    private array $smokingModifiers = [
        'none' => 1.15,
        'sometimes' => 0.95,
        'often' => 0.75,
    ];

    // 스킨케어 단계 modifier - 폴백용
    private array $careStepsModifiers = [
        'basic' => 0.9,      // 3단계 이하
        'advanced' => 1.1,   // 4단계 이상
    ];

    /**
     * DB에서 modifier 로드 (캐시됨)
     */
    private function loadDbModifiers(): array
    {
        if ($this->dbModifiers !== null) {
            return $this->dbModifiers;
        }

        try {
            $this->dbModifiers = SurveyOption::getModifierMap();
        } catch (\Exception $e) {
            $this->dbModifiers = [];
        }

        return $this->dbModifiers;
    }

    /**
     * 특정 질문/값에 대한 modifier 가져오기 (DB 우선, 폴백 지원)
     */
    private function getModifier(string $questionKey, string $value, array $fallbackModifiers): float
    {
        $dbModifiers = $this->loadDbModifiers();

        if (isset($dbModifiers[$questionKey][$value])) {
            return (float) $dbModifiers[$questionKey][$value];
        }

        return $fallbackModifiers[$value] ?? 1.0;
    }

    // 효능별 피부 측정 지표
    private array $efficacyMetrics = [
        'moisture' => [
            'name' => '피부 수분도',
            'unit' => '%',
            'baseline' => [32, 48],
            'targetImprovement' => 18,
            'description' => '각질층 수분 함유량 측정',
        ],
        'elasticity' => [
            'name' => '피부 탄력도',
            'unit' => 'R',
            'baseline' => [0.65, 0.85],
            'targetImprovement' => 0.15,
            'description' => '피부 탄성 회복력 지수',
        ],
        'tone' => [
            'name' => '피부 밝기',
            'unit' => 'L*',
            'baseline' => [58, 68],
            'targetImprovement' => 5,
            'description' => '멜라닌 지수 기반 밝기',
        ],
        'pore' => [
            'name' => '모공 축소율',
            'unit' => '%',
            'baseline' => [0, 0],
            'targetImprovement' => 25,
            'description' => '모공 면적 감소 비율',
        ],
        'soothing' => [
            'name' => '피부 진정도',
            'unit' => '%',
            'baseline' => [0, 0],
            'targetImprovement' => 35,
            'description' => '피부 자극 완화 비율',
        ],
    ];

    public function calculate(Product $product, UserProfile $profile): array
    {
        // 제품의 효능 타입 결정 (기본값: moisture)
        $efficacyType = $product->efficacy_type ?? 'moisture';

        // 제품의 효능 커브 데이터 (없으면 기본 커브 사용)
        $efficacyCurve = $product->efficacy_curve ?? $this->getDefaultEfficacyCurve($efficacyType);

        // 개인화 modifier 계산
        $totalModifier = $this->calculateTotalModifier($profile);

        // 28일 타임라인 계산
        $timeline = $this->calculateTimeline($efficacyCurve, $totalModifier, $efficacyType);

        // 마일스톤 생성
        $milestones = $this->generateMilestones($timeline, $efficacyType, $profile);

        // 비교 데이터 계산
        $comparison = $this->calculateComparison($timeline, $efficacyCurve, $totalModifier);

        // 라이프스타일 영향도 계산
        $lifestyleFactors = $this->calculateLifestyleFactors($profile);

        // 효능 메트릭 계산
        $metrics = $this->calculateEfficacyMetrics($timeline, $efficacyType, $profile, $product);

        // 사용 가이드 생성
        $usageGuide = $this->generateUsageGuide($efficacyType, $profile);

        // 피부 반응 프로파일 요약 생성
        $skinProfile = $this->generateSkinProfile($profile);

        return [
            'efficacy_type' => $efficacyType,
            'timeline' => $timeline,
            'milestones' => $milestones,
            'comparison' => $comparison,
            'metrics' => $metrics,
            'lifestyle_factors' => $lifestyleFactors,
            'usage_guide' => $usageGuide,
            'skin_profile' => $skinProfile,
            'total_modifier' => $totalModifier,
        ];
    }

    /**
     * 기본 효능 커브 반환 (28일 기준)
     */
    private function getDefaultEfficacyCurve(string $efficacyType): array
    {
        // 일자별 기본 효과 퍼센트 [day 1, 3, 7, 14, 21, 28]
        $curves = [
            'moisture' => [5, 15, 30, 50, 70, 85],
            'elasticity' => [3, 10, 22, 40, 60, 75],
            'tone' => [2, 8, 18, 35, 55, 70],
            'pore' => [3, 12, 25, 45, 65, 80],
            'soothing' => [8, 20, 35, 55, 75, 88],
        ];

        return $curves[$efficacyType] ?? $curves['moisture'];
    }

    /**
     * 총 modifier 계산 (DB 우선, 폴백 지원)
     */
    private function calculateTotalModifier(UserProfile $profile): float
    {
        $modifier = 1.0;

        // 연령대
        $modifier *= $this->getModifier('age_group', $profile->age_group ?? '30대', $this->ageModifiers);

        // 생활 습관
        $lifestyle = $profile->lifestyle ?? [];
        $modifier *= $this->getModifier('sleep_hours', $lifestyle['sleep_hours'] ?? '6to8', $this->sleepModifiers);
        $modifier *= $this->getModifier('uv_exposure', $lifestyle['uv_exposure'] ?? 'normal', $this->uvModifiers);
        $modifier *= $this->getModifier('stress_level', $lifestyle['stress_level'] ?? 'medium', $this->stressModifiers);
        $modifier *= $this->getModifier('water_intake', $lifestyle['water_intake'] ?? '1to2L', $this->waterModifiers);

        // 음주/흡연 (개별 필드)
        $modifier *= $this->getModifier('alcohol', $profile->alcohol ?? 'sometimes', $this->alcoholModifiers);
        $modifier *= $this->getModifier('smoking', $profile->smoking ?? 'none', $this->smokingModifiers);

        // 스킨케어 습관
        $careSteps = $profile->skincare_habit['care_steps'] ?? 'basic';
        $modifier *= $this->getModifier('care_steps', $careSteps, $this->careStepsModifiers);

        // 범위 제한 (0.5 ~ 1.5)
        return max(0.5, min(1.5, $modifier));
    }

    /**
     * 28일 타임라인 계산
     */
    private function calculateTimeline(array $baseCurve, float $modifier, string $efficacyType): array
    {
        $timeline = [];

        foreach ($this->days as $index => $day) {
            $baseValue = $baseCurve[$index] ?? 0;
            $personalizedValue = round($baseValue * $modifier, 1);
            $timeline[$day] = min(100, $personalizedValue);
        }

        return $timeline;
    }

    /**
     * 마일스톤 생성
     */
    private function generateMilestones(array $timeline, string $efficacyType, UserProfile $profile): array
    {
        $milestones = [];
        $efficacyNames = Product::$efficacyTypes;
        $efficacyName = $efficacyNames[$efficacyType] ?? '피부 개선';

        $milestoneMessages = [
            'moisture' => [
                1 => '수분 흡수 활성화 시작',
                3 => '피부 장벽 기능 개선 감지',
                7 => '수분 보유력 상승 확인',
                14 => '각질층 수분도 안정화',
                21 => '지속적 보습 효과 정착',
                28 => '최적 수분 밸런스 달성',
            ],
            'elasticity' => [
                1 => '콜라겐 합성 신호 활성화',
                3 => '탄성 섬유 자극 시작',
                7 => '피부 탄력 회복 감지',
                14 => '진피층 밀도 증가 확인',
                21 => '눈에 띄는 탄력 개선',
                28 => '탄탱한 피부결 완성',
            ],
            'tone' => [
                1 => '멜라닌 억제 반응 시작',
                3 => '피부톤 균일화 진행',
                7 => '색소 침착 개선 감지',
                14 => '투명한 피부결 형성',
                21 => '맑은 피부톤 정착',
                28 => '화사한 피부톤 완성',
            ],
            'pore' => [
                1 => '모공 정화 작용 시작',
                3 => '피지 분비 조절 감지',
                7 => '모공 축소 효과 확인',
                14 => '모공 가시성 감소',
                21 => '매끈한 피부결 형성',
                28 => '깨끗한 모공 관리 완성',
            ],
            'soothing' => [
                1 => '진정 성분 흡수 활성화',
                3 => '피부 자극 완화 시작',
                7 => '붉은기 감소 감지',
                14 => '눈에 띄는 진정 효과',
                21 => '피부 안정화 진행',
                28 => '진정 효과 정착',
            ],
        ];

        $messages = $milestoneMessages[$efficacyType] ?? $milestoneMessages['moisture'];

        foreach ($timeline as $day => $value) {
            $prevDay = $this->getPreviousDay($day);
            $prevValue = $prevDay ? ($timeline[$prevDay] ?? 0) : 0;
            $improvement = round($value - $prevValue, 1);

            $milestones[] = [
                'day' => $day,
                'value' => $value,
                'improvement' => $improvement,
                'message' => $this->generateDayMessage($efficacyType, $day, $value, $improvement, $profile),
                'title' => $messages[$day] ?? "{$efficacyName} 진행 중",
            ];
        }

        return $milestones;
    }

    /**
     * 이전 일자 반환
     */
    private function getPreviousDay(int $currentDay): ?int
    {
        $index = array_search($currentDay, $this->days);
        if ($index === false || $index === 0) {
            return null;
        }
        return $this->days[$index - 1];
    }

    /**
     * 일자별 상세 메시지 생성
     */
    private function generateDayMessage(string $efficacyType, int $day, float $value, float $improvement, UserProfile $profile): string
    {
        $ageGroup = $profile->age_group ?? '30대';

        $templates = [
            'moisture' => [
                1 => "피부 수분 흡수율 {$improvement}%p 상승 감지",
                3 => "수분 장벽 기능 {$value}% 수준 회복 중",
                7 => "각질층 수분 함량 {$value}% 도달",
                14 => "{$ageGroup} 평균 대비 우수한 수분 보유력 확인",
                21 => "지속적 보습 효과 {$value}% 달성",
                28 => "최적 피부 수분도 {$value}% 완성",
            ],
            'elasticity' => [
                1 => "콜라겐 합성 촉진 신호 +{$improvement}%p 확인",
                3 => "피부 탄성 회복력 {$value}% 수준",
                7 => "진피층 탄력 섬유 밀도 {$value}% 향상",
                14 => "피부 탄력도 {$value}% - 눈에 띄는 개선 구간",
                21 => "탄력 개선 {$value}% 달성",
                28 => "탄탱한 피부 탄력 {$value}% 완성",
            ],
            'tone' => [
                1 => "멜라닌 생성 억제 반응 {$improvement}%p 시작",
                3 => "피부톤 균일화 {$value}% 진행",
                7 => "색소 침착 개선 {$value}% 확인",
                14 => "투명한 피부톤 {$value}% 형성",
                21 => "맑은 피부톤 {$value}% 정착",
                28 => "화사한 피부톤 {$value}% 완성",
            ],
            'pore' => [
                1 => "모공 정화 작용 {$improvement}%p 활성화",
                3 => "피지 분비량 조절 {$value}% 진행",
                7 => "모공 면적 축소 {$value}% 확인",
                14 => "모공 가시성 {$value}% 감소",
                21 => "매끈한 피부결 {$value}% 형성",
                28 => "깨끗한 모공 관리 {$value}% 완성",
            ],
            'soothing' => [
                1 => "진정 성분 흡수 +{$improvement}%p 활성화",
                3 => "피부 자극 완화 {$value}% 진행",
                7 => "붉은기 감소 {$value}% 확인",
                14 => "눈에 띄는 진정 효과 {$value}%",
                21 => "피부 안정화 {$value}% 진행",
                28 => "진정 효과 {$value}% 완성",
            ],
        ];

        $messages = $templates[$efficacyType] ?? $templates['moisture'];
        return $messages[$day] ?? "효과 {$value}% 진행 중";
    }

    /**
     * 비교 데이터 계산
     */
    private function calculateComparison(array $timeline, array $baseCurve, float $modifier): array
    {
        $finalDay = end($this->days);
        $myFinal = $timeline[$finalDay] ?? 0;
        $avgFinal = end($baseCurve) ?? 0;

        return [
            'average' => $avgFinal,
            'personal' => $myFinal,
            'difference' => round($myFinal - $avgFinal, 1),
            'percentage' => $avgFinal > 0 ? round((($myFinal - $avgFinal) / $avgFinal) * 100, 1) : 0,
            'modifier' => round($modifier, 2),
        ];
    }

    /**
     * 라이프스타일 영향도 계산 (DB 우선, 폴백 지원)
     */
    private function calculateLifestyleFactors(UserProfile $profile): array
    {
        $factors = [];
        $lifestyle = $profile->lifestyle ?? [];

        // 질문 키와 폴백 modifier 매핑
        $questionKeyMap = [
            'sleep' => ['key' => 'sleep_hours', 'fallback' => $this->sleepModifiers],
            'uv' => ['key' => 'uv_exposure', 'fallback' => $this->uvModifiers],
            'stress' => ['key' => 'stress_level', 'fallback' => $this->stressModifiers],
            'water' => ['key' => 'water_intake', 'fallback' => $this->waterModifiers],
            'alcohol' => ['key' => 'alcohol', 'fallback' => $this->alcoholModifiers],
            'smoking' => ['key' => 'smoking', 'fallback' => $this->smokingModifiers],
            'skincare' => ['key' => 'care_steps', 'fallback' => $this->careStepsModifiers],
        ];

        // 각 요소별 영향도 계산
        $factorConfigs = [
            'sleep' => [
                'name' => '수면',
                'value' => $lifestyle['sleep_hours'] ?? '6to8',
                'icon' => 'moon',
                'positive' => ['over8'],
                'negative' => ['under6'],
            ],
            'uv' => [
                'name' => '자외선',
                'value' => $lifestyle['uv_exposure'] ?? 'normal',
                'icon' => 'sun',
                'positive' => ['indoor'],
                'negative' => ['outdoor'],
            ],
            'stress' => [
                'name' => '스트레스',
                'value' => $lifestyle['stress_level'] ?? 'medium',
                'icon' => 'brain',
                'positive' => ['low'],
                'negative' => ['high'],
            ],
            'water' => [
                'name' => '수분 섭취',
                'value' => $lifestyle['water_intake'] ?? '1to2L',
                'icon' => 'droplet',
                'positive' => ['over2L'],
                'negative' => ['under1L'],
            ],
            'alcohol' => [
                'name' => '음주',
                'value' => $profile->alcohol ?? 'sometimes',
                'icon' => 'wine',
                'positive' => ['none'],
                'negative' => ['often'],
            ],
            'smoking' => [
                'name' => '흡연',
                'value' => $profile->smoking ?? 'none',
                'icon' => 'cigarette',
                'positive' => ['none'],
                'negative' => ['often'],
            ],
            'skincare' => [
                'name' => '스킨케어',
                'value' => $profile->skincare_habit['care_steps'] ?? 'basic',
                'icon' => 'sparkles',
                'positive' => ['advanced'],
                'negative' => ['basic'],
            ],
        ];

        foreach ($factorConfigs as $key => $config) {
            $value = $config['value'];
            $questionKey = $questionKeyMap[$key]['key'];
            $fallbackModifiers = $questionKeyMap[$key]['fallback'];

            // DB에서 modifier 가져오기 (폴백 지원)
            $modifier = $this->getModifier($questionKey, $value, $fallbackModifiers);
            $impact = round(($modifier - 1.0) * 100, 0);

            $status = 'neutral';
            if (in_array($value, $config['positive'])) {
                $status = 'positive';
            } elseif (in_array($value, $config['negative'])) {
                $status = 'negative';
            }

            $factors[$key] = [
                'name' => $config['name'],
                'icon' => $config['icon'],
                'value' => $value,
                'modifier' => $modifier,
                'impact' => $impact,
                'status' => $status,
            ];
        }

        return $factors;
    }

    /**
     * 효능 메트릭 계산
     */
    private function calculateEfficacyMetrics(array $timeline, string $efficacyType, UserProfile $profile, ?Product $product = null): array
    {
        // 제품별 설정이 있으면 사용, 없으면 기본값
        $defaultConfig = $this->efficacyMetrics[$efficacyType] ?? $this->efficacyMetrics['moisture'];
        $productMetrics = $product?->efficacy_metrics ?? [];

        $config = [
            'name' => $productMetrics['name'] ?? $defaultConfig['name'],
            'unit' => $productMetrics['unit'] ?? $defaultConfig['unit'],
            'baseline' => isset($productMetrics['baseline_min']) && isset($productMetrics['baseline_max'])
                ? [(float)$productMetrics['baseline_min'], (float)$productMetrics['baseline_max']]
                : $defaultConfig['baseline'],
            'targetImprovement' => isset($productMetrics['target_improvement'])
                ? (float)$productMetrics['target_improvement']
                : $defaultConfig['targetImprovement'],
            'description' => $productMetrics['description'] ?? $defaultConfig['description'],
        ];

        // 나이에 따른 초기값 보정
        $ageIndex = match ($profile->age_group) {
            '10대' => 0.2,
            '20대' => 0.35,
            '30대' => 0.5,
            '40대' => 0.65,
            '50대이상' => 0.8,
            default => 0.5,
        };

        // 초기값 계산
        $baseline = $config['baseline'];
        $initialValue = $baseline[0] + ($baseline[1] - $baseline[0]) * $ageIndex;

        // 28일 후 개선율
        $finalImprovement = ($timeline[28] ?? 0) / 100;
        $improvement = $config['targetImprovement'] * $finalImprovement;
        $finalValue = $initialValue + $improvement;

        // 일자별 값 계산
        $dailyValues = [];
        foreach ($this->days as $day) {
            $dayRate = ($timeline[$day] ?? 0) / 100;
            $dayImprovement = $config['targetImprovement'] * $dayRate;
            $dailyValues[$day] = round($initialValue + $dayImprovement, 2);
        }

        return [
            'efficacy_type' => $efficacyType,
            'name' => $config['name'],
            'unit' => $config['unit'],
            'description' => $config['description'],
            'initial' => round($initialValue, 2),
            'final' => round($finalValue, 2),
            'change' => round($improvement, 2),
            'change_percent' => round(($improvement / max($initialValue, 0.01)) * 100, 1),
            'daily' => $dailyValues,
            'timeline_percent' => $timeline,
        ];
    }

    /**
     * 사용 가이드 생성 (개인화된 수치 기반)
     */
    private function generateUsageGuide(string $efficacyType, UserProfile $profile): array
    {
        // 현재 modifier 계산
        $currentModifier = $this->calculateTotalModifier($profile);

        // 개선 가능한 항목들 분석 및 수치화된 권장사항 생성
        $recommendations = $this->generateQuantifiedRecommendations($profile, $currentModifier, $efficacyType);

        // 사용자 맞춤 최적 사용법 계산
        $optimalUsage = $this->calculateOptimalUsage($efficacyType, $profile, $currentModifier);

        return [
            'optimal_usage' => $optimalUsage,
            'recommendations' => $recommendations,
            'current_modifier' => round($currentModifier, 2),
        ];
    }

    /**
     * 최적 사용법 계산 (수치 기반)
     */
    private function calculateOptimalUsage(string $efficacyType, UserProfile $profile, float $modifier): array
    {
        $lifestyle = $profile->lifestyle ?? [];

        // 사용자 스트레스/수면 상태에 따른 최적 사용 시간대 결정
        $stressLevel = $lifestyle['stress_level'] ?? 'medium';
        $sleepHours = $lifestyle['sleep_hours'] ?? '6to8';

        // 저녁 사용 효과 보정 (피부 재생은 밤에 활발)
        $nightBonus = 15; // 기본 저녁 보너스

        // 스트레스 높으면 저녁 케어 더 중요
        if ($stressLevel === 'high') {
            $nightBonus += 10;
        }

        // 수면 부족하면 저녁 케어로 보완
        if ($sleepHours === 'under6') {
            $nightBonus += 8;
        }

        // 효능별 최적 시간대 (최소 100% 기준, 더 좋은 시간대는 100% 초과)
        $timingConfig = match($efficacyType) {
            'tone' => [
                'best_time' => '저녁',
                'reason' => '자외선 없는 밤 동안 멜라닌 억제 작용 극대화',
                'morning_effect' => 100,
                'evening_effect' => 123,
            ],
            'soothing' => [
                'best_time' => '저녁',
                'reason' => '밤 동안 피부 재생과 진정 작용 극대화',
                'morning_effect' => 100,
                'evening_effect' => 125,
            ],
            'elasticity' => [
                'best_time' => '저녁',
                'reason' => '수면 중 콜라겐 합성 촉진 (성장호르몬 분비 시간대)',
                'morning_effect' => 100,
                'evening_effect' => 131,
            ],
            'moisture' => [
                'best_time' => '아침 & 저녁',
                'reason' => '지속적 수분 공급으로 하루 종일 보습 유지',
                'morning_effect' => 100,
                'evening_effect' => 100,
            ],
            'pore' => [
                'best_time' => '저녁',
                'reason' => '낮 동안 쌓인 피지와 노폐물 제거 후 흡수율 최대',
                'morning_effect' => 100,
                'evening_effect' => 122,
            ],
            default => [
                'best_time' => '저녁',
                'reason' => '피부 재생이 활발한 시간대',
                'morning_effect' => 100,
                'evening_effect' => 118,
            ],
        };

        // 사용 빈도별 효과
        $frequencyEffect = [
            'once_morning' => 45,
            'once_evening' => 60 + ($nightBonus / 3),
            'twice_daily' => 100,
            'twice_plus_weekly_mask' => 115,
        ];

        // 사용량별 효과 (효능별 최적량)
        $amountConfig = match($efficacyType) {
            'moisture' => ['optimal' => '500원 동전 크기 (약 1ml)', 'less_effect' => 60, 'optimal_effect' => 100, 'more_effect' => 105],
            'elasticity' => ['optimal' => '500원 동전 크기 (약 1ml)', 'less_effect' => 55, 'optimal_effect' => 100, 'more_effect' => 102],
            'tone' => ['optimal' => '100원 동전 크기 (약 0.5ml)', 'less_effect' => 65, 'optimal_effect' => 100, 'more_effect' => 100],
            'pore' => ['optimal' => '100원 동전 크기 (약 0.5ml)', 'less_effect' => 70, 'optimal_effect' => 100, 'more_effect' => 95],
            'soothing' => ['optimal' => '500원 동전 크기 (약 1ml)', 'less_effect' => 55, 'optimal_effect' => 100, 'more_effect' => 100],
            default => ['optimal' => '500원 동전 크기', 'less_effect' => 60, 'optimal_effect' => 100, 'more_effect' => 100],
        };

        // 흡수 시간 (효능별)
        $absorptionTime = match($efficacyType) {
            'moisture' => ['seconds' => 30, 'tip' => '가볍게 두드리며 흡수'],
            'elasticity' => ['seconds' => 60, 'tip' => '리프팅 방향으로 마사지하며 흡수'],
            'tone' => ['seconds' => 45, 'tip' => '색소 침착 부위 집중 도포'],
            'pore' => ['seconds' => 30, 'tip' => 'T존 집중, 모공 방향으로 흡수'],
            'soothing' => ['seconds' => 30, 'tip' => '자극 부위에 살짝 두드리며 흡수'],
            default => ['seconds' => 45, 'tip' => '부드럽게 두드리며 흡수'],
        };

        // 예상 체감 시점 계산 (modifier 반영)
        $baseFeelDays = match($efficacyType) {
            'moisture' => 3,
            'elasticity' => 10,
            'tone' => 7,
            'pore' => 5,
            'soothing' => 3,
            default => 7,
        };
        $adjustedFeelDays = max(1, round($baseFeelDays / $modifier));

        return [
            'timing' => [
                'best' => $timingConfig['best_time'],
                'reason' => $timingConfig['reason'],
                'morning_effect' => $timingConfig['morning_effect'],
                'evening_effect' => $timingConfig['evening_effect'],
            ],
            'frequency' => [
                'recommended' => '아침 & 저녁 2회',
                'once_effect' => round($frequencyEffect['once_evening']),
                'twice_effect' => $frequencyEffect['twice_daily'],
                'with_mask_effect' => round($frequencyEffect['twice_plus_weekly_mask']),
            ],
            'amount' => [
                'optimal' => $amountConfig['optimal'],
                'less_effect' => $amountConfig['less_effect'],
                'optimal_effect' => $amountConfig['optimal_effect'],
            ],
            'absorption' => [
                'time' => $absorptionTime['seconds'],
                'tip' => $absorptionTime['tip'],
            ],
            'expected_feel_days' => $adjustedFeelDays,
        ];
    }

    /**
     * 수치화된 개선 권장사항 생성 (DB 우선, 폴백 지원)
     */
    private function generateQuantifiedRecommendations(UserProfile $profile, float $currentModifier, string $efficacyType): array
    {
        $recommendations = [];
        $lifestyle = $profile->lifestyle ?? [];

        // 질문 키와 폴백 modifier 매핑
        $questionConfigs = [
            'sleep' => ['key' => 'sleep_hours', 'fallback' => $this->sleepModifiers, 'optimalFallback' => 1.15],
            'uv' => ['key' => 'uv_exposure', 'fallback' => $this->uvModifiers, 'optimalFallback' => 1.15],
            'stress' => ['key' => 'stress_level', 'fallback' => $this->stressModifiers, 'optimalFallback' => 1.1],
            'water' => ['key' => 'water_intake', 'fallback' => $this->waterModifiers, 'optimalFallback' => 1.15],
            'alcohol' => ['key' => 'alcohol', 'fallback' => $this->alcoholModifiers, 'optimalFallback' => 1.15],
            'smoking' => ['key' => 'smoking', 'fallback' => $this->smokingModifiers, 'optimalFallback' => 1.15],
        ];

        // DB에서 modifier 맵 가져오기
        $dbModifiers = $this->loadDbModifiers();

        // 각 질문의 최적(최대) modifier 계산
        $getOptimalModifier = function($questionKey, $fallbackOptimal) use ($dbModifiers) {
            if (isset($dbModifiers[$questionKey]) && !empty($dbModifiers[$questionKey])) {
                return max($dbModifiers[$questionKey]);
            }
            return $fallbackOptimal;
        };

        // 각 요소별 현재값과 최적값 비교하여 개선 가능한 항목 찾기
        $improvementFactors = [
            [
                'key' => 'sleep',
                'current' => $lifestyle['sleep_hours'] ?? '6to8',
                'currentModifier' => $this->getModifier('sleep_hours', $lifestyle['sleep_hours'] ?? '6to8', $this->sleepModifiers),
                'optimalValue' => 'over8',
                'optimalModifier' => $getOptimalModifier('sleep_hours', 1.15),
                'icon' => '😴',
                'action' => '수면 시간을 8시간 이상으로 늘리면',
                'actionShort' => '충분한 수면 (8시간 이상)',
            ],
            [
                'key' => 'uv',
                'current' => $lifestyle['uv_exposure'] ?? 'normal',
                'currentModifier' => $this->getModifier('uv_exposure', $lifestyle['uv_exposure'] ?? 'normal', $this->uvModifiers),
                'optimalValue' => 'indoor',
                'optimalModifier' => $getOptimalModifier('uv_exposure', 1.15),
                'icon' => '☀️',
                'action' => '자외선 차단제를 꼼꼼히 바르면',
                'actionShort' => '자외선 차단제 사용',
            ],
            [
                'key' => 'stress',
                'current' => $lifestyle['stress_level'] ?? 'medium',
                'currentModifier' => $this->getModifier('stress_level', $lifestyle['stress_level'] ?? 'medium', $this->stressModifiers),
                'optimalValue' => 'low',
                'optimalModifier' => $getOptimalModifier('stress_level', 1.1),
                'icon' => '🧘',
                'action' => '스트레스 관리(명상, 운동 등)를 하면',
                'actionShort' => '스트레스 관리',
            ],
            [
                'key' => 'water',
                'current' => $lifestyle['water_intake'] ?? '1to2L',
                'currentModifier' => $this->getModifier('water_intake', $lifestyle['water_intake'] ?? '1to2L', $this->waterModifiers),
                'optimalValue' => 'over2L',
                'optimalModifier' => $getOptimalModifier('water_intake', 1.15),
                'icon' => '💧',
                'action' => '하루 물 섭취량을 2L 이상으로 늘리면',
                'actionShort' => '수분 섭취 (2L 이상)',
            ],
            [
                'key' => 'alcohol',
                'current' => $profile->alcohol ?? 'sometimes',
                'currentModifier' => $this->getModifier('alcohol', $profile->alcohol ?? 'sometimes', $this->alcoholModifiers),
                'optimalValue' => 'none',
                'optimalModifier' => $getOptimalModifier('alcohol', 1.15),
                'icon' => '🍷',
                'action' => '음주를 줄이면',
                'actionShort' => '음주 절제',
            ],
            [
                'key' => 'smoking',
                'current' => $profile->smoking ?? 'none',
                'currentModifier' => $this->getModifier('smoking', $profile->smoking ?? 'none', $this->smokingModifiers),
                'optimalValue' => 'none',
                'optimalModifier' => $getOptimalModifier('smoking', 1.15),
                'icon' => '🚭',
                'action' => '금연하면',
                'actionShort' => '금연',
            ],
        ];

        // 기본 28일 효과 (현재 조건 기준)
        $baseTimeline = 28;

        foreach ($improvementFactors as $factor) {
            // 이미 최적 상태이면 건너뛰기
            if ($factor['currentModifier'] >= $factor['optimalModifier']) {
                continue;
            }

            // 개선 가능한 효과 계산
            $improvementRatio = $factor['optimalModifier'] / $factor['currentModifier'];
            $effectBoost = round(($improvementRatio - 1) * 100, 0);

            // 효과 도달 시점 단축 계산 (대략적인 추정)
            // modifier가 높을수록 같은 효과에 더 빨리 도달
            $daysSaved = round($baseTimeline * (1 - (1 / $improvementRatio)), 0);
            $daysSaved = max(1, min($daysSaved, 7)); // 1~7일 범위로 제한

            if ($effectBoost >= 5) { // 5% 이상 개선 가능한 경우만 권장
                $recommendations[] = [
                    'icon' => $factor['icon'],
                    'action' => $factor['action'],
                    'action_short' => $factor['actionShort'],
                    'effect_boost' => $effectBoost,
                    'days_saved' => $daysSaved,
                    'description' => "{$factor['action']} 효과가 약 {$effectBoost}% 향상되고, 체감 시점이 약 {$daysSaved}일 단축됩니다.",
                    'priority' => $effectBoost, // 정렬용
                ];
            }
        }

        // 효과가 큰 순서로 정렬
        usort($recommendations, fn($a, $b) => $b['priority'] - $a['priority']);

        // 상위 3개만 반환
        return array_slice($recommendations, 0, 3);
    }

    /**
     * 피부 반응 프로파일 요약 생성
     */
    private function generateSkinProfile(UserProfile $profile): array
    {
        $lifestyle = $profile->lifestyle ?? [];
        $characteristics = [];

        // 1. 재생 속도 판단 (수면, 나이, 스트레스 기반)
        $regenerationScore = 0;
        $sleepHours = $lifestyle['sleep_hours'] ?? '6to8';
        $regenerationScore += match($sleepHours) {
            'over8' => 2,
            '6to8' => 0,
            'under6' => -2,
            default => 0,
        };

        $regenerationScore += match($profile->age_group) {
            '10대', '20대' => 2,
            '30대' => 0,
            '40대' => -1,
            '50대이상' => -2,
            default => 0,
        };

        $stressLevel = $lifestyle['stress_level'] ?? 'medium';
        $regenerationScore += match($stressLevel) {
            'low' => 1,
            'medium' => 0,
            'high' => -2,
            default => 0,
        };

        // score를 1-5 level로 변환 (score 범위: -5 ~ +5 → level 범위: 1 ~ 5)
        $regenerationLevel = max(1, min(5, round(($regenerationScore + 5) / 2)));

        $characteristics['regeneration'] = [
            'label' => '재생 속도',
            'score' => $regenerationScore,
            'level' => $regenerationLevel,
            'description' => match($regenerationLevel) {
                5 => '매우 빠른 편',
                4 => '빠른 편',
                3 => '보통',
                2 => '느린 편',
                default => '매우 느린 편',
            },
            'status' => $regenerationLevel >= 4 ? 'positive' : ($regenerationLevel >= 2 ? 'neutral' : 'negative'),
        ];

        // 2. 수분 유지력 판단 (수분 섭취, 음주, 스킨케어 기반)
        $moistureScore = 0;
        $waterIntake = $lifestyle['water_intake'] ?? '1to2L';
        $moistureScore += match($waterIntake) {
            'over2L' => 2,
            '1to2L' => 0,
            'under1L' => -2,
            default => 0,
        };

        $alcohol = $profile->alcohol ?? 'sometimes';
        $moistureScore += match($alcohol) {
            'none' => 2,
            'sometimes' => 0,
            'often' => -2,
            default => 0,
        };

        $careSteps = $profile->skincare_habit['care_steps'] ?? 'basic';
        $moistureScore += match($careSteps) {
            'advanced' => 2,
            'basic' => 0,
            default => 0,
        };

        // score를 1-5 level로 변환 (score 범위: -6 ~ +6 → level 범위: 1 ~ 5)
        $moistureLevel = max(1, min(5, round(($moistureScore + 6) / 2.4)));

        $characteristics['moisture_retention'] = [
            'label' => '수분 유지력',
            'score' => $moistureScore,
            'level' => $moistureLevel,
            'description' => match($moistureLevel) {
                5 => '매우 높은 편',
                4 => '높은 편',
                3 => '보통',
                2 => '낮은 편',
                default => '매우 낮은 편',
            },
            'status' => $moistureLevel >= 4 ? 'positive' : ($moistureLevel >= 2 ? 'neutral' : 'negative'),
        ];

        // 3. 색소 반응성 판단 (자외선 노출, 스트레스 기반)
        $pigmentScore = 0;
        $uvExposure = $lifestyle['uv_exposure'] ?? 'normal';
        $pigmentScore += match($uvExposure) {
            'outdoor' => 2,
            'normal' => 1,
            'indoor' => -1,
            default => 0,
        };

        $pigmentScore += match($stressLevel) {
            'high' => 1,
            'medium' => 0,
            'low' => -1,
            default => 0,
        };

        // 나이도 색소 반응성에 영향
        $pigmentScore += match($profile->age_group) {
            '40대', '50대이상' => 1,
            '30대' => 0,
            default => -1,
        };

        // score를 1-5 level로 변환 (score 범위: -3 ~ +4 → level 범위: 1 ~ 5)
        $pigmentLevel = max(1, min(5, round(($pigmentScore + 3) / 1.4)));

        $characteristics['pigment_reactivity'] = [
            'label' => '색소 반응성',
            'score' => $pigmentScore,
            'level' => $pigmentLevel,
            'description' => match($pigmentLevel) {
                5 => '매우 높은 편',
                4 => '높은 편',
                3 => '보통',
                2 => '낮은 편',
                default => '매우 낮은 편',
            },
            'status' => $pigmentLevel <= 2 ? 'positive' : ($pigmentLevel <= 4 ? 'neutral' : 'negative'),
        ];

        // 4. 민감도 판단 (자극 관련 - 흡연, 스트레스, 수면 기반)
        $sensitivityScore = 0;
        $smoking = $profile->smoking ?? 'none';
        $sensitivityScore += match($smoking) {
            'often' => 2,
            'sometimes' => 1,
            'none' => -1,
            default => 0,
        };

        $sensitivityScore += match($stressLevel) {
            'high' => 2,
            'medium' => 0,
            'low' => -1,
            default => 0,
        };

        $sensitivityScore += match($sleepHours) {
            'under6' => 2,
            '6to8' => 0,
            'over8' => -1,
            default => 0,
        };

        // score를 1-5 level로 변환 (score 범위: -3 ~ +6 → level 범위: 1 ~ 5)
        $sensitivityLevel = max(1, min(5, round(($sensitivityScore + 3) / 1.8)));

        $characteristics['sensitivity'] = [
            'label' => '피부 민감도',
            'score' => $sensitivityScore,
            'level' => $sensitivityLevel,
            'description' => match($sensitivityLevel) {
                5 => '매우 민감',
                4 => '민감한 편',
                3 => '보통',
                2 => '안정적인 편',
                default => '매우 안정적',
            },
            'status' => $sensitivityLevel <= 2 ? 'positive' : ($sensitivityLevel <= 4 ? 'neutral' : 'negative'),
        ];

        // 종합 요약 문장 생성
        $summary = "당신의 피부는 재생 속도는 {$characteristics['regeneration']['description']} "
            . "수분 유지력은 {$characteristics['moisture_retention']['description']} "
            . "색소 반응성은 {$characteristics['pigment_reactivity']['description']}";

        return [
            'characteristics' => $characteristics,
            'summary' => $summary,
        ];
    }
}
