<?php

namespace App\Filament\Resources\IncomingShipmentResource\Pages;

use App\Filament\Resources\IncomingShipmentResource;
use App\Filament\Resources\IncomingShipmentResource\Widgets\CartonEntryWidget;
use App\Models\IncomingShipment;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateIncomingShipment extends CreateRecord
{
    protected static string $resource = IncomingShipmentResource::class;

    public array $syncedCartons = [];

    protected $listeners = [
        'update-shipment-name' => 'updateShipmentName',
        'sync-cartons-to-form' => 'syncCartonsToForm',
    ];

    public function updateShipmentName($name): void
    {
        $this->form->fill(['name' => $name]);
    }

    public function syncCartonsToForm($cartons): void
    {
        // Store cartons for use during creation
        $this->syncedCartons = $cartons;
        
        // Convert cartons from widget to items format
        $items = [];
        foreach ($cartons as $carton) {
            // Include carton if it has any meaningful data
            if (!empty($carton['product_name']) || !empty($carton['carton_number']) || !empty($carton['order_number']) || !empty($carton['quantity']) || !empty($carton['eid'])) {
                // Try to parse product name to extract style and color
                $productName = $carton['product_name'] ?? '';
                $style = '';
                $color = '';
                $packingWay = 'Hook';
                
                if (!empty($productName)) {
                    // Try to split by " - " to get style and color
                    $parts = explode(' - ', $productName);
                    if (count($parts) >= 2) {
                        $style = trim($parts[0]);
                        $color = trim($parts[1]);
                        // Check if last part is packing way
                        if (count($parts) >= 3) {
                            $lastPart = trim($parts[count($parts) - 1]);
                            if (stripos($lastPart, 'sleeve') !== false || stripos($lastPart, 'wrap') !== false) {
                                $packingWay = 'Sleeve Wrap';
                                $color = trim($parts[count($parts) - 2]);
                            }
                        }
                    } else {
                        $style = trim($productName);
                        $color = '';
                    }
                }
                
                $items[] = [
                    'carton_number' => $carton['carton_number'] ?? '',
                    'order_number' => $carton['order_number'] ?? '',
                    'style' => $style,
                    'color' => $color,
                    'eid' => $carton['eid'] ?? '',
                    'packing_way' => $packingWay,
                    'quantity' => !empty($carton['quantity']) ? (int)$carton['quantity'] : 0,
                ];
            }
        }
        
        // Update form with items - ensure it's properly set
        $formData = $this->form->getState();
        $formData['items'] = $items;
        $this->form->fill($formData);
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [
            CartonEntryWidget::class,
        ];
    }

    protected function getFormActions(): array
    {
        return []; // Hide default form actions - buttons are in the widget at bottom
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        
        // Generate shipment number if not set
        if (empty($data['name'])) {
            $data['name'] = $this->generateNextShipmentNumber();
        }
        
        // Ensure shipment_date is set (defaults to today if not provided)
        if (empty($data['shipment_date'])) {
            $data['shipment_date'] = now()->format('Y-m-d');
        }
        
        // Track when tracking number is first added
        if (!empty($data['tracking_number'])) {
            $data['tracking_added_at'] = now();
        }
        
        // Set status to 'shipped' when shipment is created
        // If tracking number is provided, set to 'shipped_track'
        if (empty($data['status'])) {
            if (!empty($data['tracking_number'])) {
                $data['status'] = 'shipped_track';
            } else {
                $data['status'] = 'shipped';
            }
        }
        
        // Use synced cartons if available, otherwise use items from form data
        // This ensures we have the latest carton data even if sync didn't happen via event
        if (!empty($this->syncedCartons)) {
            // Convert cartons to items format
            $items = [];
            foreach ($this->syncedCartons as $carton) {
                // Include carton if it has any meaningful data
                if (!empty($carton['product_name']) || !empty($carton['carton_number']) || !empty($carton['order_number']) || !empty($carton['quantity']) || !empty($carton['eid'])) {
                    // Try to parse product name to extract style and color
                    $productName = $carton['product_name'] ?? '';
                    $style = '';
                    $color = '';
                    $packingWay = 'Hook';
                    
                    if (!empty($productName)) {
                        // Try to split by " - " to get style and color
                        $parts = explode(' - ', $productName);
                        if (count($parts) >= 2) {
                            $style = trim($parts[0]);
                            $color = trim($parts[1]);
                            // Check if last part is packing way
                            if (count($parts) >= 3) {
                                $lastPart = trim($parts[count($parts) - 1]);
                                if (stripos($lastPart, 'sleeve') !== false || stripos($lastPart, 'wrap') !== false) {
                                    $packingWay = 'Sleeve Wrap';
                                    $color = trim($parts[count($parts) - 2]);
                                }
                            }
                        } else {
                            $style = trim($productName);
                            $color = '';
                        }
                    }
                    
                    $items[] = [
                        'carton_number' => $carton['carton_number'] ?? '',
                        'order_number' => $carton['order_number'] ?? '',
                        'style' => $style,
                        'color' => $color,
                        'eid' => $carton['eid'] ?? '',
                        'packing_way' => $packingWay,
                        'quantity' => !empty($carton['quantity']) ? (int)$carton['quantity'] : 0,
                    ];
                }
            }
            $data['items'] = $items;
        }
        
        // Ensure items is set and is an array
        if (!isset($data['items']) || !is_array($data['items'])) {
            $data['items'] = [];
        }
        
        // Clean up items array - remove any empty items and clean fields
        // Only include items where all required fields (except received_qty) are filled
        $cleanedItems = [];
        foreach ($data['items'] as $item) {
            $cartonNumber = trim($item['carton_number'] ?? '');
            $orderNumber = trim($item['order_number'] ?? '');
            $eid = trim($item['eid'] ?? '');
            $style = trim($item['style'] ?? '');
            $color = trim($item['color'] ?? '');
            $quantity = trim($item['quantity'] ?? '');
            
            // Only include items where all required fields are filled
            // received_qty is optional, so we don't check it
            if (!empty($cartonNumber) && !empty($orderNumber) && !empty($eid) && (!empty($style) || !empty($color)) && !empty($quantity)) {
                $cleanedItem = [];
                
                // Remove unwanted fields
                if (isset($item['_selected'])) {
                    unset($item['_selected']);
                }
                if (isset($item['product_id'])) {
                    unset($item['product_id']);
                }
                
                // Ensure all required fields are present
                $cleanedItem['carton_number'] = $cartonNumber;
                $cleanedItem['order_number'] = $orderNumber;
                $cleanedItem['style'] = $style;
                $cleanedItem['color'] = $color;
                $cleanedItem['eid'] = $eid;
                $cleanedItem['packing_way'] = $item['packing_way'] ?? 'Hook';
                $cleanedItem['quantity'] = !empty($quantity) ? (int)$quantity : 0;
                // Only include received_qty if the item was previously saved
                if (!empty($item['is_saved']) && $item['is_saved'] === true) {
                    $cleanedItem['received_qty'] = !empty($item['received_qty']) ? (int)$item['received_qty'] : 0;
                } else {
                    // New items don't have received_qty yet
                    $cleanedItem['received_qty'] = 0;
                }
                
                $cleanedItems[] = $cleanedItem;
            }
        }
        
        $data['items'] = $cleanedItems;
        
        return $data;
    }

    protected function generateNextShipmentNumber(): string
    {
        // Get the highest shipment number from existing shipments
        $lastShipment = \App\Models\IncomingShipment::where('name', 'like', 'SOCKSHIP%')
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
}
