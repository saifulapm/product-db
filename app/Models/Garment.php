<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Garment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'variants',
        'measurements',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'variants' => 'array',
        'measurements' => 'array',
    ];
}
