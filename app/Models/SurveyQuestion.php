<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class SurveyQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'title',
        'subtitle',
        'category',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(SurveyOption::class, 'question_id')->orderBy('sort_order');
    }

    public function activeOptions(): HasMany
    {
        return $this->hasMany(SurveyOption::class, 'question_id')
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    /**
     * 활성화된 설문 질문 목록을 캐시와 함께 반환
     */
    public static function getActiveQuestions(): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember('survey_questions_active', 3600, function () {
            return static::where('is_active', true)
                ->with('activeOptions')
                ->orderBy('sort_order')
                ->get();
        });
    }

    /**
     * 프론트엔드용 JSON 형식으로 변환
     */
    public function toFrontendFormat(): array
    {
        return [
            'name' => $this->key,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'options' => $this->activeOptions->map(fn($opt) => [
                'value' => $opt->value,
                'label' => $opt->label,
                'desc' => $opt->description,
            ])->toArray(),
        ];
    }

    /**
     * 캐시 클리어
     */
    public static function clearCache(): void
    {
        Cache::forget('survey_questions_active');
        Cache::forget('survey_modifiers');
    }

    protected static function booted(): void
    {
        static::saved(fn() => static::clearCache());
        static::deleted(fn() => static::clearCache());
    }
}
