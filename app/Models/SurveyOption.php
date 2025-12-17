<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class SurveyOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'value',
        'label',
        'description',
        'modifier',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'modifier' => 'float',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(SurveyQuestion::class, 'question_id');
    }

    /**
     * 질문 키별 modifier 맵을 반환 (캐시됨)
     * 예: ['age_group' => ['10대' => 1.25, '20대' => 1.15, ...], ...]
     */
    public static function getModifierMap(): array
    {
        return Cache::remember('survey_modifiers', 3600, function () {
            $modifiers = [];

            $questions = SurveyQuestion::with('options')->get();

            foreach ($questions as $question) {
                $modifiers[$question->key] = [];
                foreach ($question->options as $option) {
                    $modifiers[$question->key][$option->value] = $option->modifier;
                }
            }

            return $modifiers;
        });
    }

    /**
     * 특정 질문의 특정 값에 대한 modifier 반환
     */
    public static function getModifier(string $questionKey, string $value, float $default = 1.0): float
    {
        $map = static::getModifierMap();
        return $map[$questionKey][$value] ?? $default;
    }

    protected static function booted(): void
    {
        static::saved(fn() => SurveyQuestion::clearCache());
        static::deleted(fn() => SurveyQuestion::clearCache());
    }
}
