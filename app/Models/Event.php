<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'company_name',
        'description',
        'start_date',
        'start_time',
        'end_date',
        'location',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'start_time' => 'string',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the full start datetime (date + time)
     */
    public function getStartDateTimeAttribute()
    {
        $startTime = $this->start_time;
        // Handle start_time - it's stored as TIME in DB, so it's a string like "09:00:00"
        if (is_string($startTime)) {
            // Extract just the time part (HH:MM:SS)
            $startTime = substr($startTime, 0, 8);
        } elseif ($startTime instanceof \DateTime || $startTime instanceof \Carbon\Carbon) {
            $startTime = $startTime->format('H:i:s');
        } else {
            $startTime = '09:00:00';
        }
        return Carbon::createFromFormat('Y-m-d H:i:s', $this->start_date->format('Y-m-d') . ' ' . $startTime);
    }

    public function reminders()
    {
        return $this->hasMany(EventReminder::class);
    }
}
