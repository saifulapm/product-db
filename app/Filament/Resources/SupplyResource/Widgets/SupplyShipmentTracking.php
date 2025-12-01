<?php

namespace App\Filament\Resources\SupplyResource\Widgets;

use App\Models\Supply;
use Filament\Widgets\Widget;
use Livewire\Attributes\Reactive;

class SupplyShipmentTracking extends Widget
{
    protected static string $view = 'filament.resources.supply-resource.widgets.supply-shipment-tracking';

    protected int | string | array $columnSpan = 'full';

    #[Reactive]
    public ?Supply $record = null;

    protected function getRecord(): ?Supply
    {
        if ($this->record instanceof Supply) {
            return $this->record;
        }

        $recordId = request()->route('record');
        
        if ($recordId) {
            return Supply::find($recordId);
        }
        
        return null;
    }

    public function getShipmentTracking(): array
    {
        $record = $this->getRecord();
        
        if (!$record instanceof Supply) {
            return [];
        }

        $tracking = $record->shipment_tracking ?? [];
        
        if (empty($tracking) || !is_array($tracking)) {
            return [];
        }

        // Sort by most recent first
        usort($tracking, function($a, $b) {
            $dateA = $a['used_at'] ?? '';
            $dateB = $b['used_at'] ?? '';
            return strcmp($dateB, $dateA);
        });

        return $tracking;
    }
}

