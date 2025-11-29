<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TaskMentionNotification extends Notification
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

        $isSubtask = !empty($this->task->parent_task_id);
        $parentTask = $isSubtask ? $this->task->parentTask : null;

        return [
            'title' => 'You were mentioned',
            'message' => "{$commenterName} mentioned you in a comment on: {$this->task->title}",
            'body' => $commentPreview,
            'type' => 'task_mention',
            'format' => 'filament',
            'status' => 'warning',
            'icon' => 'heroicon-o-at-symbol',
            'iconColor' => 'warning',
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'comment_id' => $this->comment->id,
            'commenter_name' => $commenterName,
            'is_subtask' => $isSubtask,
            'parent_task_id' => $this->task->parent_task_id,
            'parent_task_title' => $parentTask?->title,
            'actions' => [
                [
                    'label' => 'View Comment',
                    'url' => \App\Filament\Resources\TaskResource::getUrl('view', ['record' => $this->task->id]),
                ],
            ],
        ];
    }
}

