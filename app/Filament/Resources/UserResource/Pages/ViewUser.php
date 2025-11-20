<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('User Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('profile_picture')
                            ->label('Profile Picture')
                            ->formatStateUsing(function ($state, $record) {
                                if ($state) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<img src="' . \Illuminate\Support\Facades\Storage::disk('public')->url($state) . '" alt="' . htmlspecialchars($record->name) . '" class="w-32 h-32 rounded-full object-cover border-4 border-gray-200 dark:border-gray-700" />'
                                    );
                                }
                                
                                // Generate initials
                                $initials = '';
                                if ($record->first_name && $record->last_name) {
                                    $initials = strtoupper(substr($record->first_name, 0, 1) . substr($record->last_name, 0, 1));
                                } else {
                                    $name = $record->name ?? '';
                                    $parts = explode(' ', trim($name));
                                    if (count($parts) >= 2) {
                                        $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
                                    } else {
                                        $initials = strtoupper(substr($name, 0, 1));
                                    }
                                }
                                
                                return new \Illuminate\Support\HtmlString(
                                    '<div class="w-32 h-32 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center border-4 border-gray-200 dark:border-gray-700"><span class="text-4xl font-semibold text-primary-600 dark:text-primary-400">' . htmlspecialchars($initials) . '</span></div>'
                                );
                            })
                            ->html()
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('name')
                            ->label('Display Name')
                            ->weight('bold')
                            ->size('lg'),
                        Infolists\Components\TextEntry::make('first_name')
                            ->label('First Name'),
                        Infolists\Components\TextEntry::make('last_name')
                            ->label('Last Name'),
                        Infolists\Components\TextEntry::make('email')
                            ->label('Email')
                            ->copyable()
                            ->icon('heroicon-m-clipboard-document'),
                        Infolists\Components\IconEntry::make('is_active')
                            ->label('Status')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),
                        Infolists\Components\TextEntry::make('roles.name')
                            ->label('Roles')
                            ->badge()
                            ->color('primary'),
                        Infolists\Components\TextEntry::make('last_login_at')
                            ->label('Last Login')
                            ->dateTime('M d, Y g:i A')
                            ->placeholder('Never'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime('M d, Y g:i A'),
                    ])
                    ->columns(2),
            ]);
    }
}
