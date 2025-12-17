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
        'efficacy_metrics',
        'intro_metrics',
        'intro_summary',
        'intro_review_count',
        'image',
        'ingredients',
        'ingredient_details',
        'nanoliposome_info',
        'base_curve',
        'qr_path',
    ];

    protected $casts = [
        'ingredients' => 'array',
        'ingredient_details' => 'array',
        'nanoliposome_info' => 'array',
        'efficacy_curve' => 'array',
        'efficacy_phases' => 'array',
        'efficacy_milestones' => 'array',
        'efficacy_metrics' => 'array',
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
            'wrinkle' => [
                'phase1' => '유효 성분이 피부에 전달되며, 표피 재생 촉진 준비 단계에 들어갑니다.',
                'phase2' => '주름 완화 변화가 느껴지기 시작하며, 미세주름이 점차 개선됩니다.',
                'phase3' => '주름 개선 효과가 안정화되며, 매끄러운 피부결이 유지되는 단계입니다.',
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
            'wrinkle' => ['초기 주름 완화 체감', '주름 개선 안정화'],
        ];

        return $defaults[$this->efficacy_type] ?? $defaults['moisture'];
    }

    // 효능 타입 목록
    public static array $efficacyTypes = [
        'moisture' => '수분 공급',
        'elasticity' => '탄력 개선',
        'tone' => '피부톤 개선',
        'pore' => '모공 케어',
        'wrinkle' => '주름 개선',
    ];

    public function analysisResults(): HasMany
    {
        return $this->hasMany(AnalysisResult::class);
    }
}
