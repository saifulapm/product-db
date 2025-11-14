<?php

namespace App\Filament\Resources\FranchiseeResource\Pages;

use App\Filament\Resources\FranchiseeResource;
use App\Filament\Resources\FranchiseeResource\Widgets\FranchiseeLogosWidget;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFranchisee extends EditRecord
{
    protected static string $resource = FranchiseeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            FranchiseeLogosWidget::class => [
                'recordId' => $this->record->id,
            ],
        ];
    }
}
