<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MasterTaskResource\Pages;
use App\Models\Task;
use Filament\Resources\Resource;

class MasterTaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Home';
    
    protected static ?string $navigationGroup = 'Tasks';
    
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('tasks.home.view');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMasterTasks::route('/'),
        ];
    }
}
