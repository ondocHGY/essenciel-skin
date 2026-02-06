<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReviewSource extends Model
{
    protected $fillable = [
        'product_id',
        'platform',
        'platform_name',
        'external_url',
        'external_id',
        'review_count',
        'average_rating',
        'recent_reviews',
        'api_config',
        'is_active',
        'synced_at',
    ];

    protected $casts = [
        'recent_reviews' => 'array',
        'api_config' => 'encrypted:array',
        'is_active' => 'boolean',
        'synced_at' => 'datetime',
        'review_count' => 'integer',
        'average_rating' => 'float',
    ];

    // 플랫폼 상수
    public const PLATFORM_SHOPEE = 'shopee';
    public const PLATFORM_NAVER = 'naver';
    public const PLATFORM_QOO10 = 'qoo10';
    public const PLATFORM_COUPANG = 'coupang';
    public const PLATFORM_MUSINSA = 'musinsa';
    public const PLATFORM_HWAHAE = 'hwahae';
    public const PLATFORM_WCONCEPT = 'wconcept';
    public const PLATFORM_AMAZON = 'amazon';

    public static array $platforms = [
        self::PLATFORM_SHOPEE => 'Shopee',
        self::PLATFORM_NAVER => '네이버 스마트스토어',
        self::PLATFORM_QOO10 => 'Qoo10',
        self::PLATFORM_COUPANG => '쿠팡',
        self::PLATFORM_MUSINSA => '무신사',
        self::PLATFORM_HWAHAE => '화해',
        self::PLATFORM_WCONCEPT => 'W컨셉',
        self::PLATFORM_AMAZON => '아마존',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 활성화된 소스만 조회
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 동기화가 필요한 소스 (N시간 이상 지난 경우)
     */
    public function scopeNeedsSync($query, int $hours = 3)
    {
        return $query->where(function ($q) use ($hours) {
            $q->whereNull('synced_at')
              ->orWhere('synced_at', '<', now()->subHours($hours));
        });
    }

    /**
     * 플랫폼 표시명 반환
     */
    public function getPlatformLabelAttribute(): string
    {
        return self::$platforms[$this->platform] ?? $this->platform;
    }
}
