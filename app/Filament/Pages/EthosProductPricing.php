<?php

namespace App\Filament\Pages;

use App\Models\Product;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;

class EthosProductPricing extends Page
{
    protected static ?string $navigationLabel = 'Product Pricing';

    protected static ?string $title = 'Ethos Product Pricing';

    protected static ?string $navigationGroup = 'Design Tools';

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'ethos/product-pricing';

    protected static string $view = 'filament.pages.ethos-product-pricing';
    
    #[Url]
    public string $search = '';
    
    public function updatedSearch(): void
    {
        // Trigger re-render when search changes
    }
    
    public function getProducts()
    {
        $query = Product::query();
        
        if (!empty($this->search)) {
            $searchTerm = $this->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('sku', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%")
                  ->orWhere('product_type', 'like', "%{$searchTerm}%")
                  ->orWhere('category', 'like', "%{$searchTerm}%");
            });
        }
        
        return $query->with('variants')
            ->orderBy('name')
            ->get();
    }
    
    public function getDisplayPrice(Product $product): string
    {
        // If product has variants, show price range
        if ($product->has_variants && $product->variants->count() > 0) {
            $minPrice = $product->variants->min('price');
            $maxPrice = $product->variants->max('price');
            
            if ($minPrice == $maxPrice) {
                return '$' . number_format($minPrice, 2);
            }
            return '$' . number_format($minPrice, 2) . ' - $' . number_format($maxPrice, 2);
        }
        
        // Use starting_from_price if available, otherwise use price
        if ($product->starting_from_price) {
            return 'Starting from $' . number_format($product->starting_from_price, 2);
        }
        
        if ($product->price) {
            return '$' . number_format($product->price, 2);
        }
        
        return 'No price set';
    }
    
    public function getCost(Product $product): ?string
    {
        if ($product->cost) {
            return '$' . number_format($product->cost, 2);
        }
        return null;
    }
}

