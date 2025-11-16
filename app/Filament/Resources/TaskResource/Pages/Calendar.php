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
    
    public $currentWeekStart;
    
    public $selectedAssignee = null;
    
    protected $queryString = ['selectedAssignee'];
    
    public function mount(): void
    {
        $this->currentWeekStart = now()->startOfWeek();
        $this->selectedAssignee = request()->query('selectedAssignee');
    }
    
    public function updatedSelectedAssignee(): void
    {
        // This will trigger a re-render when the filter changes
    }
    
    public function getTitle(): string
    {
        return 'Calendar';
    }
    
    public function previousWeek(): void
    {
        $this->currentWeekStart = $this->currentWeekStart->copy()->subWeek();
    }
    
    public function nextWeek(): void
    {
        $this->currentWeekStart = $this->currentWeekStart->copy()->addWeek();
    }
    
    public function goToToday(): void
    {
        $this->currentWeekStart = now()->startOfWeek();
    }
    
    public function getWeekDays(): array
    {
        $days = [];
        $currentDate = $this->currentWeekStart->copy();
        
        for ($i = 0; $i < 7; $i++) {
            $days[] = $currentDate->copy();
            $currentDate->addDay();
        }
        
        return $days;
    }
    
    public function getWeekRange(): string
    {
        $start = $this->currentWeekStart->copy();
        $end = $this->currentWeekStart->copy()->endOfWeek();
        
        if ($start->month === $end->month) {
            return $start->format('M d') . ' - ' . $end->format('d, Y');
        } else {
            return $start->format('M d') . ' - ' . $end->format('M d, Y');
        }
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
}
