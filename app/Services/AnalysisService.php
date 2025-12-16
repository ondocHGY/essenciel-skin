<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SurveyOptionCategory;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Cache;

class AnalysisService
{
    private ?array $dbModifiers = null;

    // 하드코딩 fallback 값들 (DB에 값이 없을 경우 사용)
    private array $fallbackAgeModifiers = [
        '10대' => 1.2,
        '20대초반' => 1.15,
        '20대후반' => 1.1,
        '30대' => 1.0,
        '40대' => 0.85,
        '50대이상' => 0.7,
    ];

    private array $fallbackSkinTypeModifiers = [
        '중성' => 1.1,
        '지성' => 1.0,
        '건성' => 0.95,
        '복합성' => 0.9,
        '민감성' => 0.8,
    ];

    private array $fallbackConsistencyModifiers = [
        'always' => 1.3,
        'regular' => 1.0,
        'sometimes' => 0.6,
    ];

    private array $fallbackLifestyleModifiers = [
        'sleep_hours' => ['under6' => 0.85, '6to8' => 1.0, 'over8' => 1.1],
        'uv_exposure' => ['indoor' => 1.1, 'normal' => 1.0, 'outdoor' => 0.85],
        'stress_levels' => ['low' => 1.1, 'medium' => 1.0, 'high' => 0.85],
        'water_intake' => ['under1L' => 0.9, '1to2L' => 1.0, 'over2L' => 1.1],
        'smoking_drinking' => ['none' => 1.1, 'sometimes' => 1.0, 'often' => 0.85],
    ];

    // 피부 측정 지표 기준값 및 단위
    private array $skinMetrics = [
        'moisture' => [
            'name' => '피부 수분량',
            'unit' => '%',
            'baseline' => [35, 55], // 시작 범위 (낮음~높음)
            'maxImprovement' => 25, // 최대 개선 가능량
            'description' => '각질층 수분 함유량',
        ],
        'elasticity' => [
            'name' => '콜라겐 밀도',
            'unit' => 'mg/cm²',
            'baseline' => [1.8, 2.8],
            'maxImprovement' => 0.9,
            'description' => '진피층 콜라겐 함량',
        ],
        'tone' => [
            'name' => '멜라닌 지수',
            'unit' => 'M.I',
            'baseline' => [180, 280], // 높을수록 어두움 (감소가 개선)
            'maxImprovement' => -80, // 음수 = 감소가 좋음
            'description' => '피부 색소 침착도',
            'inverse' => true,
        ],
        'pore' => [
            'name' => '모공 면적',
            'unit' => 'mm²',
            'baseline' => [0.8, 1.6],
            'maxImprovement' => -0.5, // 음수 = 감소가 좋음
            'description' => '평균 모공 크기',
            'inverse' => true,
        ],
        'wrinkle' => [
            'name' => '주름 깊이',
            'unit' => 'μm',
            'baseline' => [45, 120],
            'maxImprovement' => -35, // 음수 = 감소가 좋음
            'description' => '평균 주름 깊이',
            'inverse' => true,
        ],
    ];

    /**
     * DB에서 modifier 값들을 로드 (캐싱 적용)
     */
    private function loadModifiers(): array
    {
        if ($this->dbModifiers !== null) {
            return $this->dbModifiers;
        }

        $this->dbModifiers = Cache::remember('survey_modifiers', 3600, function () {
            return SurveyOptionCategory::getAllModifiers();
        });

        return $this->dbModifiers;
    }

    /**
     * 특정 카테고리의 modifier 값 가져오기
     */
    private function getModifier(string $category, string $value, float $fallback = 1.0): float
    {
        $modifiers = $this->loadModifiers();
        return $modifiers[$category][$value] ?? $fallback;
    }

    /**
     * 연령대 modifier 가져오기
     */
    private function getAgeModifier(string $ageGroup): float
    {
        $modifier = $this->getModifier('age_groups', $ageGroup);
        if ($modifier !== 1.0) {
            return $modifier;
        }
        return $this->fallbackAgeModifiers[$ageGroup] ?? 1.0;
    }

