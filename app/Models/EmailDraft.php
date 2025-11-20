<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailDraft extends Model
{
    protected $fillable = [
        'department',
        'title',
        'description',
        'phone',
        'email',
        'hours',
        'icon_color',
        'icon_name',
        'sort_order',
        'is_active',
        'is_emergency',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_emergency' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }
}

