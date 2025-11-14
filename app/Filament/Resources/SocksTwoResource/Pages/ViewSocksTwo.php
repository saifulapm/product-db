<?php

namespace App\Filament\Resources\SocksTwoResource\Pages;

use App\Filament\Resources\SocksTwoResource;
use App\Filament\Resources\SocksTwoResource\Widgets\SockTwoGallery;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSocksTwo extends ViewRecord
{
    protected static string $resource = SocksTwoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            SockTwoGallery::class,
        ];
    }
}

