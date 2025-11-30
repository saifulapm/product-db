<?php

namespace App\Filament\Resources\GarmentResource\Widgets;

use App\Models\Garment;
use Filament\Widgets\Widget;
use Livewire\Attributes\Reactive;

class GarmentMeasurementsViewWidget extends Widget
{
    protected static string $view = 'filament.resources.garment-resource.widgets.garment-measurements-view-widget';

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

    public array $expandedPanels = [];

    public function togglePanel(int $index): void
    {
        if (in_array($index, $this->expandedPanels)) {
            $this->expandedPanels = array_values(array_diff($this->expandedPanels, [$index]));
        } else {
            $this->expandedPanels[] = $index;
        }
    }

    public function isPanelExpanded(int $index): bool
    {
        return in_array($index, $this->expandedPanels);
    }

    public function getMeasurements(): array
    {
        $record = $this->getRecord();
        
        if (!$record instanceof Garment) {
            return [];
        }

        $measurements = $record->measurements ?? [];
        
        if (empty($measurements) || !is_array($measurements)) {
            return [];
        }

        return $measurements;
    }
}

