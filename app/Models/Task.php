<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use App\Models\Project;
use App\Models\TaskComment;

class Task extends Model
{
    protected $fillable = [
        'title',
        'task_type',
        'project_id',
        'parent_task_id',
        'store',
        'collection',
        'add_products',
        'design_details',
        'website_images',
        'website_images_upload',
        'website_images_attachments',
        'website_images_notes',
        'all_website_images_needed',
        'eid',
        'adjustment_needed',
        'check_full_product',
        'check_full_collection',
        'additional_eids',
        'description',
        'attachments',
        'assigned_to',
        'created_by',
        'is_completed',
        'completed_at',
        'actions',
        'due_date',
        'priority',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'due_date' => 'date',
        'priority' => 'integer',
        'attachments' => 'array',
        'add_products' => 'boolean',
        'design_details' => 'boolean',
        'website_images' => 'boolean',
        'website_images_upload' => 'array',
        'website_images_attachments' => 'array',
        'all_website_images_needed' => 'boolean',
        'check_full_product' => 'boolean',
        'check_full_collection' => 'boolean',
        'additional_eids' => 'array',
        'actions' => 'array',
    ];

    /**
     * Get the user assigned to this task.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who created this task.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the project this task belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the parent task this subtask belongs to.
     */
    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    /**
     * Get all subtasks for this task.
     */
    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    /**
     * Get all comments for this task.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->orderBy('created_at', 'asc');
    }

    /**
     * Mark task as completed.
     */
    public function markAsCompleted(): void
    {
        $actions = $this->actions ?? [];
        $actions[] = [
            'action' => 'completed',
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name ?? 'System',
            'timestamp' => now()->toDateTimeString(),
        ];
        
        $this->update([
            'is_completed' => true,
            'completed_at' => now(),
            'actions' => $actions,
        ]);
    }

    /**
     * Mark task as incomplete.
     */
    public function markAsIncomplete(): void
    {
        $actions = $this->actions ?? [];
        $actions[] = [
            'action' => 'incompleted',
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name ?? 'System',
            'timestamp' => now()->toDateTimeString(),
        ];
        
        $this->update([
            'is_completed' => false,
            'completed_at' => null,
            'actions' => $actions,
        ]);
    }

    /**
     * Get priority label.
     */
    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            1 => 'Low',
            2 => 'Medium',
            3 => 'High',
            4 => 'Urgent',
            default => 'Low',
        };
    }

    /**
     * Get priority color.
     */
    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            1 => 'gray',
            2 => 'blue',
            3 => 'orange',
            4 => 'red',
            default => 'gray',
        };
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // When a task is deleted, delete all its subtasks
        static::deleting(function (Task $task) {
            $task->subtasks()->delete();
        });
    }
}
