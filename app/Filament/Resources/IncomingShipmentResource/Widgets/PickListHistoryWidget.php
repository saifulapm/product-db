<?php

namespace App\Filament\Resources\IncomingShipmentResource\Widgets;

use Filament\Widgets\Widget;
use App\Models\IncomingShipment;
use Filament\Notifications\Notification;
use App\Filament\Resources\IncomingShipmentResource\Pages\ViewIncomingShipment;

class PickListHistoryWidget extends Widget
{
    protected static string $view = 'filament.resources.incoming-shipment-resource.widgets.pick-list-history-widget';
    
    public ?IncomingShipment $shipment = null;
    
    public function mount(IncomingShipment $shipment): void
    {
        $this->shipment = $shipment;
    }
    
    public function getPickLists(): array
    {
        $shipment = $this->getShipment();
        if (!$shipment) {
            return [];
        }
        
        $shipment->refresh();
        $pickLists = $shipment->pick_lists ?? [];
        
        if (empty($pickLists) || !is_array($pickLists)) {
            return [];
        }
        
        return $pickLists;
    }
    
    public function deletePickList(int $pickListIndex): void
    {
        $shipment = $this->getShipment();
        if (!$shipment) {
            Notification::make()
                ->title('Shipment not found')
                ->danger()
                ->send();
            return;
        }
        
        $shipment->refresh();
        $pickLists = $shipment->pick_lists ?? [];
        
        if (empty($pickLists) || !is_array($pickLists) || !isset($pickLists[$pickListIndex])) {
            Notification::make()
                ->title('Pick list not found')
                ->danger()
                ->send();
            return;
        }
        
        // Remove the pick list
        unset($pickLists[$pickListIndex]);
        
        // Re-index array
        $pickLists = array_values($pickLists);
        
        // Save back to database
        $shipment->pick_lists = $pickLists;
        $shipment->save();
        
        Notification::make()
            ->title('Pick list deleted successfully')
            ->success()
            ->send();
        
        // Refresh the widget
        $this->dispatch('$refresh');
    }
    
    public function updatePickListName(int $pickListIndex, string $newName): void
    {
        $shipment = $this->getShipment();
        if (!$shipment) {
            Notification::make()
                ->title('Shipment not found')
                ->danger()
                ->send();
            return;
        }
        
        $shipment->refresh();
        $pickLists = $shipment->pick_lists ?? [];
        
        if (empty($pickLists) || !is_array($pickLists) || !isset($pickLists[$pickListIndex])) {
            Notification::make()
                ->title('Pick list not found')
                ->danger()
                ->send();
            return;
        }
        
        // Update the name
        $pickLists[$pickListIndex]['name'] = trim($newName);
        
        // Save back to database
        $shipment->pick_lists = $pickLists;
        $shipment->save();
        
        Notification::make()
            ->title('Pick list name updated')
            ->success()
            ->send();
        
        // Refresh the widget
        $this->dispatch('$refresh');
    }
    
    protected function getViewData(): array
    {
        return [
            'pickLists' => $this->getPickLists(),
        ];
    }
}

