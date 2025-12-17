<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShootModel extends Model
{
    protected $fillable = [
        'name',
        'submission_date',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'social_media',
        'selfie_url',
        'coffee_order',
        'food_allergies',
        'tops_size',
        'bottoms_size',
        'availability',
        'height',
        'google_sheets_timestamp',
        'google_sheets_row_id',
        'google_sheets_data',
    ];

    protected function casts(): array
    {
        return [
            'submission_date' => 'datetime',
            'google_sheets_timestamp' => 'datetime',
            'google_sheets_data' => 'array',
            'tops_size' => 'array',
            'bottoms_size' => 'array',
            'availability' => 'array',
        ];
    }
}
