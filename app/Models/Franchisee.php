<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Franchisee extends Model
{
    protected $fillable = [
        'company',
        'location',
        'franchisee_name',
        'logos',
    ];

    protected function casts(): array
    {
        return [
            'logos' => 'array',
        ];
    }
}
