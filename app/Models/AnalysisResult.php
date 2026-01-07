<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AnalysisResult extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'share_token',
        'product_id',
        'profile_id',
        'timeline',
        'milestones',
        'comparison',
        'metrics',
        'lifestyle_factors',
        'usage_guide',
        'skin_profile',
    ];

    protected $casts = [
        'timeline' => 'array',
        'milestones' => 'array',
        'comparison' => 'array',
        'metrics' => 'array',
        'lifestyle_factors' => 'array',
        'usage_guide' => 'array',
        'skin_profile' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->share_token)) {
                $model->share_token = self::generateUniqueToken();
            }
        });
    }

    public static function generateUniqueToken(): string
    {
        do {
            $token = Str::random(16);
        } while (self::where('share_token', $token)->exists());

        return $token;
    }

    public function getShareUrl(): string
    {
        return route('result.share', $this->share_token);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'profile_id');
    }
}