    /**
     * 피부타입 modifier 가져오기
     */
    private function getSkinTypeModifier(string $skinType): float
    {
        $modifier = $this->getModifier('skin_types', $skinType);
        if ($modifier !== 1.0) {
            return $modifier;
        }
        return $this->fallbackSkinTypeModifiers[$skinType] ?? 1.0;
    }

    /**
     * 규칙성 modifier 가져오기
     */
    private function getConsistencyModifier(string $consistency): float
    {
        $modifier = $this->getModifier('consistency_options', $consistency);
        if ($modifier !== 1.0) {
            return $modifier;
        }
        return $this->fallbackConsistencyModifiers[$consistency] ?? 1.0;
    }

    /**
     * 생활환경 modifier 가져오기
     */
    private function getLifestyleOptionModifier(string $category, string $value): float
    {
        $modifier = $this->getModifier($category, $value);
        if ($modifier !== 1.0) {
            return $modifier;
        }
        return $this->fallbackLifestyleModifiers[$category][$value] ?? 1.0;
    }

    public function calculate(Product $product, UserProfile $profile): array
    {
        $baseCurve = $product->base_curve;

        // DB modifier 사용 (fallback 로직 포함)
        $ageModifier = $this->getAgeModifier($profile->age_group);
        $skinTypeModifier = $this->getSkinTypeModifier($profile->skin_type);
        $consistencyModifier = $this->getConsistencyModifier($profile->skincare_habit['consistency'] ?? 'regular');
        $lifestyleModifier = $this->calculateLifestyleModifier($profile->lifestyle);
        $concernMatch = $this->calculateConcernMatch($profile->concerns, $baseCurve);

        $timeline = $this->calculateTimeline(
            $baseCurve,
            $ageModifier,
            $skinTypeModifier,
            $lifestyleModifier,
            $consistencyModifier,
            $concernMatch
        );

        $milestones = $this->generateMilestones($timeline, $profile);
        $comparison = $this->calculateComparison($timeline, $baseCurve);

        // 정량적 피부 측정 지표 계산
        $quantitativeMetrics = $this->calculateQuantitativeMetrics($timeline, $profile);

        return [
            'timeline' => $timeline,
            'milestones' => $milestones,
            'comparison' => $comparison,
            'metrics' => $quantitativeMetrics,
        ];
    }

    /**
     * 정량적 피부 측정 지표 계산
     */
    private function calculateQuantitativeMetrics(array $timeline, UserProfile $profile): array
    {
        $metrics = [];

        // 사용자 프로필에 따른 초기값 보정
        $ageIndex = match ($profile->age_group) {
            '10대' => 0.2,
            '20대초반' => 0.3,
            '20대후반' => 0.4,
            '30대' => 0.5,
            '40대' => 0.7,
            '50대이상' => 0.85,
            default => 0.5,
        };

        // 피부 타입에 따른 초기값 보정
        $skinTypeIndex = match ($profile->skin_type) {
            '중성' => 0.3,
            '지성' => 0.4,
            '건성' => 0.6,
            '복합성' => 0.5,
            '민감성' => 0.7,
            default => 0.5,
        };

        foreach ($this->skinMetrics as $category => $config) {
            $baselineRange = $config['baseline'];
            $isInverse = $config['inverse'] ?? false;

            // 초기값 계산 (나이, 피부타입에 따라)
            $baselineIndex = ($ageIndex + $skinTypeIndex) / 2;
            $initialValue = $baselineRange[0] + ($baselineRange[1] - $baselineRange[0]) * $baselineIndex;

            // 12주 후 개선율 (timeline 기반)
            $improvementRate = ($timeline[$category][12] ?? 0) / 100;
            $improvement = $config['maxImprovement'] * $improvementRate;

            $finalValue = $initialValue + $improvement;

            // 주차별 값 계산
            $weeklyValues = [];
            $weeks = [1, 2, 4, 8, 12];
            foreach ($weeks as $week) {
                $weekRate = ($timeline[$category][$week] ?? 0) / 100;
                $weekImprovement = $config['maxImprovement'] * $weekRate;
                $weeklyValues[$week] = round($initialValue + $weekImprovement, 2);
            }

            // 레이더 차트용 정규화 점수 (0-100)
            // 각 지표의 "좋은 상태"를 100으로 정규화
            $radarScore = $this->calculateRadarScore($category, $initialValue, $finalValue, $config);

            $metrics[$category] = [
                'name' => $config['name'],
                'unit' => $config['unit'],
                'description' => $config['description'],
                'initial' => round($initialValue, 2),
                'final' => round($finalValue, 2),
                'change' => round($improvement, 2),
                'changeText' => $this->formatChange($improvement, $config['unit'], $isInverse),
                'isImprovement' => $isInverse ? $improvement < 0 : $improvement > 0,
                'weekly' => $weeklyValues,
                'radarBefore' => $radarScore['before'],
                'radarAfter' => $radarScore['after'],
            ];
        }

        return $metrics;
    }

