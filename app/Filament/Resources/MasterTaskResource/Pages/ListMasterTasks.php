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
    
    protected $queryString = [
        'search' => ['except' => ''],
        'sortBy' => ['except' => 'due_date_asc'],
    ];
    
    public function mount(): void
    {
        $this->search = request()->query('search', '');
        $this->sortBy = request()->query('sortBy', 'due_date_asc');
    }
    
    public function updatedSearch(): void
    {
        // This will trigger a re-render when search changes
    }
    
    public function updatedSortBy(): void
    {
        // This will trigger a re-render when sort changes
    }
    
    public function getIncompleteTasks()
    {
        $tasks = Task::whereNull('parent_task_id')
            ->with(['subtasks'])
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
        
        // Apply sorting
        return $this->sortTasks($tasks);
    }
    
    public function getCompleteTasks()
    {
        $tasks = Task::whereNull('parent_task_id')
            ->with(['subtasks'])
            ->get()
            ->filter(function ($task) {
                $subtasks = $task->subtasks;
                // Only show in complete if task has subtasks AND all are completed
                if ($subtasks->isEmpty()) {
                    return false; // Tasks without subtasks don't go to complete section
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
}
