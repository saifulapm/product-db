<?php

namespace App\Filament\Resources\MasterTaskResource\Pages;

use App\Filament\Resources\MasterTaskResource;
use App\Filament\Resources\TaskResource;
use App\Models\Task;
use Filament\Resources\Pages\Page;
use Filament\Actions;
use Illuminate\Contracts\Support\Htmlable;

class ListMasterTasks extends Page
{
    protected static string $resource = MasterTaskResource::class;
    
    protected static string $view = 'filament.resources.master-task-resource.pages.list-master-tasks';
    
    public function getTitle(): string
    {
        return 'Task Home';
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('add_task')
                ->label('Add Task')
                ->url(TaskResource::getUrl('create'))
                ->icon('heroicon-o-plus')
                ->color('primary'),
        ];
    }
    
    public ?string $search = '';
    
    public ?string $sortBy = 'due_date_asc';
    
    public ?string $taskTypeFilter = null;
    
    public ?string $assigneeFilter = null;
    
    public ?string $statusFilter = null;
    
    public string $viewType = 'list';
    
    protected $queryString = [
        'search' => ['except' => ''],
        'sortBy' => ['except' => 'due_date_asc'],
        'taskTypeFilter' => ['except' => null],
        'assigneeFilter' => ['except' => null],
        'statusFilter' => ['except' => null],
        'viewType' => ['except' => 'list'],
    ];
    
    public function mount(): void
    {
        $this->search = request()->query('search', '');
        $this->sortBy = request()->query('sortBy', 'due_date_asc');
        $this->taskTypeFilter = request()->query('taskTypeFilter');
        $this->assigneeFilter = request()->query('assigneeFilter');
        $this->statusFilter = request()->query('statusFilter');
        $this->viewType = request()->query('viewType', 'list');
    }
    
    public function setViewType(string $viewType): void
    {
        $this->viewType = $viewType;
    }
    
    public function updatedSearch(): void
    {
        // This will trigger a re-render when search changes
    }
    
    public function updatedSortBy(): void
    {
        // This will trigger a re-render when sort changes
    }
    
    public function updatedTaskTypeFilter(): void
    {
        // This will trigger a re-render when filter changes
    }
    
    public function updatedAssigneeFilter(): void
    {
        // This will trigger a re-render when filter changes
    }
    
    public function updatedStatusFilter(): void
    {
        // This will trigger a re-render when filter changes
    }
    
    public function getTaskTypeOptions()
    {
        return \App\Models\Project::orderBy('name')->pluck('name', 'id')->toArray();
    }
    
    public function getAssigneeOptions()
    {
        return \App\Models\User::orderBy('name')->pluck('name', 'id')->toArray();
    }
    
    public function getIncompleteTasks()
    {
        $tasks = Task::whereNull('parent_task_id')
            ->with(['subtasks', 'assignedUser', 'project'])
            ->get()
            ->filter(function ($task) {
                $subtasks = $task->subtasks;
                // If no subtasks, show in incomplete if task itself is not completed
                if ($subtasks->isEmpty()) {
                    return !$task->is_completed;
                }
                // If has subtasks, show in incomplete if not all subtasks are completed
                $completedSubtasks = $subtasks->where('is_completed', true)->count();
                return $completedSubtasks < $subtasks->count();
            });
        
        // Apply search filter
        if (!empty($this->search)) {
            $tasks = $tasks->filter(function ($task) {
                return stripos($task->title, $this->search) !== false;
            });
        }
        
        // Apply task type filter
        if (!empty($this->taskTypeFilter)) {
            $tasks = $tasks->filter(function ($task) {
                return $task->project_id == $this->taskTypeFilter;
            });
        }
        
        // Apply assignee filter
        if (!empty($this->assigneeFilter)) {
            $tasks = $tasks->filter(function ($task) {
                return $task->assigned_to == $this->assigneeFilter;
            });
        }
        
        // Apply sorting
        return $this->sortTasks($tasks);
    }
    
    public function getCompleteTasks()
    {
        $tasks = Task::whereNull('parent_task_id')
            ->with(['subtasks', 'assignedUser', 'project'])
            ->get()
            ->filter(function ($task) {
                // If task is marked as completed, include it regardless of subtasks
                if ($task->is_completed) {
                    return true;
                }
                
                // Otherwise, check if all subtasks are completed (for backward compatibility)
                $subtasks = $task->subtasks;
                if ($subtasks->isEmpty()) {
                    return false; // Tasks without subtasks that aren't marked as completed don't go to complete section
                }
                $completedSubtasks = $subtasks->where('is_completed', true)->count();
                return $completedSubtasks === $subtasks->count() && $subtasks->count() > 0;
            });
        
        // Apply search filter
        if (!empty($this->search)) {
            $tasks = $tasks->filter(function ($task) {
                return stripos($task->title, $this->search) !== false;
            });
        }
        
        // Apply task type filter
        if (!empty($this->taskTypeFilter)) {
            $tasks = $tasks->filter(function ($task) {
                return $task->project_id == $this->taskTypeFilter;
            });
        }
        
        // Apply assignee filter
        if (!empty($this->assigneeFilter)) {
            $tasks = $tasks->filter(function ($task) {
                return $task->assigned_to == $this->assigneeFilter;
            });
        }
        
        // Apply sorting
        return $this->sortTasks($tasks);
    }
    
    protected function sortTasks($tasks)
    {
        return $tasks->sortBy(function ($task) {
            switch ($this->sortBy) {
                case 'due_date_asc':
                    return $task->due_date ? $task->due_date->timestamp : PHP_INT_MAX;
                case 'due_date_desc':
                    return $task->due_date ? -$task->due_date->timestamp : PHP_INT_MIN;
                case 'title_asc':
                    return strtolower($task->title);
                case 'title_desc':
                    return strtolower(strrev($task->title));
                default:
                    return $task->due_date ? $task->due_date->timestamp : PHP_INT_MAX;
            }
        })->values();
    }
    
    public function getProgressPercentage(Task $task): int
    {
        $subtasks = $task->subtasks;
        if ($subtasks->isEmpty()) {
            return $task->is_completed ? 100 : 0;
        }
        $completedSubtasks = $subtasks->where('is_completed', true)->count();
        return (int) round(($completedSubtasks / $subtasks->count()) * 100);
    }
    
    public function getViewTaskUrl(Task $task): string
    {
        return TaskResource::getUrl('view', ['record' => $task]);
    }
    
    public function editTask(int $taskId): void
    {
        $this->redirect(TaskResource::getUrl('edit', ['record' => $taskId]));
    }
    
    public function deleteTask(int $taskId): void
    {
        $task = Task::find($taskId);
        
        if ($task) {
            // Delete subtasks first
            $task->subtasks()->delete();
            // Delete the task
            $task->delete();
            
            \Filament\Notifications\Notification::make()
                ->title('Task deleted successfully')
                ->success()
                ->send();
        }
    }
    
    public function markTaskAsComplete(int $taskId): void
    {
        $task = Task::find($taskId);
        
        if ($task) {
            // Get subtasks and identify which were already completed
            $subtasks = $task->subtasks;
            $alreadyCompletedSubtasks = $subtasks->where('is_completed', true)->pluck('id')->toArray();
            $incompleteSubtasks = $subtasks->where('is_completed', false);
            $autoCompletedSubtaskIds = $incompleteSubtasks->pluck('id')->toArray();
            
            // Mark all subtasks as completed first
            $task->subtasks()->update(['is_completed' => true, 'completed_at' => now()]);
            
            // Mark the main task as completed and store which subtasks were auto-completed
            $actions = $task->actions ?? [];
            $actions[] = [
                'action' => 'completed',
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name ?? 'System',
                'timestamp' => now()->toDateTimeString(),
                'auto_completed_subtasks' => $autoCompletedSubtaskIds, // Store IDs of subtasks that were auto-completed
                'already_completed_subtasks' => $alreadyCompletedSubtasks, // Store IDs of subtasks that were already completed
            ];
            
            $task->update([
                'is_completed' => true,
                'completed_at' => now(),
                'actions' => $actions,
            ]);
            
            $message = 'Task marked as complete';
            if ($incompleteSubtasks->count() > 0) {
                $message .= '. ' . $incompleteSubtasks->count() . ' subtask(s) were automatically marked as complete.';
            }
            
            \Filament\Notifications\Notification::make()
                ->title($message)
                ->success()
                ->send();
        }
    }
    
    public function hasIncompleteSubtasks(int $taskId): bool
    {
        $task = Task::find($taskId);
        
        if (!$task) {
            return false;
        }
        
        $subtasks = $task->subtasks;
        if ($subtasks->isEmpty()) {
            return false;
        }
        
        return $subtasks->where('is_completed', false)->count() > 0;
    }
    
    public function markTaskAsIncomplete(int $taskId): void
    {
        $task = Task::find($taskId);
        
        if ($task) {
            // Find the most recent completion action to get which subtasks were auto-completed
            $actions = $task->actions ?? [];
            $lastCompletionAction = null;
            
            // Find the last 'completed' action
            foreach (array_reverse($actions) as $action) {
                if (isset($action['action']) && $action['action'] === 'completed') {
                    $lastCompletionAction = $action;
                    break;
                }
            }
            
            // If we have info about which subtasks were auto-completed, only mark those as incomplete
            if ($lastCompletionAction && isset($lastCompletionAction['auto_completed_subtasks'])) {
                $autoCompletedSubtaskIds = $lastCompletionAction['auto_completed_subtasks'];
                
                // Only mark the auto-completed subtasks as incomplete
                if (!empty($autoCompletedSubtaskIds)) {
                    $task->subtasks()
                        ->whereIn('id', $autoCompletedSubtaskIds)
                        ->update(['is_completed' => false, 'completed_at' => null]);
                }
            } else {
                // Fallback: if we don't have the info, mark all subtasks as incomplete
                $task->subtasks()->update(['is_completed' => false, 'completed_at' => null]);
            }
            
            // Mark the main task as incomplete
            $actions[] = [
                'action' => 'reopened',
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name ?? 'System',
                'timestamp' => now()->toDateTimeString(),
            ];
            
            $task->update([
                'is_completed' => false,
                'completed_at' => null,
                'actions' => $actions,
            ]);
            
            \Filament\Notifications\Notification::make()
                ->title('Task marked as incomplete')
                ->success()
                ->send();
        }
    }
}
