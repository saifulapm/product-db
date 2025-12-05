<?php

namespace App\Filament\Resources\IncomingShipmentResource\Widgets;

use App\Models\IncomingShipment;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;
use Illuminate\Support\Str;

class CartonEntryWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.resources.incoming-shipment-resource.widgets.carton-entry-widget';

    protected int | string | array $columnSpan = 'full';

    public array $cartons = [];

    public string $shipmentNumber = '';

    public array $selectedRows = [];

    public bool $selectAll = false;

    public array $bulkUpdateData = [
        'carton_number' => '',
        'order_number' => '',
        'quantity' => '',
    ];

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selectedRows = array_keys($this->cartons);
        } else {
            $this->selectedRows = [];
        }
    }

    public function updatedSelectedRows(): void
    {
        $this->selectAll = count($this->selectedRows) === count($this->cartons) && count($this->cartons) > 0;
    }

    public function bulkUpdateSelected(): void
    {
        foreach ($this->selectedRows as $index) {
            if (isset($this->cartons[$index])) {
                if (!empty($this->bulkUpdateData['carton_number'])) {
                    $this->cartons[$index]['carton_number'] = $this->bulkUpdateData['carton_number'];
                }
                if (!empty($this->bulkUpdateData['order_number'])) {
                    $this->cartons[$index]['order_number'] = $this->bulkUpdateData['order_number'];
                }
                if (!empty($this->bulkUpdateData['quantity'])) {
                    $this->cartons[$index]['quantity'] = $this->bulkUpdateData['quantity'];
                }
            }
        }
        
        // Reset bulk update data
        $this->bulkUpdateData = [
            'carton_number' => '',
            'order_number' => '',
            'quantity' => '',
        ];
        
        $this->selectedRows = [];
        $this->selectAll = false;
    }

    public function mount(): void
    {
        // Generate shipment number if not set - start from SOCKSHIP001
        if (empty($this->shipmentNumber)) {
            $this->shipmentNumber = $this->generateNextShipmentNumber();
        }

        // Sync shipment number to parent form's name field
        $this->syncShipmentNumberToForm();

        // Initialize with one empty carton row
        if (empty($this->cartons)) {
            $this->cartons = [
                [
                    'carton_number' => '',
                    'order_number' => '',
                    'eid' => '',
                    'product_name' => '',
                    'quantity' => '',
                ]
            ];
        }
    }

    public function updatedShipmentNumber(): void
    {
        // Sync shipment number to parent form when it changes
        $this->syncShipmentNumberToForm();
    }

    public function updatedCartons(): void
    {
        // Sync cartons to parent form when they change
        $this->syncCartonsToForm();
    }

    protected function syncShipmentNumberToForm(): void
    {
        // Dispatch event to update parent form's name field
        $this->dispatch('update-shipment-name', name: $this->shipmentNumber);
    }

    public function syncCartonsToForm(): void
    {
        // Dispatch event to sync cartons to parent form's items field
        $this->dispatch('sync-cartons-to-form', cartons: $this->cartons);
    }

    protected function generateNextShipmentNumber(): string
    {
        // Get the highest shipment number from existing shipments
        $lastShipment = IncomingShipment::where('name', 'like', 'SOCKSHIP%')
            ->orderByRaw('CAST(SUBSTRING(name, 9) AS UNSIGNED) DESC')
            ->first();

        if ($lastShipment && preg_match('/SOCKSHIP(\d+)/', $lastShipment->name, $matches)) {
            $lastNumber = (int) $matches[1];
            $nextNumber = $lastNumber + 1;
        } else {
            // Start from 001 if no shipments exist
            $nextNumber = 1;
        }

        return 'SOCKSHIP' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    public function addCarton(): void
    {
        $this->cartons[] = [
            'carton_number' => '',
            'order_number' => '',
            'eid' => '',
            'product_name' => '',
            'quantity' => '',
        ];
    }

    public function removeCarton(int $index): void
    {
        unset($this->cartons[$index]);
        $this->cartons = array_values($this->cartons); // Re-index array
    }

    public function getCartonsProperty(): array
    {
        return $this->cartons;
    }

    public function getShipmentNumberProperty(): string
    {
        return $this->shipmentNumber;
    }
}

