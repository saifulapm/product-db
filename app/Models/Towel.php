<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Towel extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'sku',
        'size',
        'material',
        'description',
        'colorways',
        'image_url',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'colorways' => 'array',
            'is_active' => 'boolean',
        ];
    }
}


