<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    protected $fillable = [
        'review_source_id',
        'platform',
        'trigger_type',
        'status',
        'started_at',
        'completed_at',
        'duration_seconds',
        'reviews_added',
        'reviews_updated',
        'total_reviews',
        'error_message',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_seconds' => 'integer',
        'reviews_added' => 'integer',
        'reviews_updated' => 'integer',
        'total_reviews' => 'integer',
    ];

    public function reviewSource(): BelongsTo
    {
        return $this->belongsTo(ProductReviewSource::class, 'review_source_id');
    }

    public function scopePlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeLatest($query)
    {
        return $query->orderByDesc('started_at');
    }

    /**
     * 플랫폼 표시명
     */
    public function getPlatformLabelAttribute(): string
    {
        return ProductReviewSource::$platforms[$this->platform] ?? $this->platform;
    }

    /**
     * 소요시간 포맷
     */
    public function getDurationFormattedAttribute(): string
    {
        if ($this->duration_seconds === null) {
            return '-';
        }

        if ($this->duration_seconds < 60) {
            return $this->duration_seconds . '초';
        }

        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        return "{$minutes}분 {$seconds}초";
    }

    /**
     * 상태 배지 색상
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'running' => 'yellow',
            'success' => 'green',
            'failed' => 'red',
            default => 'gray',
        };
    }

    /**
     * 상태 한글명
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'running' => '실행중',
            'success' => '성공',
            'failed' => '실패',
            default => $this->status,
        };
    }

    /**
     * 트리거 타입 한글명
     */
    public function getTriggerLabelAttribute(): string
    {
        return match ($this->trigger_type) {
            'scheduled' => '스케줄',
            'manual' => '수동',
            default => $this->trigger_type,
        };
    }
}
