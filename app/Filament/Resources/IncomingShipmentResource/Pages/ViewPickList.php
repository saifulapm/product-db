<?php

namespace App\Filament\Resources\IncomingShipmentResource\Pages;

use App\Filament\Resources\IncomingShipmentResource;
use Filament\Resources\Pages\Page;
use Filament\Actions;
use Filament\Notifications\Notification;

class ViewPickList extends Page
{
    protected static string $view = 'filament.resources.incoming-shipment-resource.pages.view-pick-list';
    
    protected static string $resource = IncomingShipmentResource::class;
    
    public $shipmentId;
    public $pickListIndex;
    public $pickList = [];
    public $shipment = null;
    public $selectedPickListItems = [];
    
    public function mount(int | string $shipmentId, int $pickListIndex)
    {
        $this->shipmentId = $shipmentId;
        $this->pickListIndex = $pickListIndex;
        
        $this->shipment = \App\Models\IncomingShipment::find($shipmentId);
        
        if (!$this->shipment) {
            Notification::make()
                ->title('Shipment not found')
                ->danger()
                ->send();
            
            return redirect(IncomingShipmentResource::getUrl('index'));
        }
        
        $pickLists = $this->shipment->pick_lists ?? [];
        
        if (!isset($pickLists[$pickListIndex])) {
            Notification::make()
                ->title('Pick list not found')
                ->danger()
                ->send();
            
            return redirect(IncomingShipmentResource::getUrl('view', ['record' => $shipmentId]));
        }
        
        $this->pickList = $pickLists[$pickListIndex];
    }
    
    public function getTitle(): string
    {
        return $this->pickList['name'] ?? 'Pick List';
    }
    
    public function getHeading(): string
    {
        return $this->pickList['name'] ?? 'Pick List';
    }
    
