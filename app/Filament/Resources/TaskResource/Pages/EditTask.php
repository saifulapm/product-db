<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Models\Task;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

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
        
        // Handle "Add Products" subtask
        $addProductsSubtask = Task::where('parent_task_id', $record->id)
            ->where('title', 'like', 'Add Products%')
            ->first();
        
        if (!empty($data['add_products']) && $data['add_products'] === true) {
            $subtaskData = [
                'title' => 'Add Products',
                'description' => 'Subtask for: ' . $record->title,
                'parent_task_id' => $record->id,
                'project_id' => $record->project_id,
                'assigned_to' => $data['add_products_subtask_assigned_to'] ?? null,
                'due_date' => $data['add_products_subtask_due_date'] ?? null,
                'created_by' => $record->created_by,
                'priority' => $record->priority ?? 1,
            ];
            
            if ($addProductsSubtask) {
                $addProductsSubtask->update($subtaskData);
            } else {
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
            if ($addProductsSubtask) {
                $addProductsSubtask->delete();
                $notifications[] = 'Add Products subtask removed';
            }
        }
        
        // Handle "Size Grade or Thread Colors Needed" subtask
        $designDetailsSubtask = Task::where('parent_task_id', $record->id)
            ->where('title', 'like', 'Size Grade or Thread Colors Needed%')
            ->first();
        
        if (!empty($data['design_details']) && $data['design_details'] === true) {
            $subtaskData = [
                'title' => 'Size Grade or Thread Colors Needed - ' . $record->title,
                'description' => 'Subtask for: ' . $record->title,
                'parent_task_id' => $record->id,
                'project_id' => $record->project_id,
                'assigned_to' => $data['subtask_assigned_to'] ?? null,
                'due_date' => $data['subtask_due_date'] ?? null,
                'created_by' => $record->created_by,
                'priority' => $record->priority ?? 1,
            ];
            
            if ($designDetailsSubtask) {
                $designDetailsSubtask->update($subtaskData);
            } else {
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
            if ($designDetailsSubtask) {
                $designDetailsSubtask->delete();
                $notifications[] = 'Size Grade/Thread Colors subtask removed';
            }
        }
        
        // Handle "Website Images" subtask
        $websiteImagesSubtask = Task::where('parent_task_id', $record->id)
            ->where('title', 'like', 'Website Images%')
            ->first();
        
        if (!empty($data['website_images']) && $data['website_images'] === true) {
            $subtaskData = [
                'title' => 'Website Images - ' . $record->title,
                'description' => 'Subtask for: ' . $record->title,
                'parent_task_id' => $record->id,
                'project_id' => $record->project_id,
                'assigned_to' => $data['website_images_subtask_assigned_to'] ?? null,
                'due_date' => $data['website_images_subtask_due_date'] ?? null,
                'created_by' => $record->created_by,
                'priority' => $record->priority ?? 1,
            ];
            
            if ($websiteImagesSubtask) {
                $websiteImagesSubtask->update($subtaskData);
            } else {
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
            if ($websiteImagesSubtask) {
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
