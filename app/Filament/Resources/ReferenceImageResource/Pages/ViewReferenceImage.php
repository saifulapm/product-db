<?php

namespace App\Filament\Resources\ReferenceImageResource\Pages;

use App\Filament\Resources\ReferenceImageResource;
use App\Filament\Resources\ReferenceImageResource\Widgets\ReferenceImageGallery;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewReferenceImage extends ViewRecord
{
    protected static string $resource = ReferenceImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
    
    protected function getFooterWidgets(): array
    {
        return [
            ReferenceImageGallery::class,
        ];
    }
}
