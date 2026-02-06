<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReview extends Model
{
    protected $fillable = [
        'product_id',
        'review_source_id',
        'platform',
        'platform_product_code',
        'external_id',
        'rating',
        'title',
        'content',
        'author',
        'purchased_option',
        'images',
        'is_featured',
        'is_visible',
        'reviewed_at',
    ];

    protected $casts = [
        'rating' => 'float',
        'images' => 'array',
        'is_featured' => 'boolean',
        'is_visible' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reviewSource(): BelongsTo
    {
        return $this->belongsTo(ProductReviewSource::class, 'review_source_id');
    }

    /**
     * 대표 리뷰만 조회
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * 표시 가능한 리뷰만 조회
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * 플랫폼별 조회
     */
    public function scopePlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * 상품 미매칭 리뷰 (product_id가 NULL)
     */
    public function scopeUnmatched($query)
    {
        return $query->whereNull('product_id');
    }

    /**
     * 상품 매칭 완료 리뷰
     */
    public function scopeMatched($query)
    {
        return $query->whereNotNull('product_id');
    }

    /**
     * 플랫폼 상품코드로 조회
     */
    public function scopeByPlatformCode($query, string $platform, string $code)
    {
        return $query->where('platform', $platform)
                     ->where('platform_product_code', $code);
    }

    /**
     * 평점 높은 순 정렬
     */
    public function scopeHighRated($query)
    {
        return $query->orderByDesc('rating');
    }

    /**
     * 최신순 정렬
     */
    public function scopeLatest($query)
    {
        return $query->orderByDesc('reviewed_at');
    }

    /**
     * 플랫폼 표시명
     */
    public function getPlatformLabelAttribute(): string
    {
        return ProductReviewSource::$platforms[$this->platform] ?? $this->platform;
    }

    /**
     * 마스킹된 작성자명
     */
    public function getMaskedAuthorAttribute(): string
    {
        $name = $this->author ?? '익명';
        $length = mb_strlen($name);

        if ($length <= 2) {
            return mb_substr($name, 0, 1) . '*';
        }

        return mb_substr($name, 0, 1) . str_repeat('*', $length - 2) . mb_substr($name, -1);
    }

    /**
     * 별점 아이콘 (★☆)
     */
    public function getStarsAttribute(): string
    {
        $full = (int) floor($this->rating);
        $half = ($this->rating - $full) >= 0.5 ? 1 : 0;
        $empty = 5 - $full - $half;

        return str_repeat('★', $full) . str_repeat('☆', $empty);
    }

    /**
     * 리뷰 요약 (N자 이내)
     */
    public function getSummary(int $length = 100): string
    {
        $content = strip_tags($this->content);

        if (mb_strlen($content) <= $length) {
            return $content;
        }

        return mb_substr($content, 0, $length) . '...';
    }
}