    /**
     * 레이더 차트용 정규화 점수 계산 (0-100)
     */
    private function calculateRadarScore(string $category, float $initial, float $final, array $config): array
    {
        $isInverse = $config['inverse'] ?? false;
        $baseline = $config['baseline'];
        $maxImprovement = abs($config['maxImprovement']);

        // 각 지표의 최악~최상 범위 정의
        $ranges = [
            'moisture' => ['worst' => 25, 'best' => 70],      // 수분: 25% ~ 70%
            'elasticity' => ['worst' => 1.5, 'best' => 3.5],  // 콜라겐: 1.5 ~ 3.5 mg/cm²
            'tone' => ['worst' => 320, 'best' => 120],        // 멜라닌: 320 ~ 120 (낮을수록 좋음)
            'pore' => ['worst' => 2.0, 'best' => 0.5],        // 모공: 2.0 ~ 0.5 mm² (낮을수록 좋음)
            'wrinkle' => ['worst' => 150, 'best' => 30],      // 주름: 150 ~ 30 μm (낮을수록 좋음)
        ];

        $range = $ranges[$category] ?? ['worst' => 0, 'best' => 100];

        if ($isInverse) {
            // 낮을수록 좋은 지표 (멜라닌, 모공, 주름)
            $beforeScore = 100 - (($initial - $range['best']) / ($range['worst'] - $range['best']) * 100);
            $afterScore = 100 - (($final - $range['best']) / ($range['worst'] - $range['best']) * 100);
        } else {
            // 높을수록 좋은 지표 (수분, 탄력)
            $beforeScore = (($initial - $range['worst']) / ($range['best'] - $range['worst'])) * 100;
            $afterScore = (($final - $range['worst']) / ($range['best'] - $range['worst'])) * 100;
        }

        return [
            'before' => max(0, min(100, round($beforeScore, 1))),
            'after' => max(0, min(100, round($afterScore, 1))),
        ];
    }

    /**
     * 변화량 텍스트 포맷
     */
    private function formatChange(float $change, string $unit, bool $isInverse): string
    {
        $absChange = abs($change);
        $prefix = $change >= 0 ? '+' : '';

        if ($isInverse) {
            // 감소가 좋은 지표
            return $prefix . round($change, 1) . $unit;
        }

        return $prefix . round($change, 1) . $unit;
    }

    private function calculateLifestyleModifier(array $lifestyle): float
    {
        $score = 1.0;

        // DB modifier 사용 (fallback 로직 포함)
        // 수면 시간
        $score *= $this->getLifestyleOptionModifier('sleep_hours', $lifestyle['sleep_hours'] ?? '6to8');

        // 자외선 노출
        $score *= $this->getLifestyleOptionModifier('uv_exposure', $lifestyle['uv_exposure'] ?? 'normal');

        // 스트레스 레벨
        $score *= $this->getLifestyleOptionModifier('stress_levels', $lifestyle['stress_level'] ?? 'medium');

        // 수분 섭취
        $score *= $this->getLifestyleOptionModifier('water_intake', $lifestyle['water_intake'] ?? '1to2L');

        // 음주/흡연
        $score *= $this->getLifestyleOptionModifier('smoking_drinking', $lifestyle['smoking_drinking'] ?? 'none');

        // 0.75 ~ 1.15 범위로 제한
        return max(0.75, min(1.15, $score));
    }

