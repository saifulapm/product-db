<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'reminder_date',
        'notes',
        'is_sent',
    ];

    protected $casts = [
        'reminder_date' => 'datetime',
        'is_sent' => 'boolean',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
