<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'user_id',
        'fabric_roll_minimum',
        'products',
    ];

    protected $casts = [
        'fabric_roll_minimum' => 'decimal:2',
        'products' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
