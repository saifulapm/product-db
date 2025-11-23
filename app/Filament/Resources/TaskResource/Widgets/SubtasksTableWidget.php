<?php

namespace App\Filament\Resources\TaskResource\Widgets;

use App\Filament\Resources\TaskResource;
use App\Models\Task;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class SubtasksTableWidget extends TableWidget
{
    protected int | string | array $columnSpan = 'full';
    
    public ?int $parentTaskId = null;

    public function mount(): void
    {
        $recordId = request()->route('record');
        if ($recordId) {
            $parentTask = Task::find($recordId);
            if ($parentTask && empty($parentTask->parent_task_id)) {
                $this->parentTaskId = $parentTask->id;
            }
        }
    }

    public static function canView(): bool
    {
        $recordId = request()->route('record');
        if (!$recordId) {
            return false;
        }
        
        $parentTask = Task::find($recordId);
        if (!$parentTask) {
            return false;
        }
        
        // Only show if this is not a subtask itself and has subtasks
        return empty($parentTask->parent_task_id) && $parentTask->subtasks()->count() > 0;
    }

    public function table(Table $table): Table
    {
        // Use stored parentTaskId if available, otherwise try to get from route
        if (!$this->parentTaskId) {
            $recordId = request()->route('record');
            if ($recordId) {
                $parentTask = Task::find($recordId);
                if ($parentTask && empty($parentTask->parent_task_id)) {
                    $this->parentTaskId = $parentTask->id;
                }
            }
        }
        
        $query = $this->parentTaskId 
            ? Task::query()
                ->where('parent_task_id', $this->parentTaskId)
                ->with(['assignedUser', 'project'])
            : Task::query()->whereRaw('1 = 0');
        
        return $table
            ->query($query)
            ->recordUrl(fn (Task $record): string => TaskResource::getUrl('view', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Subtask')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->url(fn (Task $record): string => TaskResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(false)
                    ->color('primary')
                    ->wrap()
                    ->tooltip('Click to view subtask'),
                Tables\Columns\TextColumn::make('title')
                    ->label('Task Type')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(function (Task $record): string {
                        $title = $record->title;
                        $subtaskName = strpos($title, ' - ') !== false 
                            ? trim(explode(' - ', $title)[0])
                            : trim($title);
                        
                        return match($subtaskName) {
                            'Add Products' => 'warning',
                            'Size Grade or Thread Colors Needed' => 'success',
                            'Website Images' => 'purple',
                            default => 'gray',
                        };
                    })
                    ->formatStateUsing(function (Task $record): string {
                        $title = $record->title;
                        return strpos($title, ' - ') !== false 
                            ? explode(' - ', $title)[0] 
                            : $title;
                    }),
                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Assigned To')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->default('Unassigned')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('M d, Y')
                    ->sortable()
                    ->badge()
                    ->color(fn (Task $record): string => 
                        $record->due_date && $record->due_date->isPast() && !$record->is_completed 
                            ? 'danger' 
                            : 'gray'
                    )
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_completed')
                    ->label('Status')
                    ->boolean()
                    ->sortable()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('assignedUser')
                    ->label('Assigned To')
                    ->relationship('assignedUser', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_completed')
                    ->label('Status')
                    ->placeholder('All subtasks')
                    ->trueLabel('Completed only')
                    ->falseLabel('Incomplete only'),
            ])
            ->actions([
                Tables\Actions\Action::make('toggle_complete')
                    ->label(fn (Task $record): string => $record->is_completed ? 'Mark Incomplete' : 'Mark Complete')
                    ->icon(fn (Task $record): string => $record->is_completed ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (Task $record): string => $record->is_completed ? 'warning' : 'success')
                    ->action(function (Task $record) {
                        if ($record->is_completed) {
                            $record->markAsIncomplete();
                            Notification::make()
                                ->title('Subtask "' . $record->title . '" marked as incomplete')
                                ->success()
                                ->send();
                        } else {
                            $record->markAsCompleted();
                            Notification::make()
                                ->title('Subtask "' . $record->title . '" marked as complete')
                                ->success()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading(fn (Task $record): string => $record->is_completed ? 'Mark Subtask as Incomplete?' : 'Mark Subtask as Complete?')
                    ->modalDescription(fn (Task $record): string => $record->is_completed ? 'This will mark the subtask "' . $record->title . '" as incomplete.' : 'This will mark the subtask "' . $record->title . '" as complete.')
                    ->modalSubmitActionLabel(fn (Task $record): string => $record->is_completed ? 'Mark Incomplete' : 'Mark Complete'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_complete')
                        ->label('Mark as Complete')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if (!$record->is_completed) {
                                    $record->markAsCompleted();
                                    $count++;
                                }
                            }
                            
                            Notification::make()
                                ->title($count > 0 ? $count . ' subtask(s) marked as complete' : 'No subtasks were updated')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Mark Subtasks as Complete?')
                        ->modalDescription('This will mark all selected subtasks as complete.')
                        ->modalSubmitActionLabel('Mark Complete')
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('mark_incomplete')
                        ->label('Mark as Incomplete')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->is_completed) {
                                    $record->markAsIncomplete();
                                    $count++;
                                }
                            }
                            
                            Notification::make()
                                ->title($count > 0 ? $count . ' subtask(s) marked as incomplete' : 'No subtasks were updated')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Mark Subtasks as Incomplete?')
                        ->modalDescription('This will mark all selected subtasks as incomplete.')
                        ->modalSubmitActionLabel('Mark Incomplete')
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('due_date', 'asc')
            ->poll('30s')
            ->heading('Subtasks')
            ->emptyStateHeading('No subtasks')
            ->emptyStateDescription('This task has no subtasks yet.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }
}

