<?php

namespace App\Filament\Widgets;

use App\Models\PoSubmission;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class FabricUsageCalculatorWidget extends Widget
{
    protected static string $view = 'filament.widgets.fabric-usage-calculator-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 1;
    
    public static function canView(): bool
    {
        // Only show on PO Submission page, not on dashboard
        $route = request()->route();
        if (!$route) {
            return false;
        }
        
        $routeName = $route->getName();
        return str_contains($routeName, 'po-submission');
    }
    
    public array $products = [];
    public array $suedeProducts = [];
    public float $fabricRollMinimum = 411.45;
    public int $suedeMinimumOrderQty = 100;
    public ?int $currentPoId = null;
    public string $poName = '';
    public bool $showSaveModal = false;
    public bool $showLoadModal = false;
    
    public function mount(): void
    {
        // Initialize with default products if starting fresh
        if (empty($this->products)) {
            $this->products = [
            // Best Sellers
            [
                'category' => 'Best Sellers',
                'product_name' => 'The Basic Legging',
                'sample_code' => 'W2240',
                'fabric_per_piece' => 0.95,
                'cost_price' => 0,
                'xs' => 0,
                's' => 0,
                'm' => 0,
                'l' => 0,
                'xl' => 0,
            ],
            [
                'category' => 'Best Sellers',
                'product_name' => 'The Basic Short',
                'sample_code' => 'W2244',
                'fabric_per_piece' => 0.45,
                'cost_price' => 0,
                'xs' => 0,
                's' => 0,
                'm' => 0,
                'l' => 0,
                'xl' => 0,
            ],
            [
                'category' => 'Best Sellers',
                'product_name' => 'The Box Cut Bra',
                'sample_code' => 'W2255',
                'fabric_per_piece' => 0.26,
                'cost_price' => 0,
                'xs' => 0,
                's' => 0,
                'm' => 0,
                'l' => 0,
                'xl' => 0,
            ],
            [
                'category' => 'Best Sellers',
                'product_name' => 'The Racer Tank',
                'sample_code' => 'W2242',
                'fabric_per_piece' => 0.3,
                'cost_price' => 0,
                'xs' => 0,
                's' => 0,
                'm' => 0,
                'l' => 0,
                'xl' => 0,
            ],
            // Tops
            [
                'category' => 'Tops',
                'product_name' => 'The Racer Bra',
                'sample_code' => 'W2234',
                'fabric_per_piece' => 0.27,
                'cost_price' => 0,
                'xs' => 0,
                's' => 0,
                'm' => 0,
                'l' => 0,
                'xl' => 0,
            ],
            [
                'category' => 'Tops',
                'product_name' => 'The Criss Cross Bra',
                'sample_code' => 'W2231',
                'fabric_per_piece' => 0.29,
                'cost_price' => 0,
                'xs' => 0,
                's' => 0,
                'm' => 0,
                'l' => 0,
                'xl' => 0,
            ],
            [
                'category' => 'Tops',
                'product_name' => 'The Halter Bra',
                'sample_code' => 'W2236',
                'fabric_per_piece' => 0.27,
                'cost_price' => 0,
                'xs' => 0,
                's' => 0,
                'm' => 0,
                'l' => 0,
                'xl' => 0,
            ],
            [
                'category' => 'Tops',
                'product_name' => 'The Zip Jacket',
                'sample_code' => 'W2247',
                'fabric_per_piece' => 1.15,
                'cost_price' => 0,
                'xs' => 0,
                's' => 0,
                'm' => 0,
                'l' => 0,
                'xl' => 0,
            ],
            // Bottoms
            [
                'category' => 'Bottoms',
                'product_name' => 'The Pocket Jogger',
                'sample_code' => 'W2229',
                'fabric_per_piece' => 1.05,
                'cost_price' => 0,
                'xs' => 0,
                's' => 0,
                'm' => 0,
                'l' => 0,
                'xl' => 0,
            ],
            // Accessories
            [
                'category' => 'Accessories',
                'product_name' => 'The Scrunchie',
                'sample_code' => 'W2233',
                'fabric_per_piece' => 0.001,
                'cost_price' => 0,
                'quantity' => 0,
            ],
        ];
        
        // Initialize Suede 1/2 Zip product
        $this->suedeProducts = [
            [
                'category' => '3rd Pieces',
                'product_name' => 'The Suede 1/2 Zip',
                'sample_code' => 'W23008-1',
                'fabric_per_piece' => 1.64,
                'cost_price' => 0,
                'sm' => 0,
                'xl' => 0,
            ],
        ];
        }
    }
    
    public function openSaveModal(): void
    {
        if ($this->currentPoId) {
            $po = PoSubmission::find($this->currentPoId);
            if ($po && $po->user_id === Auth::id()) {
                $this->poName = $po->name;
            } else {
                $this->poName = '';
            }
        } else {
            $this->poName = '';
        }
        $this->showSaveModal = true;
    }
    
    public function closeSaveModal(): void
    {
        $this->showSaveModal = false;
        $this->poName = '';
    }
    
    public function savePo(): void
    {
        if (empty(trim($this->poName))) {
            Notification::make()
                ->title('PO Name Required')
                ->body('Please enter a name for this PO submission.')
                ->warning()
                ->send();
            return;
        }
        
        $data = [
            'name' => trim($this->poName),
            'user_id' => Auth::id(),
            'fabric_roll_minimum' => $this->fabricRollMinimum,
            'products' => $this->products,
        ];
        
        if ($this->currentPoId) {
            // Update existing PO
            $po = PoSubmission::find($this->currentPoId);
            if ($po && $po->user_id === Auth::id()) {
                $po->update($data);
                Notification::make()
                    ->title('PO Updated')
                    ->body('PO submission has been updated successfully.')
                    ->success()
                    ->send();
            }
        } else {
            // Create new PO
            $po = PoSubmission::create($data);
            $this->currentPoId = $po->id;
            Notification::make()
                ->title('PO Saved')
                ->body('PO submission has been saved successfully.')
                ->success()
                ->send();
        }
        
        $this->closeSaveModal();
    }
    
    public function openLoadModal(): void
    {
        $this->showLoadModal = true;
    }
    
    public function closeLoadModal(): void
    {
        $this->showLoadModal = false;
    }
    
    public function loadPo(int $poId): void
    {
        $po = PoSubmission::find($poId);
        
        if (!$po) {
            Notification::make()
                ->title('PO Not Found')
                ->body('The selected PO submission could not be found.')
                ->danger()
                ->send();
            return;
        }
        
        if ($po->user_id !== Auth::id()) {
            Notification::make()
                ->title('Access Denied')
                ->body('You can only load your own PO submissions.')
                ->danger()
                ->send();
            return;
        }
        
        $this->currentPoId = $po->id;
        $this->poName = $po->name;
        $this->fabricRollMinimum = (float) $po->fabric_roll_minimum;
        $this->products = $po->products ?? [];
        
        $this->closeLoadModal();
        
        Notification::make()
            ->title('PO Loaded')
            ->body('PO submission has been loaded successfully.')
            ->success()
            ->send();
    }
    
    public function deletePo(int $poId): void
    {
        $po = PoSubmission::find($poId);
        
        if (!$po || $po->user_id !== Auth::id()) {
            Notification::make()
                ->title('Access Denied')
                ->body('You can only delete your own PO submissions.')
                ->danger()
                ->send();
            return;
        }
        
        $po->delete();
        
        if ($this->currentPoId === $poId) {
            $this->currentPoId = null;
            $this->poName = '';
            $this->products = [];
            $this->fabricRollMinimum = 411.45;
        }
        
        $this->closeLoadModal();
        
        Notification::make()
            ->title('PO Deleted')
            ->body('PO submission has been deleted successfully.')
            ->success()
            ->send();
    }
    
    public function getSavedPos()
    {
        return PoSubmission::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
    }
    
    public function newPo(): void
    {
        $this->currentPoId = null;
        $this->poName = '';
        $this->products = [];
        $this->fabricRollMinimum = 411.45;
        
        Notification::make()
            ->title('New PO Started')
            ->body('Started a new PO submission.')
            ->success()
            ->send();
    }
    
    public function addProduct(): void
    {
        $this->products[] = [
            'category' => '',
            'product_name' => '',
            'sample_code' => '',
            'fabric_per_piece' => 0,
            'cost_price' => 0,
            'xs' => 0,
            's' => 0,
            'm' => 0,
            'l' => 0,
            'xl' => 0,
        ];
    }
    
    public function isScrunchie(int $index): bool
    {
        if (!isset($this->products[$index])) {
            return false;
        }
        
        $product = $this->products[$index];
        return isset($product['product_name']) && 
               stripos($product['product_name'], 'scrunchie') !== false;
    }
    
    public function getProductTotalPcs(int $index): int
    {
        if (!isset($this->products[$index])) {
            return 0;
        }
        
        $product = $this->products[$index];
        
        // If it's a scrunchie, use the 'quantity' field
        if ($this->isScrunchie($index)) {
            return (int) ($product['quantity'] ?? 0);
        }
        
        return (int) ($product['xs'] ?? 0) + 
               (int) ($product['s'] ?? 0) + 
               (int) ($product['m'] ?? 0) + 
               (int) ($product['l'] ?? 0) + 
               (int) ($product['xl'] ?? 0);
    }
    
    public function removeProduct(int $index): void
    {
        unset($this->products[$index]);
        $this->products = array_values($this->products);
    }
    
    public function getTotalFabricUsed(): float
    {
        $total = 0;
        foreach ($this->products as $index => $product) {
            $fabricPerPiece = (float) ($product['fabric_per_piece'] ?? 0);
            $totalPcs = $this->getProductTotalPcs($index);
            $total += $fabricPerPiece * $totalPcs;
        }
        return round($total, 2);
    }
    
    public function getRemainingFabric(): float
    {
        return round($this->fabricRollMinimum - $this->getTotalFabricUsed(), 2);
    }
    
    public function getProductFabricUsed(int $index): float
    {
        if (!isset($this->products[$index])) {
            return 0;
        }
        
        $product = $this->products[$index];
        $fabricPerPiece = (float) ($product['fabric_per_piece'] ?? 0);
        $totalPcs = $this->getProductTotalPcs($index);
        
        return round($fabricPerPiece * $totalPcs, 2);
    }
    
    public function getSizes(): array
    {
        return ['xs' => 'XS', 's' => 'S', 'm' => 'M', 'l' => 'L', 'xl' => 'XL'];
    }
    
    public function getSuedeSizes(): array
    {
        return [
            'sm' => 'Small/Medium',
            'xl' => 'Large/XLarge',
        ];
    }
    
    public function getCategories(): array
    {
        return [
            'Best Sellers',
            'Tops',
            'Bottoms',
            'Accessories',
            '3rd Pieces',
        ];
    }
    
    public function getSuedeProductTotalPcs(int $index): int
    {
        if (!isset($this->suedeProducts[$index])) {
            return 0;
        }
        
        $product = $this->suedeProducts[$index];
        return (int) ($product['sm'] ?? 0) + 
               (int) ($product['xl'] ?? 0);
    }
    
    public function getSuedeTotalFabricUsed(): float
    {
        $total = 0;
        foreach ($this->suedeProducts as $index => $product) {
            $fabricPerPiece = (float) ($product['fabric_per_piece'] ?? 0);
            $totalPcs = $this->getSuedeProductTotalPcs($index);
            $total += $fabricPerPiece * $totalPcs;
        }
        return round($total, 2);
    }
    
    public function getSuedeRemainingQty(): int
    {
        $totalPcs = 0;
        foreach ($this->suedeProducts as $index => $product) {
            $totalPcs += $this->getSuedeProductTotalPcs($index);
        }
        return $this->suedeMinimumOrderQty - $totalPcs;
    }
    
    public function getSuedeTotalPcs(): int
    {
        $totalPcs = 0;
        foreach ($this->suedeProducts as $index => $product) {
            $totalPcs += $this->getSuedeProductTotalPcs($index);
        }
        return $totalPcs;
    }
    
    public function getSuedeProductFabricUsed(int $index): float
    {
        if (!isset($this->suedeProducts[$index])) {
            return 0;
        }
        
        $product = $this->suedeProducts[$index];
        $fabricPerPiece = (float) ($product['fabric_per_piece'] ?? 0);
        $totalPcs = $this->getSuedeProductTotalPcs($index);
        
        return round($fabricPerPiece * $totalPcs, 2);
    }
    
    public function removeSuedeProduct(int $index): void
    {
        unset($this->suedeProducts[$index]);
        $this->suedeProducts = array_values($this->suedeProducts);
    }
    
    public function resetAllSizes(): void
    {
        foreach ($this->products as $index => $product) {
            if ($this->isScrunchie($index)) {
                $this->products[$index]['quantity'] = 0;
            } else {
                $this->products[$index]['xs'] = 0;
                $this->products[$index]['s'] = 0;
                $this->products[$index]['m'] = 0;
                $this->products[$index]['l'] = 0;
                $this->products[$index]['xl'] = 0;
            }
        }
    }
    
    public function resetAllSuedeSizes(): void
    {
        foreach ($this->suedeProducts as $index => $product) {
            $this->suedeProducts[$index]['sm'] = 0;
            $this->suedeProducts[$index]['xl'] = 0;
        }
    }
}

