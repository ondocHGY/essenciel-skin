<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserProfile extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'age_group',
        'skin_type',
        'gender',
        'concerns',
        'lifestyle',
        'skincare_habit',
        'satisfaction',
        'alcohol',
        'smoking',
    ];

    protected $casts = [
        'concerns' => 'array',
        'lifestyle' => 'array',
        'skincare_habit' => 'array',
        'created_at' => 'datetime',
    ];

    public function analysisResult(): HasOne
    {
        return $this->hasOne(AnalysisResult::class, 'profile_id');
    }
}
