<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailDraft extends Model
{
    protected $fillable = [
        'department',
        'description',
    ];
}


