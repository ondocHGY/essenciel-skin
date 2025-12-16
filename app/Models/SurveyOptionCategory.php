<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyOptionCategory extends Model
{
    protected $fillable = [
        'key',
        'name',
        'description',
        'has_icon',
        'is_multiple',
        'sort_order',
        'is_active',
        'is_system',
    ];

    protected $casts = [
        'has_icon' => 'boolean',
        'is_multiple' => 'boolean',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(SurveyOption::class, 'category_id')
                    ->orderBy('sort_order');
    }

    public function activeOptions(): HasMany
    {
        return $this->options()->where('is_active', true);
    }

    /**
     * 모든 활성 카테고리와 옵션을 프론트엔드용 형식으로 반환
     */
    public static function getOptionsForFrontend(): array
    {
        $categories = self::where('is_active', true)
            ->with('activeOptions')
            ->orderBy('sort_order')
            ->get();

        $result = [];
        foreach ($categories as $category) {
            $result[$category->key] = $category->activeOptions->map(function ($option) use ($category) {
                $item = [
                    'value' => $option->value,
                    'label' => $option->label,
                ];
                if ($category->has_icon && $option->icon) {
                    $item['icon'] = $option->icon;
                }
                return $item;
            })->toArray();
        }

        return $result;
    }

    /**
     * 특정 카테고리의 옵션별 modifier 값을 반환
     */
    public static function getModifiers(string $categoryKey): array
    {
        $category = self::where('key', $categoryKey)
            ->with(['options' => fn($q) => $q->where('is_active', true)])
            ->first();

        if (!$category) {
            return [];
        }

        $modifiers = [];
        foreach ($category->options as $option) {
            $modifiers[$option->value] = $option->modifier ?? 1.0;
        }

        return $modifiers;
    }

    /**
     * 모든 카테고리의 modifier를 한번에 가져오기 (캐싱용)
     */
    public static function getAllModifiers(): array
    {
        $categories = self::where('is_active', true)
            ->with(['options' => fn($q) => $q->where('is_active', true)])
            ->get();

        $result = [];
        foreach ($categories as $category) {
            $result[$category->key] = [];
            foreach ($category->options as $option) {
                $result[$category->key][$option->value] = $option->modifier ?? 1.0;
            }
        }

        return $result;
    }
}
