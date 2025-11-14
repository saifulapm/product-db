<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class TowelsControl extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationLabel = 'Controls';

    protected static ?string $navigationGroup = 'Towels';

    protected static ?int $navigationSort = 0;

    protected static bool $shouldRegisterNavigation = true;

    protected static string $routePath = 'towels/control';

    protected static ?string $title = 'Towels Control';

    protected static string $view = 'filament.pages.towels-control';
}
