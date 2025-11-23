<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Infolists\Components\Actions as InfolistActions;
use Filament\Infolists\Components\Actions\Action;
use Filament\Forms;

class ViewTask extends ViewRecord
{
    protected static string $resource = TaskResource::class;
    
    protected static string $view = 'filament.resources.task-resource.pages.view-task';
    
    public function getBreadcrumbs(): array
    {
        $record = $this->getRecord();
        $breadcrumbs = [];
        
        // Always start with Dashboard/Home
        $breadcrumbs[TaskResource::getUrl('index')] = 'Tasks';
        
        // If this is a subtask, add parent task to breadcrumbs
        if (!empty($record->parent_task_id)) {
            $parentTask = $record->parentTask;
            if ($parentTask) {
                $breadcrumbs[TaskResource::getUrl('view', ['record' => $parentTask])] = $parentTask->title;
            }
            // Add current subtask
            $breadcrumbs[] = $record->title;
        } else {
            // For main tasks, just add the task title
            $breadcrumbs[] = $record->title;
        }
        
        return $breadcrumbs;
    }

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();
        $recordId = $record->id; // Capture the ID explicitly
        
        return [
            Actions\Action::make('toggle_complete')
                ->label(fn (): string => $record->is_completed ? 'Mark Incomplete' : 'Mark Complete')
                ->icon(fn (): string => $record->is_completed ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->color(fn (): string => $record->is_completed ? 'warning' : 'success')
                ->action(function () {
                    $record = $this->getRecord();
                    
                    if ($record->is_completed) {
                        $record->markAsIncomplete();
                        Notification::make()
                            ->title('Task "' . $record->title . '" marked as incomplete')
                            ->success()
                            ->send();
                    } else {
                        $record->markAsCompleted();
                        Notification::make()
                            ->title('Task "' . $record->title . '" marked as complete')
                            ->success()
                            ->send();
                    }
                    
                    $this->record->refresh();
                })
                ->requiresConfirmation()
                ->modalHeading(fn (): string => $record->is_completed ? 'Mark Task as Incomplete?' : 'Mark Task as Complete?')
                ->modalDescription(fn (): string => $record->is_completed 
                    ? 'This will mark the task "' . $record->title . '" as incomplete.' 
                    : 'This will mark the task "' . $record->title . '" as complete.')
                ->modalSubmitActionLabel(fn (): string => $record->is_completed ? 'Mark Incomplete' : 'Mark Complete'),
            Actions\EditAction::make()
                ->url(function () {
                    $currentRecord = $this->getRecord();
                    return TaskResource::getUrl('edit', ['record' => $currentRecord->id]);
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $record = $this->getRecord();
        
        // Load parent task relationship if this is a subtask
        if (!empty($record->parent_task_id)) {
            $record->load('parentTask');
        }
        
        $isSubtask = !empty($record->parent_task_id);
        
        return $infolist
            ->schema([
                // Show parent task link for subtasks
                Infolists\Components\Section::make('Parent Task')
                    ->schema([
                        Infolists\Components\TextEntry::make('parentTask.title')
                            ->label('Parent Task')
                            ->weight('bold')
                            ->size('lg')
                            ->url(fn (): ?string => $record->parentTask ? TaskResource::getUrl('view', ['record' => $record->parentTask]) : null)
                            ->color('primary')
                            ->extraAttributes(['class' => 'underline']),
                    ])
                    ->visible(fn (): bool => $isSubtask)
                    ->collapsible()
                    ->collapsed(false)
                    ->columnSpanFull(),
                Infolists\Components\Section::make($isSubtask ? 'Subtask Details' : 'Task Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('title')
                            ->label($isSubtask ? 'Subtask Title' : 'Task Title')
                            ->weight('bold')
                            ->size('lg'),
                        Infolists\Components\TextEntry::make('project.name')
                            ->label('Task Type')
                            ->badge()
                            ->color(function () use ($record, $isSubtask) {
                                if ($isSubtask) {
                                    $title = $record->title;
                                    $subtaskName = strpos($title, ' - ') !== false 
                                        ? trim(explode(' - ', $title)[0])
                                        : trim($title);
                                    
                                    return match($subtaskName) {
                                        'Add Products' => 'warning',
                                        'Size Grade or Thread Colors Needed' => 'success',
                                        'Website Images' => 'purple',
                                        default => 'info',
                                    };
                                }
                                return 'info';
                            })
                            ->formatStateUsing(function ($state) use ($record, $isSubtask) {
                                if ($isSubtask) {
                                    // For subtasks, extract the subtask name from the title
                                    $title = $record->title;
                                    return strpos($title, ' - ') !== false 
                                        ? explode(' - ', $title)[0] 
                                        : $title;
                                }
                                // For main tasks, show the project name
                                return $state;
                            }),
                        Infolists\Components\TextEntry::make('store')
                            ->label('Store')
                            ->formatStateUsing(function ($state) {
                                if (empty($state)) {
                                    return null;
                                }
                                
                                $urls = [
                                    'ethos-merch.com' => 'https://ethos-merch.com',
                                    'sellwithethos.com' => 'https://sellwithethos.com',
                                    'Other' => null,
                                ];
                                
                                $url = $urls[$state] ?? null;
                                
                                if ($url) {
                                    return '<a href="' . $url . '" target="_blank" class="text-primary-600 hover:text-primary-800 underline">' . htmlspecialchars($state) . '</a>';
                                }
                                
                                return htmlspecialchars($state);
                            })
                            ->html()
                            ->badge()
                            ->color('gray')
                            ->visible(fn (): bool => !empty($record->store)),
                        Infolists\Components\TextEntry::make('collection')
                            ->label('Collection')
                            ->badge()
                            ->color('gray')
                            ->visible(fn (): bool => !empty($record->collection)),
                        Infolists\Components\TextEntry::make('description')
                            ->label('Description')
                            ->placeholder('No description provided')
                            ->columnSpanFull()
                            ->visible(fn (): bool => !empty($record->description)),
                        Infolists\Components\TextEntry::make('priority_label')
                            ->label('Priority')
                            ->badge()
                            ->color(fn (): string => $record->priority_color),
                        Infolists\Components\TextEntry::make('is_completed')
                            ->label('Status')
                            ->formatStateUsing(fn ($state): string => $state ? 'Completed' : 'Incomplete')
                            ->badge()
                            ->color(fn ($state): string => $state ? 'success' : 'gray'),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(false)
                    ->columnSpanFull(),
                // Show parent task attachments for subtasks
                Infolists\Components\Section::make('Parent Task Attachments')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('parentTask.attachments')
                            ->schema([
                                Infolists\Components\TextEntry::make('file')
                                    ->label('')
                                    ->formatStateUsing(function ($state) {
                                        if (empty($state)) {
                                            return '';
                                        }
                                        
                                        $url = asset('storage/' . $state);
                                        $fileName = basename($state);
                                        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                                        
                                        // Check if it's an image
                                        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
                                        if (in_array($extension, $imageExtensions)) {
                                            return '<div class="mb-2">
                                                <img src="' . $url . '" alt="' . htmlspecialchars($fileName) . '" class="max-w-xs rounded-lg shadow-sm mb-1" style="max-height: 200px;">
                                                <br><a href="' . $url . '" target="_blank" class="text-primary-600 hover:text-primary-800 underline text-sm">' . htmlspecialchars($fileName) . '</a>
                                            </div>';
                                        } else {
                                            return '<div class="mb-2">
                                                <a href="' . $url . '" target="_blank" class="text-primary-600 hover:text-primary-800 underline">' . htmlspecialchars($fileName) . '</a>
                                            </div>';
                                        }
                                    })
                                    ->html()
                                    ->columnSpanFull(),
                            ])
                            ->columns(1)
                            ->getStateUsing(function () use ($record): array {
                                if ($record->parentTask && !empty($record->parentTask->attachments) && is_array($record->parentTask->attachments)) {
                                    return $record->parentTask->attachments;
                                }
                                return [];
                            })
                            ->visible(function () use ($record, $isSubtask): bool {
                                return $isSubtask && 
                                       $record->parentTask && 
                                       !empty($record->parentTask->attachments) && 
                                       is_array($record->parentTask->attachments) && 
                                       count($record->parentTask->attachments) > 0;
                            }),
                    ])
                    ->visible(function () use ($record, $isSubtask): bool {
                        return $isSubtask && 
                               $record->parentTask && 
                               !empty($record->parentTask->attachments) && 
                               is_array($record->parentTask->attachments) && 
                               count($record->parentTask->attachments) > 0;
                    })
                    ->collapsible()
                    ->collapsed(false)
                    ->columnSpanFull(),
                // Website Images Notes section (for Website Images subtasks)
                Infolists\Components\Section::make('Website Images Notes')
                    ->schema([
                        Infolists\Components\TextEntry::make('website_images_notes')
                            ->label('Notes')
                            ->placeholder('No notes provided')
                            ->columnSpanFull(),
                    ])
                    ->visible(function () use ($record, $isSubtask): bool {
                        if (!$isSubtask) {
                            return false;
                        }
                        // Check if this is a Website Images subtask
                        $title = $record->title ?? '';
                        $subtaskName = strpos($title, ' - ') !== false 
                            ? trim(explode(' - ', $title)[0])
                            : trim($title);
                        return $subtaskName === 'Website Images';
                    })
                    ->collapsible()
                    ->collapsed(false)
                    ->columnSpanFull(),
                // Website Images Attachments section (for Website Images subtasks)
                Infolists\Components\Section::make('Upload PDF you\'d like website images for')
                    ->schema([
                        Infolists\Components\TextEntry::make('website_images_attachments_display')
                            ->label('')
                            ->formatStateUsing(function () use ($record) {
                                if (empty($record->website_images_attachments) || !is_array($record->website_images_attachments) || count($record->website_images_attachments) === 0) {
                                    return '<p class="text-gray-500 dark:text-gray-400">No files uploaded</p>';
                                }
                                
                                $html = '';
                                foreach ($record->website_images_attachments as $file) {
                                    if (empty($file)) {
                                        continue;
                                    }
                                    
                                    $url = asset('storage/' . $file);
                                    $fileName = basename($file);
                                    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                                    
                                    // Check if it's an image
                                    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
                                    if (in_array($extension, $imageExtensions)) {
                                        $html .= '<div class="mb-2">
                                            <img src="' . $url . '" alt="' . htmlspecialchars($fileName) . '" class="max-w-xs rounded-lg shadow-sm mb-1" style="max-height: 200px;">
                                            <br><a href="' . $url . '" target="_blank" class="text-primary-600 hover:text-primary-800 underline text-sm">' . htmlspecialchars($fileName) . '</a>
                                        </div>';
                                    } else {
                                        $html .= '<div class="mb-2">
                                            <a href="' . $url . '" target="_blank" class="text-primary-600 hover:text-primary-800 underline">' . htmlspecialchars($fileName) . '</a>
                                        </div>';
                                    }
                                }
                                
                                return $html ?: '<p class="text-gray-500 dark:text-gray-400">No files uploaded</p>';
                            })
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->visible(function () use ($record, $isSubtask): bool {
                        if (!$isSubtask) {
                            return false;
                        }
                        // Check if this is a Website Images subtask
                        $title = $record->title ?? '';
                        $subtaskName = strpos($title, ' - ') !== false 
                            ? trim(explode(' - ', $title)[0])
                            : trim($title);
                        return $subtaskName === 'Website Images';
                    })
                    ->collapsible()
                    ->collapsed(false)
                    ->columnSpanFull(),
                // Show subtask's own attachments (or task attachments if it's a main task)
                Infolists\Components\Section::make($isSubtask ? 'Subtask Attachments' : 'Attachments')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('attachments')
                            ->schema([
                                Infolists\Components\TextEntry::make('file')
                                    ->label('')
                                    ->formatStateUsing(function ($state) {
                                        if (empty($state)) {
                                            return '';
                                        }
                                        
                                        $url = asset('storage/' . $state);
                                        $fileName = basename($state);
                                        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                                        
                                        // Check if it's an image
                                        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
                                        if (in_array($extension, $imageExtensions)) {
                                            return '<div class="mb-2">
                                                <img src="' . $url . '" alt="' . htmlspecialchars($fileName) . '" class="max-w-xs rounded-lg shadow-sm mb-1" style="max-height: 200px;">
                                                <br><a href="' . $url . '" target="_blank" class="text-primary-600 hover:text-primary-800 underline text-sm">' . htmlspecialchars($fileName) . '</a>
                                            </div>';
                                        } else {
                                            return '<div class="mb-2">
                                                <a href="' . $url . '" target="_blank" class="text-primary-600 hover:text-primary-800 underline">' . htmlspecialchars($fileName) . '</a>
                                            </div>';
                                        }
                                    })
                                    ->html()
                                    ->columnSpanFull(),
                            ])
                            ->columns(1)
                            ->getStateUsing(fn (): array => $record->attachments ?? [])
                            ->visible(fn (): bool => !empty($record->attachments) && is_array($record->attachments) && count($record->attachments) > 0),
                    ])
                    ->visible(fn (): bool => !empty($record->attachments) && is_array($record->attachments) && count($record->attachments) > 0)
                    ->collapsible()
                    ->collapsed(false)
                    ->columnSpanFull(),
            ]);
    }

    public function toggleSubtaskComplete(int $subtaskId): void
    {
        $subtask = Task::findOrFail($subtaskId);
        
        // Toggle the completion status
        if ($subtask->is_completed) {
            $subtask->markAsIncomplete();
            Notification::make()
                ->title('Subtask "' . $subtask->title . '" marked as incomplete')
                ->success()
                ->send();
        } else {
            $subtask->markAsCompleted();
            Notification::make()
                ->title('Subtask "' . $subtask->title . '" marked as complete')
                ->success()
                ->send();
        }
        
        // Force refresh of the parent record and its relationships
        $this->record->refresh();
        $this->record->load('subtasks');
    }
}
