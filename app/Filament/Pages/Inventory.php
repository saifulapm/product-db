<?php

namespace App\Filament\Pages;

use App\Models\Garment;
use App\Models\Shelf;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Inventory extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    
    protected static ?string $navigationLabel = 'Inventory';
    
    protected static ?string $navigationGroup = 'Inventory';
    
    protected static ?int $navigationSort = 2;
    
    protected static string $view = 'filament.pages.inventory';
    
    public ?string $search = '';
    
    public ?string $sortColumn = 'location';
    
    public ?string $sortDirection = 'asc';
    
    public array $selectedRows = [];
    
    public bool $selectAll = false;
    
    protected $queryString = [
        'search' => ['except' => ''],
        'sortColumn' => ['except' => 'location'],
        'sortDirection' => ['except' => 'asc'],
    ];
    
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('inventory.view');
    }

    public function mount(): void
    {
        $this->search = request()->query('search', '');
        $this->sortColumn = request()->query('sortColumn', 'location');
        $this->sortDirection = request()->query('sortDirection', 'asc');
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $inventoryData = $this->getInventoryData();
            $this->selectedRows = array_column($inventoryData, 'key');
        } else {
            $this->selectedRows = [];
        }
    }

    public function updatedSelectedRows(): void
    {
        $inventoryData = $this->getInventoryData();
        $allKeys = array_column($inventoryData, 'key');
        $this->selectAll = count($this->selectedRows) === count($allKeys) && count($allKeys) > 0 && empty(array_diff($allKeys, $this->selectedRows));
    }

    public function sortBy(string $column): void
    {
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function getInventoryData(): array
    {
        $inventoryData = [];

        // Get all garments with variants
        $garments = Garment::whereNotNull('variants')
            ->where('variants', '!=', '[]')
            ->get();

        foreach ($garments as $garment) {
            $variants = $garment->variants ?? [];
            
            if (empty($variants) || !is_array($variants)) {
                continue;
            }

            foreach ($variants as $variant) {
                $shelfNumber = $variant['shelf_number'] ?? null;
                $quantity = (int)($variant['inventory'] ?? 0);
                
                // Skip if no shelf assigned or no quantity
                if (empty($shelfNumber) || $quantity <= 0) {
                    continue;
                }

                // Find the shelf to get location
                $shelf = Shelf::where('name', $shelfNumber)->first();
                
                // Create a unique key for each inventory item
                $uniqueKey = md5($shelfNumber . '|' . ($variant['sku'] ?? '') . '|' . ($variant['name'] ?? ''));
                
                $inventoryData[] = [
                    'key' => $uniqueKey,
                    'location' => $shelf ? ($shelf->location->name ?? 'Unknown') : 'Unknown',
                    'product_name' => $garment->name ?? '—',
                    'shelf_name' => $shelfNumber,
                    'variant_name' => $variant['name'] ?? '—',
                    'ethos_id' => $variant['sku'] ?? '—',
                    'quantity' => $quantity,
                ];
            }
        }

        // Apply search filter
        if (!empty($this->search)) {
            $search = strtolower($this->search);
            $inventoryData = array_filter($inventoryData, function($item) use ($search) {
                return str_contains(strtolower($item['shelf_name']), $search) ||
                       str_contains(strtolower($item['ethos_id']), $search);
            });
        }

        // Apply sorting
        usort($inventoryData, function($a, $b) {
            $column = $this->sortColumn;
            $direction = $this->sortDirection === 'asc' ? 1 : -1;
            
            $valueA = $a[$column] ?? '';
            $valueB = $b[$column] ?? '';
            
            // Handle numeric sorting for quantity
            if ($column === 'quantity') {
                return ($valueA <=> $valueB) * $direction;
            }
            
            // Handle string sorting
            return strcmp($valueA, $valueB) * $direction;
        });

        return array_values($inventoryData);
    }

    public function exportInventoryReport(): StreamedResponse
    {
        $inventoryData = $this->getInventoryData();
        
        if (empty($this->selectedRows)) {
            Notification::make()
                ->title('No Items Selected')
                ->body('Please select at least one inventory item to export.')
                ->warning()
                ->send();
            
            return response()->streamDownload(function () {
                echo '';
            }, 'empty.pdf');
        }

        // Get selected items based on unique keys
        $selectedItems = [];
        $selectedKeys = array_flip($this->selectedRows);
        foreach ($inventoryData as $item) {
            if (isset($selectedKeys[$item['key']])) {
                // Remove the 'key' from the item before adding to report
                unset($item['key']);
                $selectedItems[] = $item;
            }
        }

        if (empty($selectedItems)) {
            Notification::make()
                ->title('No Valid Items Selected')
                ->body('The selected items could not be found.')
                ->warning()
                ->send();
            
            return response()->streamDownload(function () {
                echo '';
            }, 'empty.pdf');
        }

        try {
            $pdf = Pdf::loadView('filament.pages.inventory-report', [
                'items' => $selectedItems,
                'generatedAt' => now()->format('Y-m-d H:i:s'),
                'totalItems' => count($selectedItems),
            ])
                ->setPaper('a4', 'portrait')
                ->setOption('isRemoteEnabled', true);

            $filename = 'inventory-report-' . date('Y-m-d-His') . '.pdf';

            Notification::make()
                ->title('Report Generated')
                ->body('Inventory report has been generated successfully.')
                ->success()
                ->send();

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $filename, ['Content-Type' => 'application/pdf']);

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Could not generate PDF: ' . $e->getMessage())
                ->danger()
                ->send();
            
            return response()->streamDownload(function () {
                echo '';
            }, 'error.pdf');
        }
    }
}