    private function calculateConcernMatch(array $concerns, array $baseCurve): float
    {
        $concernMapping = [
            'wrinkle' => 'wrinkle',
            'elasticity' => 'elasticity',
            'pigmentation' => 'tone',
            'pore' => 'pore',
            'acne' => 'pore',
            'dryness' => 'moisture',
            'redness' => 'tone',
            'dullness' => 'tone',
        ];

        $matchedCategories = [];
        foreach ($concerns as $concern) {
            if (isset($concernMapping[$concern])) {
                $matchedCategories[$concernMapping[$concern]] = true;
            }
        }

        $matchCount = count($matchedCategories);
        $totalCategories = count(array_keys($baseCurve));

        // 관심 고민과 제품 효과가 많이 일치할수록 보정값 증가
        return 0.9 + ($matchCount / $totalCategories) * 0.2;
    }

    private function calculateTimeline(
        array $baseCurve,
        float $ageModifier,
        float $skinTypeModifier,
        float $lifestyleModifier,
        float $consistencyModifier,
        float $concernMatch
    ): array {
        $timeline = [];
        $weeks = [1, 2, 4, 8, 12];
        $totalModifier = $ageModifier * $skinTypeModifier * $lifestyleModifier * $consistencyModifier * $concernMatch;

        foreach ($baseCurve as $category => $values) {
            $timeline[$category] = [];
            foreach ($values as $index => $value) {
                $personalizedValue = round($value * $totalModifier, 1);
                // 최대 100으로 제한
                $timeline[$category][$weeks[$index]] = min(100, $personalizedValue);
            }
        }

        return $timeline;
    }

    private function generateMilestones(array $timeline, UserProfile $profile): array
    {
        $milestones = [];
        $weeks = [1, 2, 4, 8, 12];

        // 각 주차별로 가장 개선율이 높은 카테고리 하나만 선택
        foreach ($weeks as $week) {
            $bestCategory = null;
            $bestValue = 0;
            $bestImprovement = 0;

            foreach ($timeline as $category => $weeklyValues) {
                if (isset($weeklyValues[$week])) {
                    $currentValue = $weeklyValues[$week];
                    // 이전 주차 대비 개선량 계산
                    $prevWeekIndex = array_search($week, $weeks);
                    $prevWeek = $prevWeekIndex > 0 ? $weeks[$prevWeekIndex - 1] : 0;
                    $prevValue = $prevWeek > 0 ? ($weeklyValues[$prevWeek] ?? 0) : 0;
                    $improvement = $currentValue - $prevValue;

                    // 해당 주차에서 가장 큰 개선을 보인 카테고리 선택
                    if ($improvement > $bestImprovement || ($improvement == $bestImprovement && $currentValue > $bestValue)) {
                        $bestCategory = $category;
                        $bestValue = $currentValue;
                        $bestImprovement = $improvement;
                    }
                }
            }

            if ($bestCategory && $bestValue > 0) {
                $milestones[] = [
                    'category' => $bestCategory,
                    'week' => $week,
                    'value' => $bestValue,
                    'improvement' => round($bestImprovement, 1),
                    'message' => $this->generateDataDrivenMessage($bestCategory, $week, $bestValue, $bestImprovement, $profile),
                ];
            }
        }

        return $milestones;
    }