    public function getSubheading(): ?string
    {
        $filename = $this->pickList['filename'] ?? '';
        $uploadedAt = $this->pickList['uploaded_at'] ?? '';
        
        if ($uploadedAt) {
            try {
                $date = \Carbon\Carbon::parse($uploadedAt);
                $uploadedAt = $date->format('M d, Y g:i A');
            } catch (\Exception $e) {
                // Keep original format if parsing fails
            }
        }
        
        return $filename ? "File: {$filename}" . ($uploadedAt ? " • Uploaded: {$uploadedAt}" : '') : null;
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to Shipment')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => IncomingShipmentResource::getUrl('view', ['record' => $this->shipmentId])),
            Actions\Action::make('delete_pick_list')
                ->label('Delete Pick List')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Delete Pick List')
                ->modalDescription('Are you sure you want to delete this pick list? This action cannot be undone.')
                ->action(function () {
                    $pickLists = $this->shipment->pick_lists ?? [];
                    
                    if (isset($pickLists[$this->pickListIndex])) {
                        unset($pickLists[$this->pickListIndex]);
                        $pickLists = array_values($pickLists); // Re-index array
                        
                        $this->shipment->pick_lists = $pickLists;
                        $this->shipment->save();
                        
                        Notification::make()
                            ->title('Pick list deleted')
                            ->success()
                            ->send();
                        
                        return redirect(IncomingShipmentResource::getUrl('view', ['record' => $this->shipmentId]));
                    }
                }),
        ];
    }
    
    public function markItemAsPicked(int $itemIndex, int $shipmentItemIndex, int $quantityToPick): void
    {
        if (!$this->shipment) {
            return;
        }
        
        $pickLists = $this->shipment->pick_lists ?? [];
        
        if (!isset($pickLists[$this->pickListIndex])) {
            return;
        }
        
        $pickList = &$pickLists[$this->pickListIndex];
        if (!isset($pickList['picked_items'])) {
            $pickList['picked_items'] = [];
        }
        
        // Check if already picked
        $found = false;
        foreach ($pickList['picked_items'] as &$picked) {
            if (($picked['item_index'] ?? null) === $itemIndex && 
                ($picked['shipment_item_index'] ?? null) === $shipmentItemIndex) {
                $picked['quantity_picked'] = ($picked['quantity_picked'] ?? 0) + $quantityToPick;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $pickList['picked_items'][] = [
                'item_index' => $itemIndex,
                'shipment_item_index' => $shipmentItemIndex,
                'quantity_picked' => $quantityToPick,
                'picked_at' => now()->toIso8601String(),
            ];
        }
        
        $this->shipment->pick_lists = $pickLists;
        $this->shipment->save();
        
        // Reload pick list
        $this->pickList = $pickLists[$this->pickListIndex];
        
        Notification::make()
            ->title('Item marked as picked')
            ->success()
            ->send();
    }
    
    public function bulkMarkItemsAsPicked(array $itemData): void
    {
        if (!$this->shipment) {
            return;
        }
        
        $pickLists = $this->shipment->pick_lists ?? [];
        
        if (!isset($pickLists[$this->pickListIndex])) {
            return;
        }
        
        $pickList = &$pickLists[$this->pickListIndex];
        if (!isset($pickList['picked_items'])) {
            $pickList['picked_items'] = [];
        }
        
        $markedCount = 0;
        
        foreach ($itemData as $data) {
            $itemIndex = $data['itemIndex'] ?? null;
            $shipmentItemIndex = $data['shipmentItemIndex'] ?? null;
            $quantityToPick = $data['quantity'] ?? 0;
            
            if ($itemIndex === null || $shipmentItemIndex === null || $quantityToPick <= 0) {
                continue;
            }
            
            // Check if already picked
            $found = false;
            foreach ($pickList['picked_items'] as &$picked) {
                if (($picked['item_index'] ?? null) === $itemIndex && 
                    ($picked['shipment_item_index'] ?? null) === $shipmentItemIndex) {
                    $picked['quantity_picked'] = ($picked['quantity_picked'] ?? 0) + $quantityToPick;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $pickList['picked_items'][] = [
                    'item_index' => $itemIndex,
                    'shipment_item_index' => $shipmentItemIndex,
                    'quantity_picked' => $quantityToPick,
                    'picked_at' => now()->toIso8601String(),
                ];
            }
            
            $markedCount++;
        }
        
        if ($markedCount > 0) {
            $this->shipment->pick_lists = $pickLists;
            $this->shipment->save();
            
            // Reload pick list
            $this->pickList = $pickLists[$this->pickListIndex];
            
            Notification::make()
                ->title('Items marked as picked')
                ->body($markedCount . ' item(s) marked as picked.')
                ->success()
                ->send();
        }
    }
    
    public function bulkDeletePickListItems(array $itemIndices): void
    {
        if (!$this->shipment) {
            return;
        }
        
        $pickLists = $this->shipment->pick_lists ?? [];
        
        if (!isset($pickLists[$this->pickListIndex])) {
            return;
        }
        
        $pickList = &$pickLists[$this->pickListIndex];
        $pickListItems = $pickList['items'] ?? [];
        
        rsort($itemIndices);
        
        $deletedCount = 0;
        foreach ($itemIndices as $itemIndex) {
            if (isset($pickListItems[$itemIndex])) {
                unset($pickListItems[$itemIndex]);
                
                // Remove picked items
                if (isset($pickList['picked_items'])) {
                    $pickList['picked_items'] = array_values(array_filter($pickList['picked_items'], function ($picked) use ($itemIndex) {
                        return ($picked['item_index'] ?? null) !== $itemIndex;
                    }));
                }
                
                $deletedCount++;
            }
        }
        
        $pickList['items'] = array_values($pickListItems);
        $this->shipment->pick_lists = $pickLists;
        $this->shipment->save();
        
        // Reload pick list
        $this->pickList = $pickLists[$this->pickListIndex];
        
        Notification::make()
            ->title('Items deleted')
            ->body($deletedCount . ' item(s) deleted from pick list.')
            ->success()
            ->send();
    }
}

