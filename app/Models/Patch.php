<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patch extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'sku',
        'supplier',
        'size',
        'width',
        'height',
        'backing',
        'description',
        'image_reference',
        'minimums',
        'quantity',
        'lead_time',
        'pricing',
        'colors',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'colors' => 'array',
            'is_active' => 'boolean',
            'width' => 'decimal:2',
            'height' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }
}

