<?php

namespace App\Filament\Resources\GarmentResource\Widgets;

use App\Models\Garment;
use Filament\Widgets\Widget;
use Livewire\Attributes\Reactive;

class GarmentVariantsViewWidget extends Widget
{
    protected static string $view = 'filament.resources.garment-resource.widgets.garment-variants-view-widget';

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

    public function getVariants(): array
    {
        $record = $this->getRecord();
        
        if (!$record instanceof Garment) {
            return [];
        }

        $variants = $record->variants ?? [];
        
        if (empty($variants) || !is_array($variants)) {
            return [];
        }

        return $variants;
    }

    public function getVariantsSummary(): array
    {
        $variants = $this->getVariants();
        
        if (empty($variants)) {
            return [];
        }

        // Group by Variant name and sum inventory
        $summary = [];
        foreach ($variants as $variant) {
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
}

