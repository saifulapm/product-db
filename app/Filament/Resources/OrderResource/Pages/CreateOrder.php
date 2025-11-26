<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    public function mount(): void
    {
        parent::mount();
        
        // Pre-fill incoming_shipment_id if passed in URL
        $shipmentId = request()->query('incoming_shipment_id');
        if ($shipmentId) {
            $this->form->fill([
                'incoming_shipment_id' => $shipmentId,
            ]);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Create order items (allocations) when order is created
        $this->record->createOrderItems();
    }
}
