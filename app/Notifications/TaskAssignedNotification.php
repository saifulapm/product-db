<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Task $task
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $creatorName = $this->task->creator?->name ?? 'System';
        $dueDateText = $this->task->due_date 
            ? 'Due: ' . $this->task->due_date->format('M d, Y')
            : 'No due date';

        $isSubtask = !empty($this->task->parent_task_id);
        $parentTask = $isSubtask ? $this->task->parentTask : null;
        
        $title = $isSubtask ? 'New Subtask Assigned' : 'New Task Assigned';
        $message = $isSubtask 
            ? "You've been assigned to subtask: {$this->task->title}"
            : "You've been assigned to: {$this->task->title}";
        
        $body = "Assigned by {$creatorName}. {$dueDateText}";
        if ($isSubtask && $parentTask) {
            $body .= " (Parent: {$parentTask->title})";
        }

        return [
            'title' => $title,
            'message' => $message,
            'body' => $body,
            'type' => 'task_assigned',
            'format' => 'filament',
            'status' => 'info',
            'icon' => 'heroicon-o-clipboard-document-check',
            'iconColor' => 'primary',
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'is_subtask' => $isSubtask,
            'parent_task_id' => $this->task->parent_task_id,
            'parent_task_title' => $parentTask?->title,
            'actions' => [
                [
                    'label' => $isSubtask ? 'View Subtask' : 'View Task',
                    'url' => \App\Filament\Resources\TaskResource::getUrl('view', ['record' => $this->task->id]),
                ],
            ],
        ];
    }
}

