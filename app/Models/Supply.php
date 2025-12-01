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
        'reorder_point',
        'cubic_measurements',
        'shipment_tracking',
    ];

    protected $casts = [
        'cubic_measurements' => 'array',
        'shipment_tracking' => 'array',
    ];
}
