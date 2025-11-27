<?php

namespace App\Filament\Resources\IncomingShipmentResource\Widgets;

use Filament\Widgets\Widget;
use App\Models\IncomingShipment;
use Filament\Notifications\Notification;

class PickListHistoryWidget extends Widget
{
    protected static string $view = 'filament.resources.incoming-shipment-resource.widgets.pick-list-history-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    public ?IncomingShipment $shipment = null;
    
    public static function canView(): bool
    {
        $route = request()->route();
        if (!$route) {
            return false;
        }
        
        $routeName = $route->getName();
        return str_contains($routeName, 'incoming-shipments.view');
    }
    
    public function mount(): void
    {
        $this->loadShipment();
    }
    
    protected function loadShipment(): void
    {
        $recordId = request()->route('record');
        if ($recordId) {
            // Always reload from database to get latest pick lists
            $this->shipment = IncomingShipment::find($recordId);
            if ($this->shipment) {
                // Refresh the model to ensure we have latest data
                $this->shipment->refresh();
            }
        }
    }
    
    public function getPickLists(): array
    {
        if (!$this->shipment) {
            $this->loadShipment();
        }
        
        if (!$this->shipment) {
            return [];
        }
        
        $this->shipment->refresh();
        $pickLists = $this->shipment->pick_lists ?? [];
        
        if (empty($pickLists) || !is_array($pickLists)) {
            return [];
        }
        
        return $pickLists;
    }
    
    public function deletePickList(int $pickListIndex): void
    {
        if (!$this->shipment) {
            $this->loadShipment();
        }
        
        if (!$this->shipment) {
            Notification::make()
                ->title('Shipment not found')
                ->danger()
                ->send();
            return;
        }
        
        $this->shipment->refresh();
        $pickLists = $this->shipment->pick_lists ?? [];
        
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
        $this->shipment->pick_lists = $pickLists;
        $this->shipment->save();
        
        Notification::make()
            ->title('Pick list deleted successfully')
            ->success()
            ->send();
        
        // Refresh the widget
        $this->loadShipment();
        $this->dispatch('$refresh');
    }
    
    public function updatePickListName(int $pickListIndex, string $newName): void
    {
        if (!$this->shipment) {
            $this->loadShipment();
        }
        
        if (!$this->shipment) {
            Notification::make()
                ->title('Shipment not found')
                ->danger()
                ->send();
            return;
        }
        
        $this->shipment->refresh();
        $pickLists = $this->shipment->pick_lists ?? [];
        
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
        $this->shipment->pick_lists = $pickLists;
        $this->shipment->save();
        
        Notification::make()
            ->title('Pick list name updated')
            ->success()
            ->send();
        
        // Refresh the widget
        $this->loadShipment();
        $this->dispatch('$refresh');
    }
    
    protected function getViewData(): array
    {
        return [
            'pickLists' => $this->getPickLists(),
            'shipment' => $this->shipment,
        ];
    }
}
