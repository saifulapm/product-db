<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Models\Task;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        
        // Set default priority if not provided
        if (!isset($data['priority']) || $data['priority'] === null) {
            $data['priority'] = 1; // Default to Low
        }
        
        // Set default due date to today if not provided
        if (!isset($data['due_date']) || empty($data['due_date'])) {
            $data['due_date'] = Carbon::today()->format('Y-m-d');
        }
        
        // Log the creation action
        $data['actions'] = [[
            'action' => 'created',
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name ?? 'System',
            'timestamp' => now()->toDateTimeString(),
        ]];
        
        return $data;
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getState();
        $subtasksCreated = 0;
        
        // Create subtask if "Add Products" is checked
        if (!empty($data['add_products']) && $data['add_products'] === true) {
            // Determine assigned user: use manual selection if provided, otherwise auto-assign to Grace
            $assignedTo = null;
            if (!empty($data['add_products_subtask_assigned_to'])) {
                // Manual override provided
                $assignedTo = $data['add_products_subtask_assigned_to'];
            } else {
                // Auto-assign to Grace
                $graceUser = User::where('email', 'grace@ethos.community')->first();
                $assignedTo = $graceUser ? $graceUser->id : null;
            }
            
            // Determine due date: use manual selection if provided, otherwise calculate based on PST time
            $dueDateFormatted = null;
            if (!empty($data['add_products_subtask_due_date'])) {
                // Manual override provided
                $dueDateFormatted = Carbon::parse($data['add_products_subtask_due_date'])->format('Y-m-d');
            } else {
                // Auto-calculate based on PST time
                // If created before 2pm PST: due date is same day
                // If created after 2pm PST: due date is following day
                $pstNow = Carbon::now('America/Los_Angeles');
                $dueDate = $pstNow->copy();
                
                if ($pstNow->hour >= 14) {
                    // After 2pm PST, set due date to next day
                    $dueDate->addDay();
                }
                // Before 2pm PST, due date is same day (already set)
                
                // Format as date only (Y-m-d)
                $dueDateFormatted = $dueDate->format('Y-m-d');
            }
            
            $subtaskData = [
                'title' => 'Add Products',
                'description' => 'Subtask for: ' . $this->record->title,
                'parent_task_id' => $this->record->id,
                'project_id' => $this->record->project_id,
                'assigned_to' => $assignedTo,
                'due_date' => $dueDateFormatted,
                'created_by' => auth()->id(),
                'priority' => $this->record->priority ?? 1,
                'actions' => [[
                    'action' => 'created',
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()->name ?? 'System',
                    'timestamp' => now()->toDateTimeString(),
                ]],
            ];
            
            Task::create($subtaskData);
            $subtasksCreated++;
        }
        
        // Create subtask if "Size Grade or Thread Colors Needed" is checked
        if (!empty($data['design_details']) && $data['design_details'] === true) {
            // Determine assigned user: use manual selection if provided, otherwise auto-assign to Ephraim
            $assignedTo = null;
            if (!empty($data['subtask_assigned_to'])) {
                // Manual override provided
                $assignedTo = $data['subtask_assigned_to'];
            } else {
                // Auto-assign to Ephraim
                $ephraimUser = User::where('email', 'ephraim.ethos@gmail.com')->first();
                $assignedTo = $ephraimUser ? $ephraimUser->id : null;
            }
            
            // Determine due date: use manual selection if provided, otherwise set to same day
            $dueDateFormatted = null;
            if (!empty($data['subtask_due_date'])) {
                // Manual override provided
                $dueDateFormatted = Carbon::parse($data['subtask_due_date'])->format('Y-m-d');
            } else {
                // Auto-set to same day
                $dueDateFormatted = Carbon::now()->format('Y-m-d');
            }
            
            $subtaskData = [
                'title' => 'Size Grade or Thread Colors Needed - ' . $this->record->title,
                'description' => 'Subtask for: ' . $this->record->title,
                'parent_task_id' => $this->record->id,
                'project_id' => $this->record->project_id,
                'assigned_to' => $assignedTo,
                'due_date' => $dueDateFormatted,
                'created_by' => auth()->id(),
                'priority' => $this->record->priority ?? 1,
                'actions' => [[
                    'action' => 'created',
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()->name ?? 'System',
                    'timestamp' => now()->toDateTimeString(),
                ]],
            ];
            
            Task::create($subtaskData);
            $subtasksCreated++;
        }
        
        // Create subtask if "Website Images" is checked OR if project type is "Website Images"
        $isWebsiteImagesProject = false;
        if (!empty($this->record->project_id)) {
            $project = \App\Models\Project::find($this->record->project_id);
            $isWebsiteImagesProject = $project && $project->name === 'Website Images';
        }
        
        // Check if Website Images subtask already exists
        $websiteImagesSubtaskExists = Task::where('parent_task_id', $this->record->id)
            ->where('title', 'like', 'Website Images%')
            ->exists();
        
        if ((!empty($data['website_images']) && $data['website_images'] === true || $isWebsiteImagesProject) && !$websiteImagesSubtaskExists) {
            // Determine assigned user: use manual selection if provided, otherwise auto-assign to Vinzent
            $assignedTo = null;
            if (!empty($data['website_images_subtask_assigned_to'])) {
                // Manual override provided
                $assignedTo = $data['website_images_subtask_assigned_to'];
            } else {
                // Auto-assign to Vinzent
                $vinzentUser = User::where('email', 'vinzent@ethos.community')->first();
                $assignedTo = $vinzentUser ? $vinzentUser->id : null;
            }
            
            // Determine due date: use manual selection if provided, otherwise set to next day
            $dueDateFormatted = null;
            if (!empty($data['website_images_subtask_due_date'])) {
                // Manual override provided
                $dueDateFormatted = Carbon::parse($data['website_images_subtask_due_date'])->format('Y-m-d');
            } else {
                // Auto-set to next day
                $dueDateFormatted = Carbon::now()->addDay()->format('Y-m-d');
            }
            
            $subtaskData = [
                'title' => 'Website Images - ' . $this->record->title,
                'description' => 'Subtask for: ' . $this->record->title,
                'parent_task_id' => $this->record->id,
                'project_id' => $this->record->project_id,
                'assigned_to' => $assignedTo,
                'due_date' => $dueDateFormatted,
                'created_by' => auth()->id(),
                'priority' => $this->record->priority ?? 1,
                'website_images_notes' => $data['website_images_notes'] ?? null,
                'website_images_attachments' => $data['website_images_attachments'] ?? null,
                'actions' => [[
                    'action' => 'created',
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()->name ?? 'System',
                    'timestamp' => now()->toDateTimeString(),
                ]],
            ];
            
            Task::create($subtaskData);
            $subtasksCreated++;
        }
        
        if ($subtasksCreated > 0) {
            Notification::make()
                ->title($subtasksCreated . ' subtask(s) created successfully')
                ->success()
                ->send();
        }
    }
}