    /**
     * 데이터 기반 분석적 마일스톤 메시지 생성
     */
    private function generateDataDrivenMessage(string $category, int $week, float $value, float $improvement, UserProfile $profile): string
    {
        $categoryNames = [
            'moisture' => '수분',
            'elasticity' => '탄력',
            'tone' => '피부톤',
            'pore' => '모공',
            'wrinkle' => '주름',
        ];

        $categoryName = $categoryNames[$category] ?? $category;
        $skinType = $profile->skin_type ?? '복합성';
        $ageGroup = $profile->age_group ?? '30대';

        // 주차별 + 카테고리별 데이터 기반 메시지
        $messages = [
            1 => [
                'moisture' => "{$skinType} 피부 수분 흡수율 {$improvement}%p 상승 감지",
                'elasticity' => "콜라겐 합성 촉진 신호 +{$improvement}%p 확인",
                'tone' => "멜라닌 억제 반응 {$improvement}%p 개선 시작",
                'pore' => "피지 분비량 조절 효과 {$improvement}%p 감지",
                'wrinkle' => "표피 재생 사이클 활성화 +{$improvement}%p",
            ],
            2 => [
                'moisture' => "각질층 수분 보유력 {$value}% 도달 (평균 대비 +" . round($improvement) . "%p)",
                'elasticity' => "진피층 탄성 섬유 밀도 {$value}% 수준 측정",
                'tone' => "{$ageGroup} 평균 대비 피부톤 균일도 {$improvement}%p 개선",
                'pore' => "모공 수축 효과 {$value}% 진행 확인",
                'wrinkle' => "미세주름 깊이 감소율 {$value}% 달성",
            ],
            4 => [
                'moisture' => "수분 장벽 기능 {$value}% 회복 - 최적 수분 밸런스 근접",
                'elasticity' => "피부 탄력도 {$value}% 도달, 눈에 띄는 개선 구간 진입",
                'tone' => "색소 침착 개선 {$value}% - 맑은 피부톤 형성 중",
                'pore' => "모공 가시성 {$value}% 감소, 피부결 개선 뚜렷",
                'wrinkle' => "주름 개선 효과 {$value}% - {$ageGroup} 상위 그룹 수준",
            ],
            8 => [
                'moisture' => "장기 수분 유지력 {$value}% 안정화 - 건강한 피부 상태",
                'elasticity' => "탄력 개선 {$value}% 달성, 처짐 방지 효과 극대화",
                'tone' => "피부톤 균일도 {$value}% - 투명한 피부 완성 단계",
                'pore' => "모공 관리 {$value}% 효과, 매끈한 피부결 유지",
                'wrinkle' => "주름 완화 {$value}% 도달 - 동안 피부 효과",
            ],
            12 => [
                'moisture' => "최종 수분도 {$value}% 달성 - 이상적인 피부 컨디션",
                'elasticity' => "탄력 개선 목표 {$value}% 달성 - 탱탱한 피부 완성",
                'tone' => "피부톤 개선 {$value}% 완료 - 맑고 화사한 피부",
                'pore' => "모공 케어 {$value}% 달성 - 결점 없는 피부결",
                'wrinkle' => "주름 개선 {$value}% 완료 - 젊고 건강한 피부",
            ],
        ];

        return $messages[$week][$category] ?? "{$categoryName} 개선 {$value}% 달성";
    }

    private function getMilestoneMessage(string $category, int $threshold): string
    {
        $messages = [
            'moisture' => [
                10 => '피부 수분 공급 시작',
                30 => '수분 밸런스 개선 중',
                50 => '촉촉한 피부 유지',
            ],
            'elasticity' => [
                10 => '탄력 개선 시작',
                30 => '피부 탄력 향상 중',
                50 => '눈에 띄는 탄력 개선',
            ],
            'tone' => [
                10 => '피부톤 정돈 시작',
                30 => '피부톤 균일화 진행',
                50 => '맑고 화사한 피부톤',
            ],
            'pore' => [
                10 => '모공 케어 시작',
                30 => '모공 축소 진행',
                50 => '깨끗한 모공 관리',
            ],
            'wrinkle' => [
                10 => '주름 케어 시작',
                30 => '잔주름 개선 중',
                50 => '눈에 띄는 주름 개선',
            ],
        ];

        return $messages[$category][$threshold] ?? "{$category} {$threshold}% 달성";
    }

    private function calculateComparison(array $timeline, array $baseCurve): array
    {
        $comparison = [];

        foreach ($timeline as $category => $weeklyValues) {
            $myFinal = end($weeklyValues);
            $avgFinal = end($baseCurve[$category]);

            $comparison[$category] = [
                'average' => $avgFinal,
                'personal' => $myFinal,
                'difference' => round($myFinal - $avgFinal, 1),
                'percentage' => $avgFinal > 0 ? round((($myFinal - $avgFinal) / $avgFinal) * 100, 1) : 0,
            ];
        }

        return $comparison;
    }
}
