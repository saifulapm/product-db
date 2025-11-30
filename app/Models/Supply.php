<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supply extends Model
{
    protected $fillable = [
        'name',
        'type',
        'weight',
        'reorder_link',
        'quantity',
        'cubic_measurements',
    ];

    protected $casts = [
        'cubic_measurements' => 'array',
    ];
}
