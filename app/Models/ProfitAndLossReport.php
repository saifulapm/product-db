<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfitAndLossReport extends Model
{
    protected $fillable = [
        'report_month',
        'total_revenue',
        'total_expenses',
        'net_profit',
        'notes',
        'attachment_path',
    ];

    protected $casts = [
        'report_month' => 'date',
        'total_revenue' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'net_profit' => 'decimal:2',
    ];
}




