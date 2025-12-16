<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'code',
        'name',
        'brand',
        'category',
        'image',
        'ingredients',
        'base_curve',
        'qr_path',
    ];

    protected $casts = [
        'ingredients' => 'array',
        'base_curve' => 'array',
    ];

    public function analysisResults(): HasMany
    {
        return $this->hasMany(AnalysisResult::class);
    }
}
