<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\FabricUsageCalculatorWidget;
use Filament\Pages\Page;

class PoSubmission extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationLabel = 'Fabric Calculator';
    
    protected static ?string $title = 'Fabric Calculator';
    
    protected static ?string $navigationGroup = 'Design Tools';
    
    protected static ?int $navigationSort = 3;
    
    protected static string $view = 'filament.pages.po-submission';
    
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
            FabricUsageCalculatorWidget::class,
        ];
    }
    
    public function getColumns(): int | string | array
    {
        return 1;
    }
}

