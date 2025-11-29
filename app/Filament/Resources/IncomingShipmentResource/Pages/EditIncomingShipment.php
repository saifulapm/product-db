<?php

namespace App\Filament\Resources\IncomingShipmentResource\Pages;

use App\Filament\Resources\IncomingShipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EditIncomingShipment extends EditRecord
{
    protected static string $resource = IncomingShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return []; // Hide default form actions - buttons are in the widget at bottom
    }
    
    public function bulkDeleteItems(): void
    {
        $formData = $this->form->getState();
        $items = $formData['items'] ?? [];
        $selectedIndices = $this->mountedRepeaterBulkActionData ?? [];
        
        if (empty($selectedIndices) || empty($items)) {
            Notification::make()
                ->title('No items selected')
                ->body('Please select items to delete.')
                ->warning()
                ->send();
            return;
        }
        
        // Remove selected items by index
        $remainingItems = [];
        foreach ($items as $index => $item) {
            if (!in_array($index, $selectedIndices)) {
                $remainingItems[] = $item;
            }
        }
        
        // Update the form state
        $formData['items'] = $remainingItems;
        $this->form->fill($formData);
        
        Notification::make()
            ->title('Items deleted')
            ->body(count($selectedIndices) . ' item(s) deleted successfully.')
            ->success()
            ->send();
    }
    
    
    protected function parseCsvFile(string $filePath): array
    {
        $items = [];
        $handle = fopen($filePath, 'r');
        
        if ($handle === false) {
            return $items;
        }
        
        // Read header row
        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return $items;
        }
        
        // Normalize header and map columns - exact mapping for: Carton, Style, Color, Packaging Style, Quantity
        $headerMap = [];
        foreach ($header as $index => $col) {
            $colTrimmed = trim($col);
            $colLower = strtolower($colTrimmed);
            
            // Map exact headers: Carton, Style, Color, Packaging Style, Quantity
            // Use case-insensitive matching and handle variations
            if (preg_match('/^carton$/i', $colTrimmed)) {
                $headerMap['carton'] = $index;
            } elseif (preg_match('/^style$/i', $colTrimmed)) {
                $headerMap['style'] = $index;
            } elseif (preg_match('/^color$/i', $colTrimmed)) {
                $headerMap['color'] = $index;
            } elseif (preg_match('/^packaging\s+style$/i', $colTrimmed)) {
                $headerMap['packing_way'] = $index;
            } elseif (preg_match('/^quantity$/i', $colTrimmed) || preg_match('/^qty$/i', $colTrimmed) || preg_match('/quantity/i', $colLower)) {
                $headerMap['quantity'] = $index;
            }
        }
        
        // Fallback: positional mapping if header mapping failed (Carton, Style, Color, Packaging Style, Quantity)
        if (empty($headerMap) && count($header) >= 5) {
            $headerMap = [
                'carton' => 0,
                'style' => 1,
                'color' => 2,
                'packing_way' => 3,
                'quantity' => 4,
            ];
        }
        
        // Read data rows
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) {
                continue; // Skip empty or invalid rows
            }
            
            $item = [
                'carton_number' => isset($headerMap['carton']) && isset($row[$headerMap['carton']]) 
                    ? trim($row[$headerMap['carton']]) 
                    : '',
                'style' => isset($headerMap['style']) && isset($row[$headerMap['style']]) 
                    ? trim($row[$headerMap['style']]) 
                    : '',
                'color' => isset($headerMap['color']) && isset($row[$headerMap['color']]) 
                    ? trim($row[$headerMap['color']]) 
                    : '',
                'packing_way' => isset($headerMap['packing_way']) && isset($row[$headerMap['packing_way']]) 
                    ? trim($row[$headerMap['packing_way']]) 
                    : 'Hook',
                'quantity' => isset($headerMap['quantity']) && isset($row[$headerMap['quantity']]) 
                    ? $this->extractQuantity($row[$headerMap['quantity']]) 
                    : 1,
            ];
            
            // Normalize packing_way from "Packaging Style" column
            // Map to either 'Hook' or 'Sleeve Wrap'
            $packingStyle = strtolower(trim($item['packing_way']));
            if (stripos($packingStyle, 'sleeve') !== false || stripos($packingStyle, 'wrap') !== false) {
                $item['packing_way'] = 'Sleeve Wrap';
            } else {
                $item['packing_way'] = 'Hook';
            }
            
            // Only add item if it has required fields
            if (!empty($item['style']) && !empty($item['color'])) {
                $items[] = $item;
            }
        }
        
        fclose($handle);
        return $items;
    }
    
    /**
     * Extract numeric quantity from a value, handling various formats
     */
    protected function extractQuantity($value): int
    {
        if (empty($value)) {
            return 1;
        }
        
        // Trim whitespace
        $value = trim($value);
        
        // If it's already a number, cast it
        if (is_numeric($value)) {
            return (int)$value;
        }
        
        // Extract first number from the string (handles cases like "12 pcs", "12", etc.)
        if (preg_match('/\d+/', $value, $matches)) {
            return (int)$matches[0];
        }
        
        // Default to 1 if no number found
        return 1;
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Update status to 'shipped_track' if tracking number is added and status is 'shipped'
        if (!empty($data['tracking_number']) && $this->record->status === 'shipped') {
            $data['status'] = 'shipped_track';
        }
        
        // Remove the _selected checkbox field, product_id, and is_saved flag from items before saving
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as &$item) {
                if (isset($item['_selected'])) {
                    unset($item['_selected']);
                }
                if (isset($item['product_id'])) {
                    unset($item['product_id']);
                }
                if (isset($item['is_saved'])) {
                    unset($item['is_saved']); // Don't save UI flag to database
                }
            }
        }
        
        return $data;
    }

    protected function getFooterWidgets(): array
    {
        return [
            IncomingShipmentResource\Widgets\ShipmentContentsWidget::class,
        ];
    }

    protected function getListeners(): array
    {
        return [
            'refresh' => '$refresh',
            'update-shipment-items' => 'updateShipmentItems',
            'save-shipment-form' => 'handleSave',
        ];
    }

    public function updateShipmentItems($items): void
    {
        $formData = $this->form->getState();
        $formData['items'] = $items;
        $this->form->fill($formData);
    }

    public function handleSave(): void
    {
        // Ensure form is filled with latest data from the sync
        $this->form->fill($this->form->getState());
        
        // Call the parent save method which handles validation and persistence
        try {
            $this->save();
        } catch (\Exception $e) {
            // If save fails, dispatch an error notification
            \Filament\Notifications\Notification::make()
                ->title('Error saving shipment')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
