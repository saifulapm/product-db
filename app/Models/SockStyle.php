<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SockStyle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'packaging_style',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}