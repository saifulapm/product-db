<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SharedTableView extends Model
{
    protected $fillable = [
        'resource_name',
        'view_name',
        'filters',
        'sort_column',
        'sort_direction',
        'column_visibility',
        'search_query',
        'is_default',
        'created_by',
    ];

    protected $casts = [
        'filters' => 'array',
        'column_visibility' => 'array',
        'is_default' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}