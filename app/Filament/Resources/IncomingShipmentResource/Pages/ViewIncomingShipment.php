<?php

namespace App\Filament\Resources\IncomingShipmentResource\Pages;

use App\Filament\Resources\IncomingShipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewIncomingShipment extends ViewRecord
{
    protected static string $resource = IncomingShipmentResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
    
    protected function getFooterWidgets(): array
    {
        return [
            IncomingShipmentResource\Widgets\IncomingItemsTableWidget::class,
        ];
    }
}
