<?php

namespace App\Filament\Resources\IncomingShipmentResource\Widgets;

use App\Filament\Resources\IncomingShipmentResource;
use App\Models\IncomingShipment;
use Filament\Widgets\Widget;
use Livewire\Attributes\Reactive;

class PackingListsTableWidget extends Widget
{
    protected static string $view = 'filament.resources.incoming-shipment-resource.widgets.packing-lists-table-widget';
    
    protected int | string | array $columnSpan = 'full';

    #[Reactive]
    public ?IncomingShipment $record = null;

    public function mount(): void
    {
        // Load record if not provided reactively
        if (!$this->record) {
            $recordId = request()->route('record');
            if ($recordId) {
                $this->record = IncomingShipment::find($recordId);
            }
        }
    }

    public function getViewData(): array
    {
        if (!$this->record) {
            return ['pickLists' => []];
        }

        // Refresh to get latest data
        $this->record->refresh();
        $pickLists = $this->record->pick_lists ?? [];
        if (!is_array($pickLists)) {
            $pickLists = [];
        }

        // Enrich pick lists with index and summary data
        $enrichedPickLists = [];
        foreach ($pickLists as $index => $pickList) {
            $items = $pickList['items'] ?? [];
            $itemCount = count($items);
            
            $totalNeeded = 0;
            foreach ($items as $item) {
                $totalNeeded += $item['quantity'] ?? $item['quantity_required'] ?? 0;
            }
            
            $pickedItems = $pickList['picked_items'] ?? [];
            $totalPicked = 0;
            foreach ($pickedItems as $picked) {
                $totalPicked += $picked['quantity_picked'] ?? 0;
            }
            
            $enrichedPickLists[] = [
                'index' => $index,
                'name' => $pickList['name'] ?? 'Pick List ' . ($index + 1),
                'filename' => $pickList['filename'] ?? 'Unknown',
                'uploaded_at' => $pickList['uploaded_at'] ?? '',
                'status' => $pickList['status'] ?? 'pending',
                'item_count' => $itemCount,
                'total_needed' => $totalNeeded,
                'total_picked' => $totalPicked,
                'remaining' => max(0, $totalNeeded - $totalPicked),
                'progress_percent' => $totalNeeded > 0 ? round(($totalPicked / $totalNeeded) * 100) : 0,
            ];
        }

        return [
            'pickLists' => $enrichedPickLists,
        ];
    }
}

