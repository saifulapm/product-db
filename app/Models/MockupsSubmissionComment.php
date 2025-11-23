<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MockupsSubmissionComment extends Model
{
    protected $fillable = [
        'mockups_submission_id',
        'user_id',
        'message',
    ];

    public function mockupsSubmission(): BelongsTo
    {
        return $this->belongsTo(MockupsSubmission::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
