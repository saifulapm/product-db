<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskComment extends Model
{
    protected $fillable = [
        'task_id',
        'parent_comment_id',
        'user_id',
        'comment',
        'tagged_users',
        'reactions',
    ];

    protected $casts = [
        'tagged_users' => 'array',
        'reactions' => 'array',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parentComment(): BelongsTo
    {
        return $this->belongsTo(TaskComment::class, 'parent_comment_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TaskComment::class, 'parent_comment_id')->orderBy('created_at', 'asc');
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Send notifications when a comment is created
        static::created(function (TaskComment $comment) {
            $task = $comment->task;
            $commenter = $comment->user;
            
            if (!$task || !$commenter) {
                return;
            }

            // Get users to notify
            $usersToNotify = collect();

            // Notify the assigned user (if different from commenter)
            if ($task->assigned_to && $task->assigned_to !== $commenter->id) {
                $assignedUser = $task->assignedUser;
                if ($assignedUser) {
                    $usersToNotify->push($assignedUser);
                }
            }

            // Notify the task creator (if different from commenter and assigned user)
            if ($task->created_by && $task->created_by !== $commenter->id) {
                $creator = $task->creator;
                if ($creator && !$usersToNotify->contains('id', $creator->id)) {
                    $usersToNotify->push($creator);
                }
            }

            // Track tagged users separately for mention notifications
            $taggedUsers = collect();
            if ($comment->tagged_users && is_array($comment->tagged_users)) {
                foreach ($comment->tagged_users as $taggedUserId) {
                    if ($taggedUserId !== $commenter->id) {
                        $taggedUser = \App\Models\User::find($taggedUserId);
                        if ($taggedUser && !$usersToNotify->contains('id', $taggedUser->id)) {
                            $taggedUsers->push($taggedUser);
                        }
                    }
                }
            }

            // Send regular comment notifications to assigned user and creator
            foreach ($usersToNotify as $user) {
                $user->notify(new \App\Notifications\TaskCommentNotification($task, $comment));
            }

            // Send mention notifications to tagged users
            if ($task->parent_task_id) {
                $task->load('parentTask');
            }
            foreach ($taggedUsers as $taggedUser) {
                $taggedUser->notify(new \App\Notifications\TaskMentionNotification($task, $comment));
            }
        });
    }
}
