<?php

namespace App\Filament\Resources\GarmentResource\Widgets;

use App\Models\Garment;
use App\Models\Shelf;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;
use Illuminate\Support\Str;
use Livewire\Attributes\Reactive;

class VariantEntryWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.resources.garment-resource.widgets.variant-entry-widget';

    protected int | string | array $columnSpan = 'full';

    #[Reactive]
    public ?Garment $record = null;

    public array $variants = [];

    public array $selectedRows = [];

    public bool $selectAll = false;

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selectedRows = array_keys($this->variants);
        } else {
            $this->selectedRows = [];
        }
    }

    public function updatedSelectedRows(): void
    {
        $this->selectAll = count($this->selectedRows) === count($this->variants) && count($this->variants) > 0;
    }

    protected function getRecord(): ?Garment
    {
        // Try to get record from reactive property first
        if ($this->record instanceof Garment) {
            return $this->record;
        }

        // Fallback: get record from route parameter
        $recordId = request()->route('record');
        
        if ($recordId) {
            return Garment::find($recordId);
        }
        
        return null;
    }

    public function mount(): void
    {
        $record = $this->getRecord();
        
        // Load existing variants if editing
        if ($record instanceof Garment && !empty($record->variants)) {
            $this->variants = $record->variants;
            // Ensure total_inventory is calculated for each variant
            foreach ($this->variants as $index => $variant) {
                if (!isset($this->variants[$index]['total_inventory'])) {
                    $this->variants[$index]['total_inventory'] = (int)($variant['inventory'] ?? 0);
                }
            }
        } else {
            // Initialize with empty variants for new records
            $this->variants = [];
        }
    }

    public function updatedVariants($value): void
    {
        // Recalculate total inventory for all variants whenever variants change
        foreach ($this->variants as $index => $variant) {
            $this->variants[$index]['total_inventory'] = (int)($variant['inventory'] ?? 0);
        }
        
        // Update variants summary widget (but don't save)
        $this->dispatch('variants-updated', variants: $this->variants);
    }
    
    protected function recalculateTotals(): void
    {
        // Recalculate total inventory for all variants
        foreach ($this->variants as $index => $variant) {
            $this->variants[$index]['total_inventory'] = (int)($variant['inventory'] ?? 0);
        }
        // Update variants summary widget (but don't save)
        $this->dispatch('variants-updated', variants: $this->variants);
    }

    public function dispatchVariantsToParent(): void
    {
        // Recalculate totals before syncing
        $this->recalculateTotals();
        
        // Dispatch event to sync variants to parent form's variants field
        $this->dispatch('sync-variants-to-form', variants: $this->variants);
        
        // Dispatch event to trigger save
        $this->dispatch('save-garment-form');
    }

    public function addVariant(): void
    {
        $this->variants[] = [
            'name' => '',
            'sku' => '',
            'inventory' => 0,
            'shelf_number' => '',
            'total_inventory' => 0,
        ];
    }

    public function removeVariant(int $index): void
    {
        unset($this->variants[$index]);
        $this->variants = array_values($this->variants); // Re-index array
        $this->recalculateTotals();
    }

    public function bulkDeleteSelected(): void
    {
        if (empty($this->selectedRows)) {
            return;
        }

        // Remove selected variants
        $remainingVariants = [];
        foreach ($this->variants as $index => $variant) {
            if (!in_array($index, $this->selectedRows)) {
                $remainingVariants[] = $variant;
            }
        }

        $this->variants = $remainingVariants;
        $this->selectedRows = [];
        $this->selectAll = false;
        $this->recalculateTotals();
    }

    public function getVariantsProperty(): array
    {
        return $this->variants;
    }

    public function getShelvesOptions(): array
    {
        return Shelf::where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'name')
            ->toArray();
    }
}

