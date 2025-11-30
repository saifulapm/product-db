<?php

namespace App\Filament\Resources\GarmentResource\Widgets;

use App\Models\Garment;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;
use Livewire\Attributes\Reactive;

class CubicDimensionsWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.resources.garment-resource.widgets.cubic-dimensions-widget';

    protected int | string | array $columnSpan = 'full';

    #[Reactive]
    public ?Garment $record = null;

    public ?float $length = null;
    public ?float $width = null;
    public ?float $height = null;

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
        
        // Load existing cubic dimensions if editing
        if ($record instanceof Garment && !empty($record->cubic_dimensions)) {
            $dimensions = $record->cubic_dimensions;
            $this->length = $dimensions['length'] ?? null;
            $this->width = $dimensions['width'] ?? null;
            $this->height = $dimensions['height'] ?? null;
        } else {
            // Initialize with empty values
            $this->length = null;
            $this->width = null;
            $this->height = null;
        }
    }

    public function updatedLength(): void
    {
        $this->dispatchCubicDimensionsToParent();
    }

    public function updatedWidth(): void
    {
        $this->dispatchCubicDimensionsToParent();
    }

    public function updatedHeight(): void
    {
        $this->dispatchCubicDimensionsToParent();
    }

    public function dispatchCubicDimensionsToParent(): void
    {
        // Dispatch event to sync cubic dimensions to parent form's cubic_dimensions field
        $this->dispatch('sync-cubic-dimensions-to-form', cubicDimensions: [
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
        ]);
    }

    public function getCubicVolume(): ?float
    {
        if ($this->length && $this->width && $this->height) {
            return round((float)$this->length * (float)$this->width * (float)$this->height, 2);
        }
        return null;
    }
}

