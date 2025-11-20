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

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();
        
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
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $record = $this->getRecord();
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
                            ->color('info'),
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
                        Infolists\Components\TextEntry::make('assignedUser.name')
                            ->label('Assigned To')
                            ->badge()
                            ->color('info')
                            ->placeholder('Unassigned'),
                        Infolists\Components\TextEntry::make('due_date')
                            ->label('Due Date')
                            ->date('M d, Y')
                            ->badge()
                            ->color('warning')
                            ->visible(fn (): bool => !empty($record->due_date)),
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
                Infolists\Components\Section::make('Attachments')
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
                Infolists\Components\Section::make('Subtasks')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('subtasks')
                            ->schema([
                                Infolists\Components\TextEntry::make('title')
                                    ->label('Subtask')
                                    ->weight('bold')
                                    ->url(fn (Task $subtask): string => TaskResource::getUrl('view', ['record' => $subtask]))
                                    ->color('primary')
                                    ->extraAttributes(['class' => 'underline']),
                                Infolists\Components\TextEntry::make('assignedUser.name')
                                    ->label('Assigned To')
                                    ->badge()
                                    ->color('info'),
                                Infolists\Components\TextEntry::make('due_date')
                                    ->label('Due Date')
                                    ->date('M d, Y'),
                                InfolistActions::make([
                                    Action::make('toggle_complete')
                                        ->label(fn (Task $record): string => $record->is_completed ? 'Mark Incomplete' : 'Mark Complete')
                                        ->icon(fn (Task $record): string => $record->is_completed ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                                        ->color(fn (Task $record): string => $record->is_completed ? 'warning' : 'success')
                                        ->action(function (Task $record) {
                                            // Ensure we're working with a fresh instance of the specific subtask
                                            $subtask = Task::findOrFail($record->id);
                                            
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
                                        })
                                        ->requiresConfirmation()
                                        ->modalHeading(fn (Task $record): string => $record->is_completed ? 'Mark Subtask as Incomplete?' : 'Mark Subtask as Complete?')
                                        ->modalDescription(fn (Task $record): string => $record->is_completed ? 'This will mark the subtask "' . $record->title . '" as incomplete.' : 'This will mark the subtask "' . $record->title . '" as complete.')
                                        ->modalSubmitActionLabel(fn (Task $record): string => $record->is_completed ? 'Mark Incomplete' : 'Mark Complete'),
                                ])
                                    ->label('Actions'),
                            ])
                            ->columns(4)
                            ->visible(fn (): bool => $record->subtasks()->count() > 0),
                    ])
                    ->visible(fn (): bool => !$isSubtask && $record->subtasks()->count() > 0)
                    ->collapsible()
                    ->columnSpanFull(),
                Infolists\Components\Section::make('Chat')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('comments')
                            ->schema([
                                Infolists\Components\TextEntry::make('user.name')
                                    ->label('User')
                                    ->weight('bold')
                                    ->badge()
                                    ->color('info'),
                                Infolists\Components\TextEntry::make('comment')
                                    ->label('')
                                    ->formatStateUsing(function ($state, TaskComment $record) {
                                        $comment = htmlspecialchars($state);
                                        
                                        // Parse @mentions and create links
                                        if (!empty($record->tagged_users)) {
                                            $users = User::whereIn('id', $record->tagged_users)->get()->keyBy('id');
                                            foreach ($users as $userId => $user) {
                                                $mention = '@' . $user->name;
                                                $replacement = '<span class="bg-blue-100 text-blue-800 px-1 rounded font-medium">@' . htmlspecialchars($user->name) . '</span>';
                                                $comment = str_replace($mention, $replacement, $comment);
                                            }
                                        }
                                        
                                        return $comment;
                                    })
                                    ->html()
                                    ->columnSpanFull(),
                                Infolists\Components\TextEntry::make('reactions_display')
                                    ->label('')
                                    ->formatStateUsing(function ($state, TaskComment $record) {
                                        $reactions = $record->reactions ?? [];
                                        
                                        $thumbsUpUsers = $reactions['thumbs_up'] ?? [];
                                        $heartUsers = $reactions['heart'] ?? [];
                                        
                                        $thumbsUpCount = count($thumbsUpUsers);
                                        $heartCount = count($heartUsers);
                                        
                                        if ($thumbsUpCount === 0 && $heartCount === 0) {
                                            return '';
                                        }
                                        
                                        $html = '<div class="flex items-center gap-3 mt-2 text-sm text-gray-600">';
                                        
                                        if ($thumbsUpCount > 0) {
                                            $html .= '<span class="flex items-center gap-1">👍 <span>' . $thumbsUpCount . '</span></span>';
                                        }
                                        
                                        if ($heartCount > 0) {
                                            $html .= '<span class="flex items-center gap-1">❤️ <span>' . $heartCount . '</span></span>';
                                        }
                                        
                                        $html .= '</div>';
                                        
                                        return $html;
                                    })
                                    ->html()
                                    ->columnSpanFull()
                                    ->visible(fn (TaskComment $record): bool => !empty($record->reactions) && (count($record->reactions['thumbs_up'] ?? []) > 0 || count($record->reactions['heart'] ?? []) > 0)),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('')
                                    ->dateTime('M d, Y g:i A')
                                    ->color('gray')
                                    ->size('sm'),
                                // Replies section
                                Infolists\Components\RepeatableEntry::make('replies')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('user.name')
                                            ->label('User')
                                            ->weight('bold')
                                            ->badge()
                                            ->color('info')
                                            ->size('sm'),
                                        Infolists\Components\TextEntry::make('comment')
                                            ->label('')
                                            ->formatStateUsing(function ($state, TaskComment $reply) {
                                                $comment = htmlspecialchars($state);
                                                
                                                // Parse @mentions
                                                if (!empty($reply->tagged_users)) {
                                                    $users = User::whereIn('id', $reply->tagged_users)->get()->keyBy('id');
                                                    foreach ($users as $userId => $user) {
                                                        $mention = '@' . $user->name;
                                                        $replacement = '<span class="bg-blue-100 text-blue-800 px-1 rounded font-medium text-xs">@' . htmlspecialchars($user->name) . '</span>';
                                                        $comment = str_replace($mention, $replacement, $comment);
                                                    }
                                                }
                                                
                                                return $comment;
                                            })
                                            ->html()
                                            ->columnSpanFull(),
                                        Infolists\Components\TextEntry::make('reactions_display')
                                            ->label('')
                                            ->formatStateUsing(function ($state, TaskComment $reply) {
                                                $reactions = $reply->reactions ?? [];
                                                
                                                $thumbsUpUsers = $reactions['thumbs_up'] ?? [];
                                                $heartUsers = $reactions['heart'] ?? [];
                                                
                                                $thumbsUpCount = count($thumbsUpUsers);
                                                $heartCount = count($heartUsers);
                                                
                                                if ($thumbsUpCount === 0 && $heartCount === 0) {
                                                    return '';
                                                }
                                                
                                                $html = '<div class="flex items-center gap-3 mt-1 text-xs text-gray-600">';
                                                
                                                if ($thumbsUpCount > 0) {
                                                    $html .= '<span class="flex items-center gap-1">👍 <span>' . $thumbsUpCount . '</span></span>';
                                                }
                                                
                                                if ($heartCount > 0) {
                                                    $html .= '<span class="flex items-center gap-1">❤️ <span>' . $heartCount . '</span></span>';
                                                }
                                                
                                                $html .= '</div>';
                                                
                                                return $html;
                                            })
                                            ->html()
                                            ->columnSpanFull()
                                            ->visible(fn (TaskComment $reply): bool => !empty($reply->reactions) && (count($reply->reactions['thumbs_up'] ?? []) > 0 || count($reply->reactions['heart'] ?? []) > 0)),
                                        Infolists\Components\TextEntry::make('created_at')
                                            ->label('')
                                            ->dateTime('M d, Y g:i A')
                                            ->color('gray')
                                            ->size('xs'),
                                        InfolistActions::make([
                                            Action::make('react_thumbs_up_reply')
                                                ->label(function ($record) {
                                                    $reactions = $record->reactions ?? [];
                                                    $thumbsUpUsers = $reactions['thumbs_up'] ?? [];
                                                    $count = count($thumbsUpUsers);
                                                    return '👍' . ($count > 0 ? ' ' . $count : '');
                                                })
                                                ->icon('heroicon-o-hand-thumb-up')
                                                ->color(function ($record) {
                                                    $reactions = $record->reactions ?? [];
                                                    $thumbsUpUsers = $reactions['thumbs_up'] ?? [];
                                                    return in_array(auth()->id(), $thumbsUpUsers) ? 'primary' : 'gray';
                                                })
                                                ->size('sm')
                                                ->button()
                                                ->action(function ($record) {
                                                    $reactions = $record->reactions ?? [];
                                                    $thumbsUpUsers = $reactions['thumbs_up'] ?? [];
                                                    $currentUserId = auth()->id();
                                                    
                                                    if (in_array($currentUserId, $thumbsUpUsers)) {
                                                        $thumbsUpUsers = array_values(array_diff($thumbsUpUsers, [$currentUserId]));
                                                    } else {
                                                        $thumbsUpUsers[] = $currentUserId;
                                                    }
                                                    
                                                    $reactions['thumbs_up'] = $thumbsUpUsers;
                                                    $record->update(['reactions' => $reactions]);
                                                    
                                                    $this->record->refresh();
                                                }),
                                            Action::make('react_heart_reply')
                                                ->label(function ($record) {
                                                    $reactions = $record->reactions ?? [];
                                                    $heartUsers = $reactions['heart'] ?? [];
                                                    $count = count($heartUsers);
                                                    return '❤️' . ($count > 0 ? ' ' . $count : '');
                                                })
                                                ->icon('heroicon-o-heart')
                                                ->color(function ($record) {
                                                    $reactions = $record->reactions ?? [];
                                                    $heartUsers = $reactions['heart'] ?? [];
                                                    return in_array(auth()->id(), $heartUsers) ? 'danger' : 'gray';
                                                })
                                                ->size('sm')
                                                ->button()
                                                ->action(function ($record) {
                                                    $reactions = $record->reactions ?? [];
                                                    $heartUsers = $reactions['heart'] ?? [];
                                                    $currentUserId = auth()->id();
                                                    
                                                    if (in_array($currentUserId, $heartUsers)) {
                                                        $heartUsers = array_values(array_diff($heartUsers, [$currentUserId]));
                                                    } else {
                                                        $heartUsers[] = $currentUserId;
                                                    }
                                                    
                                                    $reactions['heart'] = $heartUsers;
                                                    $record->update(['reactions' => $reactions]);
                                                    
                                                    $this->record->refresh();
                                                }),
                                        ])
                                            ->label('Actions'),
                                    ])
                                    ->columns(1)
                                    ->getStateUsing(fn (TaskComment $record): \Illuminate\Database\Eloquent\Collection => $record->replies()->with('user')->get())
                                    ->visible(fn (TaskComment $record): bool => $record->replies()->count() > 0)
                                    ->columnSpanFull(),
                                InfolistActions::make([
                                    Action::make('reply')
                                        ->label('Reply')
                                        ->icon('heroicon-o-arrow-turn-down-right')
                                        ->color('gray')
                                        ->size('sm')
                                        ->form([
                                            Forms\Components\Textarea::make('comment')
                                                ->label('Reply')
                                                ->required()
                                                ->rows(3)
                                                ->placeholder('Type your reply... Use @username to tag users'),
                                            Forms\Components\Select::make('tagged_users')
                                                ->label('Tag Users')
                                                ->multiple()
                                                ->options(User::pluck('name', 'id'))
                                                ->searchable()
                                                ->preload()
                                                ->placeholder('Select users to tag'),
                                        ])
                                        ->action(function (array $data, TaskComment $record) {
                                            $comment = $data['comment'];
                                            $taggedUserIds = $data['tagged_users'] ?? [];
                                            
                                            // Parse @mentions
                                            preg_match_all('/@(\w+)/', $comment, $matches);
                                            if (!empty($matches[1])) {
                                                $mentionedUserIds = User::whereIn('name', $matches[1])->pluck('id')->toArray();
                                                $taggedUserIds = array_unique(array_merge($taggedUserIds, $mentionedUserIds));
                                            }
                                            
                                            TaskComment::create([
                                                'task_id' => $this->getRecord()->id,
                                                'parent_comment_id' => $record->id,
                                                'user_id' => auth()->id(),
                                                'comment' => $comment,
                                                'tagged_users' => !empty($taggedUserIds) ? $taggedUserIds : null,
                                            ]);
                                            
                                            // Send notifications
                                            if (!empty($taggedUserIds)) {
                                                foreach ($taggedUserIds as $userId) {
                                                    if ($userId != auth()->id()) {
                                                        $taggedUser = User::find($userId);
                                                        if ($taggedUser) {
                                                            Notification::make()
                                                                ->title('You were mentioned in a reply')
                                                                ->body('@' . auth()->user()->name . ' mentioned you in a reply')
                                                                ->info()
                                                                ->sendToDatabase($taggedUser);
                                                        }
                                                    }
                                                }
                                            }
                                            
                                            Notification::make()
                                                ->title('Reply added successfully')
                                                ->success()
                                                ->send();
                                            
                                            $this->record->refresh();
                                        }),
                                    Action::make('react_thumbs_up')
                                        ->label(function ($record) {
                                            $reactions = $record->reactions ?? [];
                                            $thumbsUpUsers = $reactions['thumbs_up'] ?? [];
                                            $currentUserId = auth()->id();
                                            $count = count($thumbsUpUsers);
                                            $hasReacted = in_array($currentUserId, $thumbsUpUsers);
                                            return '👍' . ($count > 0 ? ' ' . $count : '');
                                        })
                                        ->icon('heroicon-o-hand-thumb-up')
                                        ->color(function ($record) {
                                            $reactions = $record->reactions ?? [];
                                            $thumbsUpUsers = $reactions['thumbs_up'] ?? [];
                                            return in_array(auth()->id(), $thumbsUpUsers) ? 'primary' : 'gray';
                                        })
                                        ->size('sm')
                                        ->button()
                                        ->action(function ($record) {
                                            $reactions = $record->reactions ?? [];
                                            $thumbsUpUsers = $reactions['thumbs_up'] ?? [];
                                            $currentUserId = auth()->id();
                                            
                                            if (in_array($currentUserId, $thumbsUpUsers)) {
                                                // Remove reaction
                                                $thumbsUpUsers = array_values(array_diff($thumbsUpUsers, [$currentUserId]));
                                            } else {
                                                // Add reaction
                                                $thumbsUpUsers[] = $currentUserId;
                                            }
                                            
                                            $reactions['thumbs_up'] = $thumbsUpUsers;
                                            $record->update(['reactions' => $reactions]);
                                            
                                            $this->record->refresh();
                                        }),
                                    Action::make('react_heart')
                                        ->label(function ($record) {
                                            $reactions = $record->reactions ?? [];
                                            $heartUsers = $reactions['heart'] ?? [];
                                            $count = count($heartUsers);
                                            return '❤️' . ($count > 0 ? ' ' . $count : '');
                                        })
                                        ->icon('heroicon-o-heart')
                                        ->color(function ($record) {
                                            $reactions = $record->reactions ?? [];
                                            $heartUsers = $reactions['heart'] ?? [];
                                            return in_array(auth()->id(), $heartUsers) ? 'danger' : 'gray';
                                        })
                                        ->size('sm')
                                        ->button()
                                        ->action(function ($record) {
                                            $reactions = $record->reactions ?? [];
                                            $heartUsers = $reactions['heart'] ?? [];
                                            $currentUserId = auth()->id();
                                            
                                            if (in_array($currentUserId, $heartUsers)) {
                                                // Remove reaction
                                                $heartUsers = array_values(array_diff($heartUsers, [$currentUserId]));
                                            } else {
                                                // Add reaction
                                                $heartUsers[] = $currentUserId;
                                            }
                                            
                                            $reactions['heart'] = $heartUsers;
                                            $record->update(['reactions' => $reactions]);
                                            
                                            $this->record->refresh();
                                        }),
                                ])
                                    ->label('Actions'),
                            ])
                            ->columns(1)
                            ->getStateUsing(fn (): \Illuminate\Database\Eloquent\Collection => $record->comments()->whereNull('parent_comment_id')->with(['user', 'replies.user'])->get())
                            ->visible(fn (): bool => $record->comments()->whereNull('parent_comment_id')->count() > 0),
                        InfolistActions::make([
                            Action::make('add_comment')
                                ->label('Add Comment')
                                ->icon('heroicon-o-chat-bubble-left-right')
                                ->color('primary')
                                ->form([
                                    Forms\Components\Textarea::make('comment')
                                        ->label('Comment')
                                        ->required()
                                        ->rows(4)
                                        ->placeholder('Type your comment here... Use @username to tag users')
                                        ->helperText('Type @ followed by a username to tag someone (e.g., @john)'),
                                    Forms\Components\Select::make('tagged_users')
                                        ->label('Tag Users')
                                        ->multiple()
                                        ->options(User::pluck('name', 'id'))
                                        ->searchable()
                                        ->preload()
                                        ->placeholder('Select users to tag')
                                        ->helperText('You can also type @username in the comment above'),
                                ])
                                ->action(function (array $data) {
                                    $record = $this->getRecord();
                                    $comment = $data['comment'];
                                    
                                    // Extract @mentions from comment text
                                    $taggedUserIds = $data['tagged_users'] ?? [];
                                    
                                    // Also parse @mentions from comment text
                                    preg_match_all('/@(\w+)/', $comment, $matches);
                                    if (!empty($matches[1])) {
                                        $mentionedUserIds = User::whereIn('name', $matches[1])->pluck('id')->toArray();
                                        $taggedUserIds = array_unique(array_merge($taggedUserIds, $mentionedUserIds));
                                    }
                                    
                                    $taskComment = TaskComment::create([
                                        'task_id' => $record->id,
                                        'user_id' => auth()->id(),
                                        'comment' => $comment,
                                        'tagged_users' => !empty($taggedUserIds) ? $taggedUserIds : null,
                                    ]);
                                    
                                    // Send notifications to tagged users
                                    if (!empty($taggedUserIds)) {
                                        foreach ($taggedUserIds as $userId) {
                                            if ($userId != auth()->id()) {
                                                $taggedUser = User::find($userId);
                                                if ($taggedUser) {
                                                    Notification::make()
                                                        ->title('You were mentioned in a comment')
                                                        ->body('@' . auth()->user()->name . ' mentioned you in a comment on task: ' . $record->title)
                                                        ->info()
                                                        ->sendToDatabase($taggedUser);
                                                }
                                            }
                                        }
                                    }
                                    
                                    Notification::make()
                                        ->title('Comment added successfully')
                                        ->success()
                                        ->send();
                                    
                                    $this->record->refresh();
                                }),
                        ])
                            ->label('Actions'),
                    ])
                    ->collapsible()
                    ->collapsed(false)
                    ->columnSpanFull(),
                Infolists\Components\Section::make('History Timeline')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('history_timeline')
                            ->schema([
                                Infolists\Components\TextEntry::make('type')
                                    ->label('')
                                    ->formatStateUsing(function ($state) {
                                        return match($state) {
                                            'created' => '📝',
                                            'completed' => '✅',
                                            'reopened' => '🔄',
                                            'subtask_completed' => '✓',
                                            default => '•',
                                        };
                                    })
                                    ->size('lg')
                                    ->columnSpan(1),
                                Infolists\Components\TextEntry::make('event_description')
                                    ->label('Event')
                                    ->weight('bold')
                                    ->columnSpan(2),
                                Infolists\Components\TextEntry::make('user_name')
                                    ->label('User')
                                    ->badge()
                                    ->color('info')
                                    ->columnSpan(1),
                                Infolists\Components\TextEntry::make('timestamp')
                                    ->label('Date & Time')
                                    ->formatStateUsing(function ($state) {
                                        if (!$state) {
                                            return 'N/A';
                                        }
                                        try {
                                            return \Carbon\Carbon::parse($state)->format('M d, Y g:i A');
                                        } catch (\Exception $e) {
                                            return $state;
                                        }
                                    })
                                    ->badge()
                                    ->color('gray')
                                    ->columnSpan(1),
                            ])
                            ->columns(5)
                            ->getStateUsing(function () use ($record) {
                                $timeline = [];
                                
                                // Add task creation event
                                if ($record->created_at) {
                                    $timeline[] = [
                                        'type' => 'created',
                                        'event_description' => 'Task created',
                                    'user_name' => $record->creator->name ?? 'Unknown',
                                        'timestamp' => $record->created_at->toDateTimeString(),
                                        'data' => [],
                                    ];
                                }
                                
                                // Add subtask completion events
                                foreach ($record->subtasks()->whereNotNull('completed_at')->orderBy('completed_at', 'asc')->get() as $subtask) {
                                    // Try to get user from subtask's actions array
                                    $subtaskActions = $subtask->actions ?? [];
                                    $completedBy = 'System';
                                    foreach (array_reverse($subtaskActions) as $action) {
                                        if (isset($action['action']) && $action['action'] === 'completed' && isset($action['user_name'])) {
                                            $completedBy = $action['user_name'];
                                            break;
                                        }
                                    }
                                    
                                    $timeline[] = [
                                        'type' => 'subtask_completed',
                                        'event_description' => 'Subtask completed: ' . $subtask->title,
                                        'user_name' => $completedBy,
                                        'timestamp' => $subtask->completed_at->toDateTimeString(),
                                        'data' => [
                                            'subtask_title' => $subtask->title,
                                            'subtask_id' => $subtask->id,
                                        ],
                                    ];
                                }
                                
                                // Add actions from the actions array
                                $actions = $record->actions ?? [];
                                foreach ($actions as $action) {
                                    if (isset($action['action']) && isset($action['timestamp'])) {
                                        $actionType = $action['action'];
                                        
                                        // Skip 'created' if we already have it from created_at
                                        if ($actionType === 'created' && $record->created_at) {
                                            continue;
                                        }
                                        
                                        $eventDescription = match($actionType) {
                                            'completed' => 'Task marked as complete',
                                            'incompleted' => 'Task reopened',
                                            default => ucfirst($actionType),
                                        };
                                        
                                        $timeline[] = [
                                            'type' => $actionType === 'incompleted' ? 'reopened' : $actionType,
                                            'event_description' => $eventDescription,
                                            'user_name' => $action['user_name'] ?? 'System',
                                            'timestamp' => $action['timestamp'],
                                            'data' => $action,
                                        ];
                                    }
                                }
                                
                                // Sort timeline by timestamp (oldest first)
                                usort($timeline, function ($a, $b) {
                                    $timeA = $a['timestamp'] ?? '';
                                    $timeB = $b['timestamp'] ?? '';
                                    return strcmp($timeA, $timeB);
                                });
                                
                                return $timeline;
                            })
                            ->visible(fn (): bool => $record->created_at || ($record->subtasks()->whereNotNull('completed_at')->count() > 0) || (!empty($record->actions) && is_array($record->actions) && count($record->actions) > 0)),
                    ])
                    ->collapsible()
                    ->collapsed(fn (): bool => !$isSubtask)
                    ->columnSpanFull(),
            ]);
    }
}
