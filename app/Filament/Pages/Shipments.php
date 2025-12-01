<?php

namespace App\Filament\Pages;

use App\Models\Supply;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

class Shipments extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Shipments';

    protected static ?string $navigationGroup = 'Shipping';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.shipments';

    #[Url]
    public ?string $search = '';

    #[Url]
    public ?string $sortColumn = 'used_at';

    #[Url]
    public ?string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'sortColumn' => ['except' => 'used_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function mount(): void
    {
        $this->search = request()->query('search', '');
        $this->sortColumn = request()->query('sortColumn', 'used_at');
        $this->sortDirection = request()->query('sortDirection', 'desc');
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

    public function getShipmentsData(): array
    {
        $shipmentsData = [];

        // Get all supplies with shipment tracking
        $supplies = Supply::whereNotNull('shipment_tracking')
            ->get();

        foreach ($supplies as $supply) {
            $tracking = $supply->shipment_tracking ?? [];
            
            if (empty($tracking) || !is_array($tracking)) {
                continue;
            }

            foreach ($tracking as $shipment) {
                $shipmentsData[] = [
                    'order_number' => $shipment['order_number'] ?? '—',
                    'supply_name' => $supply->name,
                    'supply_type' => $supply->type,
                    'garment_quantity' => $shipment['quantity'] ?? 1,
                    'used_at' => $shipment['used_at'] ?? '',
                    'garment_id' => $shipment['garment_id'] ?? null,
                ];
            }
        }

        // Apply search filter
        if (!empty($this->search)) {
            $search = strtolower($this->search);
            $shipmentsData = array_filter($shipmentsData, function($item) use ($search) {
                return str_contains(strtolower($item['order_number']), $search) ||
                       str_contains(strtolower($item['supply_name']), $search);
            });
        }

        // Apply sorting
        usort($shipmentsData, function($a, $b) {
            $column = $this->sortColumn;
            $direction = $this->sortDirection === 'asc' ? 1 : -1;
            
            $valueA = $a[$column] ?? '';
            $valueB = $b[$column] ?? '';
            
            // Handle date sorting
            if ($column === 'used_at') {
                return strcmp($valueB, $valueA) * $direction;
            }
            
            // Handle numeric sorting for garment_quantity
            if ($column === 'garment_quantity') {
                return ($valueA <=> $valueB) * $direction;
            }
            
            // Handle string sorting
            return strcmp($valueA, $valueB) * $direction;
        });

        return array_values($shipmentsData);
    }
}

