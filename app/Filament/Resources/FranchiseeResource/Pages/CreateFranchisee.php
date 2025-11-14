<?php

namespace App\Filament\Resources\FranchiseeResource\Pages;

use App\Filament\Resources\FranchiseeResource;
use App\Filament\Resources\FranchiseeResource\Widgets\FranchiseeLogosWidget;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFranchisee extends CreateRecord
{
    protected static string $resource = FranchiseeResource::class;

    protected function getFooterWidgets(): array
    {
        return [
            FranchiseeLogosWidget::class,
        ];
    }

    protected function afterCreate(): void
    {
        // Refresh widgets to pass the new record ID
        $this->dispatch('$refresh');
    }
}
