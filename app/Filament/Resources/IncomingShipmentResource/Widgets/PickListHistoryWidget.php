<?php

namespace App\Filament\Resources\IncomingShipmentResource\Widgets;

use Filament\Widgets\Widget;
use App\Models\IncomingShipment;
use Filament\Notifications\Notification;

class PickListHistoryWidget extends Widget
{
    protected static string $view = 'filament.resources.incoming-shipment-resource.widgets.pick-list-history-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    // Poll the widget every 5 seconds to check for updates
    protected static ?string $pollingInterval = '5s';
    
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
        // Always reload shipment to get latest data
        $this->loadShipment();
        
        if (!$this->shipment) {
            return [];
        }
        
        // Refresh to get latest pick_lists from database
        $this->shipment->refresh();
        $pickLists = $this->shipment->pick_lists ?? [];
        
        // Handle JSON string if it's not already decoded (shouldn't happen with array cast, but just in case)
        if (is_string($pickLists)) {
            $decoded = json_decode($pickLists, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $pickLists = $decoded;
            } else {
                $pickLists = [];
            }
        }
        
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
        $pickLists = $this->getPickLists();
        
        return [
            'pickLists' => $pickLists,
            'shipment' => $this->shipment,
        ];
    }
    
    protected function getListeners(): array
    {
        return [
            'pick-list-updated' => 'refreshWidget',
            '$refresh' => 'refreshWidget',
            'refresh-widgets' => 'refreshWidget',
        ];
    }
    
    public function refreshWidget(): void
    {
        // Clear shipment to force reload
        $this->shipment = null;
        $this->loadShipment();
        $this->dispatch('$refresh');
    }
}
