<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Models\Task;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Populate subtask fields if subtasks exist
        $addProductsSubtask = Task::where('parent_task_id', $this->record->id)
            ->where('title', 'like', 'Add Products%')
            ->first();
        if ($addProductsSubtask) {
            $data['add_products_subtask_assigned_to'] = $addProductsSubtask->assigned_to;
            $data['add_products_subtask_due_date'] = $addProductsSubtask->due_date;
        }
        
        $designDetailsSubtask = Task::where('parent_task_id', $this->record->id)
            ->where('title', 'like', 'Size Grade or Thread Colors Needed%')
            ->first();
        if ($designDetailsSubtask) {
            $data['subtask_assigned_to'] = $designDetailsSubtask->assigned_to;
            $data['subtask_due_date'] = $designDetailsSubtask->due_date;
        }
        
        $websiteImagesSubtask = Task::where('parent_task_id', $this->record->id)
            ->where('title', 'like', 'Website Images%')
            ->first();
        if ($websiteImagesSubtask) {
            $data['website_images_subtask_assigned_to'] = $websiteImagesSubtask->assigned_to;
            $data['website_images_subtask_due_date'] = $websiteImagesSubtask->due_date;
        }
        
        return $data;
    }

    protected function afterSave(): void
    {
        $data = $this->form->getState();
        $record = $this->record;
        $notifications = [];
        
        // Only process subtasks for main tasks (tasks without a parent)
        // Subtasks should not trigger subtask creation logic
        if ($record->parent_task_id) {
            return;
        }
        
        // Only process subtasks if the form contains the relevant toggle fields
        // This prevents creating subtasks when editing other fields like assigned_to or due_date
        $hasSubtaskFields = isset($data['add_products']) || isset($data['design_details']) || isset($data['website_images']);
        
        if (!$hasSubtaskFields) {
            // No subtask fields in form, skip subtask processing
            return;
        }
        
        // Handle "Add Products" subtask
        $addProductsSubtask = Task::where('parent_task_id', $record->id)
            ->where('title', 'Add Products')
            ->first();
        
        if (!empty($data['add_products']) && $data['add_products'] === true) {
            // Determine assigned user: use manual selection if provided, otherwise auto-assign to Grace
            $assignedTo = null;
            if (!empty($data['add_products_subtask_assigned_to'])) {
                // Manual override provided
                $assignedTo = $data['add_products_subtask_assigned_to'];
            } else {
                // Auto-assign to Grace (only when creating new subtask, not updating existing)
                if (!$addProductsSubtask) {
                    $graceUser = User::where('email', 'grace@ethos.community')->first();
                    $assignedTo = $graceUser ? $graceUser->id : null;
                }
            }
            
            // Determine due date: use manual selection if provided, otherwise calculate based on PST time
            $dueDateFormatted = null;
            if (!empty($data['add_products_subtask_due_date'])) {
                // Manual override provided
                $dueDateFormatted = Carbon::parse($data['add_products_subtask_due_date'])->format('Y-m-d');
            } else {
                // Auto-calculate based on PST time (only when creating new subtask, not updating existing)
                if (!$addProductsSubtask) {
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
            }
            
            $subtaskData = [
                'title' => 'Add Products',
                'description' => 'Subtask for: ' . $record->title,
                'parent_task_id' => $record->id,
                'project_id' => $record->project_id,
                'assigned_to' => $assignedTo,
                'due_date' => $dueDateFormatted,
                'created_by' => $record->created_by,
                'priority' => $record->priority ?? 1,
            ];
            
            if ($addProductsSubtask) {
                // Update existing subtask - use manual override if provided, otherwise keep existing values
                $updateData = [
                    'title' => $subtaskData['title'],
                    'description' => $subtaskData['description'],
                    'project_id' => $subtaskData['project_id'],
                    'priority' => $subtaskData['priority'],
                ];
                
                // Use manual override if provided, otherwise keep existing value
                if (isset($assignedTo)) {
                    $updateData['assigned_to'] = $assignedTo;
                } else {
                    $updateData['assigned_to'] = $addProductsSubtask->assigned_to;
                }
                
                // Use manual override if provided, otherwise keep existing value
                if (isset($dueDateFormatted)) {
                    $updateData['due_date'] = $dueDateFormatted;
                } else {
                    $updateData['due_date'] = $addProductsSubtask->due_date;
                }
                
                $addProductsSubtask->update($updateData);
            } else {
                // Only create if it doesn't exist
                $subtaskData['actions'] = [[
                    'action' => 'created',
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()->name ?? 'System',
                    'timestamp' => now()->toDateTimeString(),
                ]];
                Task::create($subtaskData);
                $notifications[] = 'Add Products subtask created';
            }
        } else {
            // Only delete if toggle is explicitly false and subtask exists
            if ($addProductsSubtask && isset($data['add_products']) && $data['add_products'] === false) {
                $addProductsSubtask->delete();
                $notifications[] = 'Add Products subtask removed';
            }
        }
        
        // Handle "Size Grade or Thread Colors Needed" subtask
        $designDetailsSubtask = Task::where('parent_task_id', $record->id)
            ->where('title', 'like', 'Size Grade or Thread Colors Needed%')
            ->first();
        
        if (!empty($data['design_details']) && $data['design_details'] === true) {
            $subtaskTitle = 'Size Grade or Thread Colors Needed - ' . $record->title;
            $subtaskData = [
                'title' => $subtaskTitle,
                'description' => 'Subtask for: ' . $record->title,
                'parent_task_id' => $record->id,
                'project_id' => $record->project_id,
                'assigned_to' => $data['subtask_assigned_to'] ?? null,
                'due_date' => $data['subtask_due_date'] ?? null,
                'created_by' => $record->created_by,
                'priority' => $record->priority ?? 1,
            ];
            
            if ($designDetailsSubtask) {
                // Update existing subtask
                $designDetailsSubtask->update($subtaskData);
            } else {
                // Only create if it doesn't exist
                $subtaskData['actions'] = [[
                    'action' => 'created',
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()->name ?? 'System',
                    'timestamp' => now()->toDateTimeString(),
                ]];
                Task::create($subtaskData);
                $notifications[] = 'Size Grade/Thread Colors subtask created';
            }
        } else {
            // Only delete if toggle is explicitly false and subtask exists
            if ($designDetailsSubtask && isset($data['design_details']) && $data['design_details'] === false) {
                $designDetailsSubtask->delete();
                $notifications[] = 'Size Grade/Thread Colors subtask removed';
            }
        }
        
        // Handle "Website Images" subtask
        $websiteImagesSubtask = Task::where('parent_task_id', $record->id)
            ->where('title', 'like', 'Website Images%')
            ->first();
        
        if (!empty($data['website_images']) && $data['website_images'] === true) {
            $subtaskTitle = 'Website Images - ' . $record->title;
            $subtaskData = [
                'title' => $subtaskTitle,
                'description' => 'Subtask for: ' . $record->title,
                'parent_task_id' => $record->id,
                'project_id' => $record->project_id,
                'assigned_to' => $data['website_images_subtask_assigned_to'] ?? null,
                'due_date' => $data['website_images_subtask_due_date'] ?? null,
                'created_by' => $record->created_by,
                'priority' => $record->priority ?? 1,
            ];
            
            if ($websiteImagesSubtask) {
                // Update existing subtask
                $websiteImagesSubtask->update($subtaskData);
            } else {
                // Only create if it doesn't exist
                $subtaskData['actions'] = [[
                    'action' => 'created',
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()->name ?? 'System',
                    'timestamp' => now()->toDateTimeString(),
                ]];
                Task::create($subtaskData);
                $notifications[] = 'Website Images subtask created';
            }
        } else {
            // Only delete if toggle is explicitly false and subtask exists
            if ($websiteImagesSubtask && isset($data['website_images']) && $data['website_images'] === false) {
                $websiteImagesSubtask->delete();
                $notifications[] = 'Website Images subtask removed';
            }
        }
        
        if (!empty($notifications)) {
            Notification::make()
                ->title(implode(', ', $notifications))
                ->success()
                ->send();
        }
    }
}
