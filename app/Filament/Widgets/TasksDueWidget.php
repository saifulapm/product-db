<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TasksDueWidget extends Widget
{
    protected static string $view = 'filament.widgets.tasks-due-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 10;
    
    public function getOverdueTasks()
    {
        $userId = Auth::id();
        $today = Carbon::today();
        
        return Task::where('assigned_to', $userId)
            ->whereDate('due_date', '<', $today)
            ->where('is_completed', false)
            ->with(['assignedUser', 'parentTask', 'project'])
            ->orderBy('due_date', 'asc')
            ->get();
    }
    
    public function getTasksDueToday()
    {
        $userId = Auth::id();
        $today = Carbon::today();
        
        return Task::where('assigned_to', $userId)
            ->whereDate('due_date', $today)
            ->where('is_completed', false)
            ->with(['assignedUser', 'parentTask', 'project'])
            ->orderBy('due_date', 'asc')
            ->get();
    }
    
    public function getAllTasksDueToday()
    {
        $userId = Auth::id();
        $today = Carbon::today();
        
        return Task::where('assigned_to', $userId)
            ->whereDate('due_date', $today)
            ->with(['assignedUser', 'parentTask', 'project'])
            ->get();
    }
    
    public function areAllTasksComplete()
    {
        $allTasks = $this->getAllTasksDueToday();
        $incompleteTasks = $this->getTasksDueToday();
        
        // Show celebration if there are no incomplete tasks
        // (either all tasks are complete, or there are no tasks at all)
        return $incompleteTasks->isEmpty();
    }
    
    public function getUpcomingTasks()
    {
        return Task::where('assigned_to', Auth::id())
            ->whereDate('due_date', '>', Carbon::today())
            ->where('is_completed', false)
            ->with(['assignedUser', 'parentTask', 'project'])
            ->orderBy('due_date', 'asc')
            ->limit(10)
            ->get();
    }
}

