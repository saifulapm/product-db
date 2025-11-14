<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DstFile extends Model
{
    protected $fillable = [
        'file_name',
        'file_path',
        'design_name',
        'description',
        'file_type',
        'stitch_count',
        'thread_colors_needed',
        'size_dimensions',
        'usage_instructions',
        'application_notes',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'stitch_count' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('design_name');
    }
}










