<?php

namespace App\Filament\Resources\SockStyleResource\Pages;

use App\Filament\Resources\SockStyleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSockStyle extends ViewRecord
{
    protected static string $resource = SockStyleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
