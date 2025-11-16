<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\QuickCadBuilderWidget;
use Filament\Pages\Page;

class QuickCadBuilder extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    
    protected static ?string $navigationLabel = 'Quick CAD Builder';
    
    protected static ?string $navigationGroup = 'Design Tools';
    
    protected static ?int $navigationSort = 1;
    
    protected static string $view = 'filament.pages.quick-cad-builder';
    
    protected function getHeaderWidgets(): array
    {
        return [];
    }
    
    protected function getFooterWidgets(): array
    {
        return [];
    }
    
    public function getWidgets(): array
    {
        return [
            QuickCadBuilderWidget::class,
        ];
    }
    
    public function getColumns(): int | string | array
    {
        return 1;
    }
}

