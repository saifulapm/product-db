<?php

namespace App\Filament\Resources\SupplyResource\Pages;

use App\Filament\Resources\SupplyResource;
use App\Filament\Resources\SupplyResource\Widgets\SupplyShipmentTracking;
use App\Filament\Resources\SupplyResource\Widgets\SupplyUsageStatistics;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSupply extends ViewRecord
{
    protected static string $resource = SupplyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            SupplyUsageStatistics::class,
            SupplyShipmentTracking::class,
        ];
    }
}
