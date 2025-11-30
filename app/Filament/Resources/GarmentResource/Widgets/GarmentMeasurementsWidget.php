<?php

namespace App\Filament\Resources\GarmentResource\Widgets;

use App\Models\Garment;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;
use Livewire\Attributes\Reactive;

class GarmentMeasurementsWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.resources.garment-resource.widgets.garment-measurements-widget';

    protected int | string | array $columnSpan = 'full';

    #[Reactive]
    public ?Garment $record = null;

    public array $measurements = [];
    
    public array $expandedPanels = [];

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

    public function mount(): void
    {
        $record = $this->getRecord();
        
        // Load existing measurements if editing
        if ($record instanceof Garment && !empty($record->measurements)) {
            $this->measurements = $record->measurements;
        } else {
            // Initialize with empty measurements
            $this->measurements = [];
        }
    }

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

    public function addFabricPanel(): void
    {
        $this->measurements[] = [
            'fabric_panel_name' => '',
            'image_url' => '',
            'length_label' => '',
            'length_value' => '',
            'width_label' => '',
            'width_value' => '',
        ];
    }

    public function removeFabricPanel(int $index): void
    {
        unset($this->measurements[$index]);
        $this->measurements = array_values($this->measurements); // Re-index array
        // Remove from expanded panels if it was expanded
        $this->expandedPanels = array_values(array_diff($this->expandedPanels, [$index]));
        // Re-index expanded panels
        $this->expandedPanels = array_map(function($panelIndex) use ($index) {
            return $panelIndex > $index ? $panelIndex - 1 : $panelIndex;
        }, $this->expandedPanels);
        $this->dispatchMeasurementsToParent();
    }

    public function updatedMeasurements(): void
    {
        // Auto-sync measurements to parent form when changed
        $this->dispatchMeasurementsToParent();
    }

    public function dispatchMeasurementsToParent(): void
    {
        // Dispatch event to sync measurements to parent form's measurements field
        $this->dispatch('sync-measurements-to-form', measurements: $this->measurements);
    }

    public function getMeasurementsProperty(): array
    {
        return $this->measurements;
    }

    public function convertInchesToCm($inches): ?float
    {
        if (empty($inches) || !is_numeric($inches)) {
            return null;
        }
        return round((float)$inches * 2.54, 2);
    }
}

