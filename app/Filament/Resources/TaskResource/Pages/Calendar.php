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
    
    public $currentDate;
    
    public $viewType = 'week'; // 'month', 'week', '3day', 'day'
    
    public $selectedAssignee = null;
    
    protected $queryString = ['selectedAssignee', 'viewType'];
    
    public function mount(): void
    {
        $this->currentDate = now()->startOfWeek();
        $this->selectedAssignee = request()->query('selectedAssignee');
        $this->viewType = request()->query('viewType', 'week');
    }
    
    public function updatedSelectedAssignee(): void
    {
        // This will trigger a re-render when the filter changes
    }
    
    public function setViewType(string $viewType): void
    {
        $this->viewType = $viewType;
        
        // Adjust current date based on view type
        if ($viewType === 'month') {
            $this->currentDate = $this->currentDate->copy()->startOfMonth()->startOfWeek();
        } elseif ($viewType === 'week') {
            $this->currentDate = $this->currentDate->copy()->startOfWeek();
        } elseif ($viewType === '3day') {
            // Keep current date, just adjust to start of the 3-day range
            $this->currentDate = $this->currentDate->copy()->startOfDay();
        } elseif ($viewType === 'day') {
            $this->currentDate = $this->currentDate->copy()->startOfDay();
        }
    }
    
    public function getTitle(): string
    {
        return 'Calendar';
    }
    
    public function previous(): void
    {
        if ($this->viewType === 'month') {
            $this->currentDate = $this->currentDate->copy()->subMonth()->startOfMonth()->startOfWeek();
        } elseif ($this->viewType === 'week') {
            $this->currentDate = $this->currentDate->copy()->subWeek();
        } elseif ($this->viewType === '3day') {
            $this->currentDate = $this->currentDate->copy()->subDays(3);
        } elseif ($this->viewType === 'day') {
            $this->currentDate = $this->currentDate->copy()->subDay();
        }
    }
    
    public function next(): void
    {
        if ($this->viewType === 'month') {
            $this->currentDate = $this->currentDate->copy()->addMonth()->startOfMonth()->startOfWeek();
        } elseif ($this->viewType === 'week') {
            $this->currentDate = $this->currentDate->copy()->addWeek();
        } elseif ($this->viewType === '3day') {
            $this->currentDate = $this->currentDate->copy()->addDays(3);
        } elseif ($this->viewType === 'day') {
            $this->currentDate = $this->currentDate->copy()->addDay();
        }
    }
    
    public function goToToday(): void
    {
        if ($this->viewType === 'month') {
            $this->currentDate = now()->startOfMonth()->startOfWeek();
        } elseif ($this->viewType === 'week') {
            $this->currentDate = now()->startOfWeek();
        } else {
            $this->currentDate = now()->startOfDay();
        }
    }
    
    public function getDateRange(): string
    {
        if ($this->viewType === 'month') {
            return $this->currentDate->copy()->startOfMonth()->format('F Y');
        } elseif ($this->viewType === 'week') {
            $start = $this->currentDate->copy();
            $end = $this->currentDate->copy()->endOfWeek();
            
            if ($start->month === $end->month) {
                return $start->format('M d') . ' - ' . $end->format('d, Y');
            } else {
                return $start->format('M d') . ' - ' . $end->format('M d, Y');
            }
        } elseif ($this->viewType === '3day') {
            $start = $this->currentDate->copy();
            $end = $this->currentDate->copy()->addDays(2);
            
            if ($start->month === $end->month) {
                return $start->format('M d') . ' - ' . $end->format('d, Y');
            } else {
                return $start->format('M d') . ' - ' . $end->format('M d, Y');
            }
        } elseif ($this->viewType === 'day') {
            return $this->currentDate->copy()->format('l, F d, Y');
        }
        
        return '';
    }
    
    public function getWeekDays(): array
    {
        $days = [];
        $currentDate = $this->currentDate->copy();
        
        for ($i = 0; $i < 7; $i++) {
            $days[] = $currentDate->copy();
            $currentDate->addDay();
        }
        
        return $days;
    }
    
    public function getThreeDays(): array
    {
        $days = [];
        $currentDate = $this->currentDate->copy();
        
        for ($i = 0; $i < 3; $i++) {
            $days[] = $currentDate->copy();
            $currentDate->addDay();
        }
        
        return $days;
    }
    
    public function getSingleDay(): Carbon
    {
        return $this->currentDate->copy();
    }
    
    public function getMonthDays(): array
    {
        $days = [];
        
        // Start from the first day of the month, then go back to the start of that week
        $monthStart = $this->currentDate->copy()->startOfMonth();
        $calendarStart = $monthStart->copy()->startOfWeek();
        
        // Generate 6 weeks (42 days) to ensure full month display
        $currentDate = $calendarStart->copy();
        
        for ($i = 0; $i < 42; $i++) {
            $days[] = [
                'date' => $currentDate->copy(),
                'isCurrentMonth' => $currentDate->month === $monthStart->month,
                'isToday' => $currentDate->isToday(),
            ];
            $currentDate->addDay();
        }
        
        return $days;
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
