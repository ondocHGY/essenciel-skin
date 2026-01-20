<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'code',
        'name',
        'brand',
        'category',
        'efficacy_type',
        'efficacy_curve',
        'efficacy_phases',
        'efficacy_milestones',
        'milestone_center_texts',
        'efficacy_metrics',
        'optimal_timing',
        'intro_metrics',
        'intro_summary',
        'intro_review_count',
        'image',
        'ingredient_details',
        'nanoliposome_info',
        'technology_section',
        'sci_section',
        'base_curve',
        'qr_path',
        'point_color',
        'accent_color',
    ];

    protected $casts = [
        'ingredient_details' => 'array',
        'nanoliposome_info' => 'array',
        'technology_section' => 'array',
        'sci_section' => 'array',
        'efficacy_curve' => 'array',
        'efficacy_phases' => 'array',
        'efficacy_milestones' => 'array',
        'milestone_center_texts' => 'array',
        'efficacy_metrics' => 'array',
        'optimal_timing' => 'array',
        'intro_metrics' => 'array',
        'intro_summary' => 'array',
        'base_curve' => 'array',
    ];

    /**
     * 효능 단계별 기본 설명 반환
     */
    public function getEfficacyPhaseDescriptions(): array
    {
        if ($this->efficacy_phases) {
            return $this->efficacy_phases;
        }

        // 기본값
        $defaults = [
            'moisture' => [
                'phase1' => '유효 성분이 피부에 전달되며, 수분 흡수 준비 단계에 들어갑니다.',
                'phase2' => '피부 수분도 변화가 느껴지기 시작하며, 건조함이 점차 완화됩니다.',
                'phase3' => '수분 밸런스 효과가 안정화되며, 촉촉한 피부가 유지되는 단계입니다.',
            ],
            'elasticity' => [
                'phase1' => '유효 성분이 피부에 전달되며, 콜라겐 합성 촉진 준비 단계에 들어갑니다.',
                'phase2' => '피부 탄력 변화가 느껴지기 시작하며, 처짐이 점차 개선됩니다.',
                'phase3' => '탄력 개선 효과가 안정화되며, 탱탱한 피부가 유지되는 단계입니다.',
            ],
            'tone' => [
                'phase1' => '유효 성분이 피부에 전달되며, 멜라닌 생성 신호를 완화할 준비 단계에 들어갑니다.',
                'phase2' => '피부 톤 변화가 눈으로 느껴지기 시작하며, 칙칙함이 점차 완화됩니다.',
                'phase3' => '색소 완화 효과가 안정화되며, 균일한 톤이 유지되는 단계입니다.',
            ],
            'pore' => [
                'phase1' => '유효 성분이 피부에 전달되며, 모공 정화 준비 단계에 들어갑니다.',
                'phase2' => '모공 축소 변화가 눈으로 느껴지기 시작하며, 피지 분비가 조절됩니다.',
                'phase3' => '모공 케어 효과가 안정화되며, 매끈한 피부결이 유지되는 단계입니다.',
            ],
            'soothing' => [
                'phase1' => '유효 성분이 피부에 전달되며, 진정 작용 준비 단계에 들어갑니다.',
                'phase2' => '피부 자극이 완화되기 시작하며, 붉은기가 점차 진정됩니다.',
                'phase3' => '진정 효과가 안정화되며, 편안한 피부 상태가 유지되는 단계입니다.',
            ],
        ];

        return $defaults[$this->efficacy_type] ?? $defaults['moisture'];
    }

    /**
     * 효능 마일스톤 기본값 반환
     */
    public function getEfficacyMilestoneLabels(): array
    {
        if ($this->efficacy_milestones) {
            return $this->efficacy_milestones;
        }

        $defaults = [
            'moisture' => ['초기 보습 체감', '수분 밸런스 안정화'],
            'elasticity' => ['초기 탄력 체감', '탄력 효과 안정화'],
            'tone' => ['초기 톤 개선 체감', '색소 완화 안정화'],
            'pore' => ['초기 모공 케어 체감', '모공 개선 안정화'],
            'soothing' => ['초기 진정 효과 체감', '피부 진정 안정화'],
        ];

        return $defaults[$this->efficacy_type] ?? $defaults['moisture'];
    }

    /**
     * 마일스톤 카드 가운데 텍스트 반환
     */
    public function getMilestoneCenterTexts(): array
    {
        if ($this->milestone_center_texts) {
            return $this->milestone_center_texts;
        }

        // 기본값 (효능 타입별) - 개행문자 포함
        $defaults = [
            'moisture' => ["피부 수분\n흡수 시작", "수분 밸런스\n안정화"],
            'elasticity' => ["콜라겐 생성\n촉진 시작", "탄력 효과\n안정화"],
            'tone' => ["멜라닌 생성\n억제 시작", "피부톤 균일화\n안정화"],
            'pore' => ["피지 분비\n조절 시작", "모공 케어\n안정화"],
            'soothing' => ["진정 성분\n흡수 시작", "피부 진정\n안정화"],
        ];

        return $defaults[$this->efficacy_type] ?? $defaults['moisture'];
    }

    /**
     * 최적 사용 시간 설정 반환
     */
    public function getOptimalTiming(): array
    {
        if ($this->optimal_timing) {
            return $this->optimal_timing;
        }

        // 기본값 (효능 타입별)
        $defaults = [
            'moisture' => [
                'reason' => '지속적 수분 공급으로 하루 종일 보습 유지',
                'morning_effect' => 100,
                'evening_effect' => 100,
            ],
            'elasticity' => [
                'reason' => '수면 중 콜라겐 합성 촉진 (성장호르몬 분비 시간대)',
                'morning_effect' => 100,
                'evening_effect' => 131,
            ],
            'tone' => [
                'reason' => '자외선 없는 밤 동안 멜라닌 억제 작용 극대화',
                'morning_effect' => 100,
                'evening_effect' => 123,
            ],
            'pore' => [
                'reason' => '낮 동안 쌓인 피지와 노폐물 제거 후 흡수율 최대',
                'morning_effect' => 100,
                'evening_effect' => 122,
            ],
            'soothing' => [
                'reason' => '밤 동안 피부 재생과 진정 작용 극대화',
                'morning_effect' => 100,
                'evening_effect' => 125,
            ],
        ];

        return $defaults[$this->efficacy_type] ?? $defaults['moisture'];
    }

    // 효능 타입 목록
    public static array $efficacyTypes = [
        'moisture' => '수분 공급',
        'elasticity' => '탄력 개선',
        'tone' => '피부톤 개선',
        'pore' => '모공 케어',
        'soothing' => '피부 진정',
    ];

    public function analysisResults(): HasMany
    {
        return $this->hasMany(AnalysisResult::class);
    }

    /**
     * 제품 성분 (Active Ingredients)
     */
    public function productIngredients(): HasMany
    {
        return $this->hasMany(ProductIngredient::class)->ordered();
    }

    /**
     * 활성화된 제품 성분만 조회
     */
    public function activeIngredients(): HasMany
    {
        return $this->hasMany(ProductIngredient::class)->active()->ordered();
    }
}
