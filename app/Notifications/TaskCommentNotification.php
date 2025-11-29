<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TaskCommentNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Task $task,
        public TaskComment $comment
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $commenterName = $this->comment->user?->name ?? 'Someone';
        $commentPreview = strlen($this->comment->comment) > 100 
            ? substr($this->comment->comment, 0, 100) . '...'
            : $this->comment->comment;

        return [
            'title' => 'New Comment on Task',
            'message' => "{$commenterName} commented on: {$this->task->title}",
            'body' => $commentPreview,
            'type' => 'task_comment',
            'format' => 'filament',
            'status' => 'info',
            'icon' => 'heroicon-o-chat-bubble-left-right',
            'iconColor' => 'primary',
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'comment_id' => $this->comment->id,
            'commenter_name' => $commenterName,
            'actions' => [
                [
                    'label' => 'View Task',
                    'url' => \App\Filament\Resources\TaskResource::getUrl('view', ['record' => $this->task->id]),
                ],
            ],
        ];
    }
}

