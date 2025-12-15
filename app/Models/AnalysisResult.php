<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalysisResult extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'product_id',
        'profile_id',
        'timeline',
        'milestones',
        'comparison',
        'metrics',
    ];

    protected $casts = [
        'timeline' => 'array',
        'milestones' => 'array',
        'comparison' => 'array',
        'metrics' => 'array',
        'created_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'profile_id');
    }
}
