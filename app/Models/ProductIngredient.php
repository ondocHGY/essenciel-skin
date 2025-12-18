<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductIngredient extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'image',
        'percentage',
        'description',
        'tags',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 이미지 URL 반환 (없으면 null)
     */
    public function getImageUrlAttribute(): ?string
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        return null;
    }

    /**
     * 활성화된 성분만 조회하는 스코프
     */
    public function scopeActive($query)
    {
        return $query->where(function($q) {
            $q->where('is_active', 1)
              ->orWhere('is_active', true)
              ->orWhere('is_active', 'true')
              ->orWhere('is_active', '1');
        });
    }

    /**
     * 정렬 순서대로 조회하는 스코프
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
