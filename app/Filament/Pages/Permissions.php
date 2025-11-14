<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\Role;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class Permissions extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Permissions';

    protected static ?string $title = 'User Permissions';

    protected static ?string $navigationGroup = 'Admin';

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'admin/permissions';

    protected static string $view = 'filament.pages.permissions';

    public function table(Table $table): Table
    {
        return $table
            ->query(User::query()->with('roles'))
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['first_name', 'last_name', 'name'])
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Current Roles')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Super Admin' => 'danger',
                        'Admin' => 'warning',
                        default => 'primary',
                    })
                    ->separator(',')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Filter by Role')
                    ->relationship('roles', 'name')
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Users'),
            ])
            ->actions([
                Tables\Actions\Action::make('assign_super_admin')
                    ->label('Assign Super Admin')
                    ->icon('heroicon-o-shield-check')
                    ->color('danger')
                    ->visible(fn (User $record) => !$record->hasRole('super-admin'))
                    ->requiresConfirmation()
                    ->modalHeading('Assign Super Admin Role')
                    ->modalDescription(fn (User $record) => 'Are you sure you want to assign the Super Admin role to ' . $record->full_name . '? This will give them full access to all features.')
                    ->action(function (User $record) {
                        $superAdminRole = Role::where('slug', 'super-admin')->first();
                        if ($superAdminRole) {
                            $record->assignRole($superAdminRole);
                            Notification::make()
                                ->title('Super Admin role assigned')
                                ->success()
                                ->body($record->full_name . ' now has Super Admin access.')
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('remove_super_admin')
                    ->label('Remove Super Admin')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->visible(fn (User $record) => $record->hasRole('super-admin'))
                    ->requiresConfirmation()
                    ->modalHeading('Remove Super Admin Role')
                    ->modalDescription(fn (User $record) => 'Are you sure you want to remove the Super Admin role from ' . $record->full_name . '?')
                    ->action(function (User $record) {
                        $superAdminRole = Role::where('slug', 'super-admin')->first();
                        if ($superAdminRole) {
                            $record->removeRole($superAdminRole);
                            Notification::make()
                                ->title('Super Admin role removed')
                                ->success()
                                ->body($record->full_name . ' no longer has Super Admin access.')
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('manage_roles')
                    ->label('Manage All Roles')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->color('primary')
                    ->url(fn (User $record) => \App\Filament\Resources\UserResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(false),
            ])
            ->defaultSort('first_name');
    }
}

