<?php

namespace App\Filament\Resources\GarmentResource\Widgets;

use App\Models\Garment;
use Filament\Widgets\Widget;
use Livewire\Attributes\Reactive;

class VariantsSummaryWidget extends Widget
{
    protected static string $view = 'filament.resources.garment-resource.widgets.variants-summary-widget';

    protected int | string | array $columnSpan = 'full';

    #[Reactive]
    public ?Garment $record = null;

    public array $variants = [];

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
        
        // Load existing variants if editing
        if ($record instanceof Garment && !empty($record->variants)) {
            $this->variants = $record->variants;
        } else {
            $this->variants = [];
        }
    }

    public function getVariantsSummary(): array
    {
        if (empty($this->variants)) {
            return [];
        }

        // Group by Variant name and sum inventory
        $summary = [];
        foreach ($this->variants as $variant) {
            $variantName = $variant['name'] ?? '';
            $inventory = (int)($variant['inventory'] ?? 0);
            
            if (!empty($variantName)) {
                if (!isset($summary[$variantName])) {
                    $summary[$variantName] = [
                        'variant_name' => $variantName,
                        'total_quantity' => 0,
                    ];
                }
                $summary[$variantName]['total_quantity'] += $inventory;
            }
        }

        return array_values($summary);
    }

    protected function getListeners(): array
    {
        return [
            'variants-updated' => 'handleVariantsUpdate',
        ];
    }

    public function handleVariantsUpdate($variants): void
    {
        $this->variants = $variants;
    }
}

