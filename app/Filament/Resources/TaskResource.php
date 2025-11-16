<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
use App\Models\Task;
use App\Models\User;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $navigationLabel = 'All Tasks';

    protected static ?string $navigationGroup = 'Tasks';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Task Details')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Task Title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter task title'),
                        Forms\Components\Select::make('project_id')
                            ->label('Task Type')
                            ->relationship('project', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Select a task type')
                            ->required()
                            ->live(),
                        Forms\Components\Select::make('store')
                            ->label('Select Store')
                            ->options([
                                'ethos-merch.com' => 'ethos-merch.com',
                                'sellwithethos.com' => 'sellwithethos.com',
                                'Other' => 'Other',
                            ])
                            ->searchable()
                            ->placeholder('Select a store')
                            ->live(),
                        Forms\Components\Select::make('collection')
                            ->label('Select Collection')
                            ->options([
                                // Add collection options here
                            ])
                            ->searchable()
                            ->placeholder('Select a collection')
                            ->visible(function ($get) {
                                return !empty($get('store'));
                            }),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(4)
                            ->columnSpanFull()
                            ->placeholder('Enter task description'),
                        FileUpload::make('attachments')
                            ->label('Attachments')
                            ->helperText('Upload files related to this task')
                            ->multiple()
                            ->acceptedFileTypes(['image/*', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                            ->maxSize(10240) // 10MB
                            ->directory('tasks/attachments')
                            ->disk('public')
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->columnSpanFull(),
                        Forms\Components\Select::make('assigned_to')
                            ->label('Assign To')
                            ->relationship('assignedUser', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Select a user'),
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Due Date')
                            ->displayFormat('M d, Y')
                            ->placeholder('Select due date'),
                        Forms\Components\Select::make('priority')
                            ->label('Priority')
                            ->options([
                                1 => 'Low',
                                2 => 'Medium',
                                3 => 'High',
                                4 => 'Urgent',
                            ])
                            ->placeholder('Select priority (optional)'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Product Additions')
                    ->schema([
                        Forms\Components\Toggle::make('add_products')
                            ->label('Add Products')
                            ->default(true)
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\Select::make('add_products_subtask_assigned_to')
                            ->label('Assign Subtask To')
                            ->options(User::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->placeholder('Select a user for the subtask')
                            ->visible(function ($get) {
                                return $get('add_products') === true;
                            }),
                        Forms\Components\DatePicker::make('add_products_subtask_due_date')
                            ->label('Subtask Due Date')
                            ->displayFormat('M d, Y')
                            ->placeholder('Select due date for the subtask')
                            ->visible(function ($get) {
                                return $get('add_products') === true;
                            }),
                        Forms\Components\Toggle::make('design_details')
                            ->label('Size Grade or Thread Colors Needed')
                            ->helperText('Check if size grade or thread colors are needed for this product')
                            ->default(false)
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\Select::make('subtask_assigned_to')
                            ->label('Assign Subtask To')
                            ->options(User::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->placeholder('Select a user for the subtask')
                            ->visible(function ($get) {
                                return $get('design_details') === true;
                            }),
                        Forms\Components\DatePicker::make('subtask_due_date')
                            ->label('Subtask Due Date')
                            ->displayFormat('M d, Y')
                            ->placeholder('Select due date for the subtask')
                            ->visible(function ($get) {
                                return $get('design_details') === true;
                            }),
                        Forms\Components\Toggle::make('website_images')
                            ->label('Website Images')
                            ->helperText('Check if website images are needed for this product')
                            ->default(false)
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\Select::make('website_images_subtask_assigned_to')
                            ->label('Assign Subtask To')
                            ->options(User::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->placeholder('Select a user for the subtask')
                            ->visible(function ($get) {
                                return $get('website_images') === true;
                            }),
                        Forms\Components\DatePicker::make('website_images_subtask_due_date')
                            ->label('Subtask Due Date')
                            ->displayFormat('M d, Y')
                            ->placeholder('Select due date for the subtask')
                            ->visible(function ($get) {
                                return $get('website_images') === true;
                            }),
                    ])
                    ->columns(2)
                    ->visible(function ($get) {
                        $projectId = $get('project_id');
                        if (!$projectId) {
                            return false;
                        }
                        $project = \App\Models\Project::find($projectId);
                        return $project && $project->name === 'Product Additions';
                    })
                    ->columnSpanFull(),
                Forms\Components\Section::make('Product Adjustments')
                    ->schema([
                        Forms\Components\Toggle::make('check_full_collection')
                            ->label('Check Full Collection')
                            ->default(false)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('eid')
                            ->label('EID')
                            ->maxLength(255)
                            ->placeholder('Enter EID')
                            ->columnSpan(5),
                        Forms\Components\TextInput::make('adjustment_needed')
                            ->label('Adjustment Needed')
                            ->maxLength(255)
                            ->placeholder('Enter adjustment needed')
                            ->columnSpan(5),
                        Forms\Components\Toggle::make('check_full_product')
                            ->label('Check Full Product')
                            ->default(false)
                            ->columnSpan(2),
                        Forms\Components\Repeater::make('additional_eids')
                            ->label('Additional EIDs')
                            ->schema([
                                Forms\Components\TextInput::make('eid')
                                    ->label('EID')
                                    ->maxLength(255)
                                    ->required()
                                    ->placeholder('Enter EID')
                                    ->columnSpan(5),
                                Forms\Components\TextInput::make('adjustment_needed')
                                    ->label('Adjustment Needed')
                                    ->maxLength(255)
                                    ->placeholder('Enter adjustment needed')
                                    ->columnSpan(5),
                                Forms\Components\Toggle::make('check_full_product')
                                    ->label('Check Full Product')
                                    ->default(false)
                                    ->columnSpan(2),
                            ])
                            ->columns(12)
                            ->defaultItems(0)
                            ->addActionLabel('Add Another EID')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['eid'] ?? 'New EID')
                            ->reorderable(true)
                            ->columnSpanFull(),
                    ])
                    ->columns(12)
                    ->visible(function ($get) {
                        $projectId = $get('project_id');
                        if (!$projectId) {
                            return false;
                        }
                        $project = \App\Models\Project::find($projectId);
                        return $project && $project->name === 'Product Adjustments';
                    })
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Show only subtasks (tasks with a parent_task_id), eager load relationships
                $query->whereNotNull('parent_task_id')
                    ->with(['parentTask', 'assignedUser', 'creator']);
            })
            ->columns([
                Tables\Columns\TextColumn::make('parentTask.title')
                    ->label('Task Title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->formatStateUsing(function (Task $record) {
                        $parentTask = $record->parentTask;
                        return $parentTask ? htmlspecialchars($parentTask->title) : 'Unknown Parent';
                    })
                    ->url(function (Task $record): ?string {
                        $parentTask = $record->parentTask;
                        return $parentTask ? TaskResource::getUrl('view', ['record' => $parentTask]) : null;
                    })
                    ->openUrlInNewTab(false)
                    ->wrap(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Subtask')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->formatStateUsing(function (Task $record) {
                        return htmlspecialchars($record->title);
                    })
                    ->url(fn (Task $record): string => TaskResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(false)
                    ->wrap(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Task Type')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(function (Task $record): string {
                        $title = $record->title;
                        // Extract subtask name (before " - " if it exists)
                        $subtaskName = strpos($title, ' - ') !== false 
                            ? trim(explode(' - ', $title)[0])
                            : trim($title);
                        
                        return match($subtaskName) {
                            'Add Products' => 'warning',
                            'Size Grade or Thread Colors Needed' => 'success',
                            'Website Images' => 'purple',
                            default => 'success',
                        };
                    })
                    ->formatStateUsing(function (Task $record): string {
                        $title = $record->title;
                        // Extract and return only the subtask name (before " - " if it exists)
                        return strpos($title, ' - ') !== false 
                            ? explode(' - ', $title)[0] 
                            : $title;
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Assigned To')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('M d, Y')
                    ->sortable()
                    ->color(fn (Task $record): string => 
                        $record->due_date && $record->due_date->isPast() && !$record->is_completed 
                            ? 'danger' 
                            : 'gray'
                    ),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed At')
                    ->dateTime('M d, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('task_type_filter')
                    ->label('Task Type')
                    ->options([
                        'main' => 'Main Tasks Only',
                        'subtask' => 'Subtasks Only',
                        'all' => 'All Tasks',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] === 'main') {
                            return $query->whereNull('parent_task_id');
                        }
                        if ($data['value'] === 'subtask') {
                            return $query->whereNotNull('parent_task_id');
                        }
                        return $query;
                    }),
                Tables\Filters\SelectFilter::make('is_completed')
                    ->label('Status')
                    ->options([
                        0 => 'Incomplete',
                        1 => 'Completed',
                    ]),
                Tables\Filters\SelectFilter::make('project_id')
                    ->label('Task Type')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('assigned_to')
                    ->label('Assigned To')
                    ->relationship('assignedUser', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('priority')
                    ->label('Priority')
                    ->options([
                        1 => 'Low',
                        2 => 'Medium',
                        3 => 'High',
                        4 => 'Urgent',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('toggle_complete')
                    ->icon('heroicon-o-check-circle')
                    ->color(fn (Task $record): string => $record->is_completed ? 'success' : 'warning')
                    ->iconButton()
                    ->action(function (Task $record) {
                        if ($record->is_completed) {
                            $record->markAsIncomplete();
                            Notification::make()
                                ->title('Task marked as incomplete')
                                ->success()
                                ->send();
                        } else {
                            $record->markAsCompleted();
                            Notification::make()
                                ->title('Task marked as completed')
                                ->success()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading(fn (Task $record): string => $record->is_completed ? 'Mark Task as Incomplete?' : 'Mark Task as Complete?')
                    ->modalDescription(fn (Task $record): string => $record->is_completed 
                        ? 'This will mark the task as incomplete.' 
                        : 'This will mark the task as complete.')
                    ->modalSubmitActionLabel(fn (Task $record): string => $record->is_completed ? 'Mark Incomplete' : 'Mark Complete'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_complete')
                        ->label('Mark as Complete')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->markAsCompleted();
                            Notification::make()
                                ->title('Tasks marked as completed')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('mark_incomplete')
                        ->label('Mark as Incomplete')
                        ->icon('heroicon-o-x-circle')
                        ->color('gray')
                        ->action(function ($records) {
                            $records->each->markAsIncomplete();
                            Notification::make()
                                ->title('Tasks marked as incomplete')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'view' => Pages\ViewTask::route('/{record}'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}
