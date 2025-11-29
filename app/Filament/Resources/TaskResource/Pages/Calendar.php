<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Models\Task;
use App\Models\User;
use Filament\Pages\Page;
use Carbon\Carbon;

class Calendar extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    
    protected static ?string $navigationLabel = 'Calendar';
    
    protected static ?string $navigationGroup = 'Tasks';
    
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.resources.task-resource.pages.calendar';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('tasks.calendar.view');
    }
    
    public $currentDate;
    
    public $viewType = 'week'; // 'week', '3day', 'day'
    
    public $selectedAssignee = null;
    
    public $isFullScreen = false;
    
    protected $queryString = ['selectedAssignee', 'viewType'];
    
    public function mount(): void
    {
        $this->selectedAssignee = request()->query('selectedAssignee');
        $viewType = request()->query('viewType', 'week');
        
        // If month view is requested, default to week
        if ($viewType === 'month') {
            $this->viewType = 'week';
        } else {
            $this->viewType = $viewType;
        }
        
        // Initialize currentDate based on view type (store as string for Livewire)
        if ($this->viewType === 'week') {
            $this->currentDate = now()->startOfWeek()->toDateString();
        } elseif ($this->viewType === '3day' || $this->viewType === 'day') {
            $this->currentDate = now()->startOfDay()->toDateString();
        } else {
            $this->currentDate = now()->startOfWeek()->toDateString();
        }
    }
    
    public function updatedSelectedAssignee(): void
    {
        // This will trigger a re-render when the filter changes
    }
    
    public function setViewType(string $viewType): void
    {
        // Reject month view, default to week if requested
        if ($viewType === 'month') {
            $viewType = 'week';
        }
        
        $this->viewType = $viewType;
        
        // Adjust current date based on view type (parse string to Carbon, then back to string)
        $date = Carbon::parse($this->currentDate);
        if ($viewType === 'week') {
            $this->currentDate = $date->copy()->startOfWeek()->toDateString();
        } elseif ($viewType === '3day') {
            // Keep current date, just adjust to start of the 3-day range
            $this->currentDate = $date->copy()->startOfDay()->toDateString();
        } elseif ($viewType === 'day') {
            $this->currentDate = $date->copy()->startOfDay()->toDateString();
        }
    }
    
    public function getTitle(): string
    {
        return 'Calendar';
    }
    
    public function previous(): void
    {
        if ($this->viewType === 'week') {
            $newDate = Carbon::parse($this->currentDate)->subWeek();
            $this->currentDate = $newDate->toDateString();
        } elseif ($this->viewType === '3day') {
            $newDate = Carbon::parse($this->currentDate)->subDays(3);
            $this->currentDate = $newDate->toDateString();
        } elseif ($this->viewType === 'day') {
            $newDate = Carbon::parse($this->currentDate)->subDay();
            $this->currentDate = $newDate->toDateString();
        }
    }
    
    public function next(): void
    {
        if ($this->viewType === 'week') {
            $newDate = Carbon::parse($this->currentDate)->addWeek();
            $this->currentDate = $newDate->toDateString();
        } elseif ($this->viewType === '3day') {
            $newDate = Carbon::parse($this->currentDate)->addDays(3);
            $this->currentDate = $newDate->toDateString();
        } elseif ($this->viewType === 'day') {
            $newDate = Carbon::parse($this->currentDate)->addDay();
            $this->currentDate = $newDate->toDateString();
        }
    }
    
    public function goToToday(): void
    {
        if ($this->viewType === 'week') {
            $this->currentDate = now()->startOfWeek()->toDateString();
        } elseif ($this->viewType === '3day' || $this->viewType === 'day') {
            $this->currentDate = now()->startOfDay()->toDateString();
        } else {
            $this->currentDate = now()->startOfWeek()->toDateString();
        }
    }
    
    public function getDateRange(): string
    {
        $date = Carbon::parse($this->currentDate);
        
        if ($this->viewType === 'week') {
            $start = $date->copy();
            $end = $date->copy()->endOfWeek();
            
            if ($start->month === $end->month) {
                return $start->format('M d') . ' - ' . $end->format('d, Y');
            } else {
                return $start->format('M d') . ' - ' . $end->format('M d, Y');
            }
        } elseif ($this->viewType === '3day') {
            $start = $date->copy();
            $end = $date->copy()->addDays(2);
            
            if ($start->month === $end->month) {
                return $start->format('M d') . ' - ' . $end->format('d, Y');
            } else {
                return $start->format('M d') . ' - ' . $end->format('M d, Y');
            }
        } elseif ($this->viewType === 'day') {
            return $date->copy()->format('l, F d, Y');
        }
        
        return '';
    }
    
    public function getWeekDays(): array
    {
        $days = [];
        $currentDate = Carbon::parse($this->currentDate)->copy();
        
        for ($i = 0; $i < 7; $i++) {
            $days[] = $currentDate->copy();
            $currentDate->addDay();
        }
        
        return $days;
    }
    
    public function getThreeDays(): array
    {
        $days = [];
        $currentDate = Carbon::parse($this->currentDate)->copy();
        
        for ($i = 0; $i < 3; $i++) {
            $days[] = $currentDate->copy();
            $currentDate->addDay();
        }
        
        return $days;
    }
    
    public function getSingleDay(): Carbon
    {
        return Carbon::parse($this->currentDate)->copy();
    }
    
    public function getTasksForDate(Carbon $date)
    {
        $query = Task::whereDate('due_date', $date->format('Y-m-d'))
            ->with(['parentTask', 'assignedUser']);
        
        // Filter by assignee if selected
        if ($this->selectedAssignee) {
            $query->where('assigned_to', $this->selectedAssignee);
        }
        
        return $query->get();
    }
    
    public function getTasksForDateRange(Carbon $startDate, Carbon $endDate)
    {
        $query = Task::whereBetween('due_date', [
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        ])
            ->with(['parentTask', 'assignedUser']);
        
        // Filter by assignee if selected
        if ($this->selectedAssignee) {
            $query->where('assigned_to', $this->selectedAssignee);
        }
        
        return $query->get();
    }
    
    public function getAssignees()
    {
        return User::whereHas('assignedTasks', function ($query) {
            $query->whereNotNull('due_date');
        })
        ->orderBy('name')
        ->get();
    }
    
    public function getViewTaskUrl(Task $task): string
    {
        return TaskResource::getUrl('view', ['record' => $task]);
    }
    
    // Legacy methods for backward compatibility
    public function previousWeek(): void
    {
        $this->previous();
    }
    
    public function nextWeek(): void
    {
        $this->next();
    }
    
    public function getWeekRange(): string
    {
        return $this->getDateRange();
    }
}