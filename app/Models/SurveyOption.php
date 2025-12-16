<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyOption extends Model
{
    protected $fillable = [
        'category_id',
        'value',
        'label',
        'icon',
        'modifier',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'modifier' => 'float',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(SurveyOptionCategory::class, 'category_id');
    }
}
