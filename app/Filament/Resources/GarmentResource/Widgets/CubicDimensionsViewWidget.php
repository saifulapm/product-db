<?php

namespace App\Filament\Resources\GarmentResource\Widgets;

use App\Models\Garment;
use Filament\Widgets\Widget;
use Livewire\Attributes\Reactive;

class CubicDimensionsViewWidget extends Widget
{
    protected static string $view = 'filament.resources.garment-resource.widgets.cubic-dimensions-view-widget';

    protected int | string | array $columnSpan = 'full';

    #[Reactive]
    public ?Garment $record = null;

    protected function getRecord(): ?Garment
    {
        // Try to get record from reactive property first
        if ($this->record instanceof Garment) {
            return $this->record;
        }

        // Fallback: get record from route parameter
        $recordId = request()->route('record');
        
        if ($recordId) {
            return Garment::find($recordId);
        }
        
        return null;
    }

    public function getCubicDimensions(): ?array
    {
        $record = $this->getRecord();
        
        if (!$record instanceof Garment) {
            return null;
        }

        return $record->cubic_dimensions ?? null;
    }

    public function getCubicVolume(): ?float
    {
        $dimensions = $this->getCubicDimensions();
        
        if (!$dimensions || !isset($dimensions['length']) || !isset($dimensions['width']) || !isset($dimensions['height'])) {
            return null;
        }

        $length = (float)($dimensions['length'] ?? 0);
        $width = (float)($dimensions['width'] ?? 0);
        $height = (float)($dimensions['height'] ?? 0);

        if ($length > 0 && $width > 0 && $height > 0) {
            return round($length * $width * $height, 2);
        }

        return null;
    }
}

