<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmbroideryProcess extends Model
{
    protected $fillable = [
        'step_number',
        'step_title',
        'description',
        'equipment_required',
        'materials_needed',
        'estimated_time',
        'special_notes',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'step_number' => 'integer',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('step_number');
    }
}




