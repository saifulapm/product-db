<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate SKU if not provided (required by database)
        if (empty($data['sku'])) {
            $data['sku'] = $this->generateUniqueSku();
        }
        
        // Set default values for required fields
        if (empty($data['price'])) {
            $data['price'] = 0.00;
        }
        
        if (!isset($data['cost'])) {
            $data['cost'] = 0.00;
        }
        
        // Set default values for other required fields
        if (!isset($data['stock_quantity'])) {
            $data['stock_quantity'] = 0;
        }
        
        if (!isset($data['min_stock_level'])) {
            $data['min_stock_level'] = 0;
        }
        
        if (!isset($data['is_featured'])) {
            $data['is_featured'] = false;
        }
        
        if (!isset($data['has_variants'])) {
            $data['has_variants'] = false;
        }
        
        if (empty($data['status'])) {
            $data['status'] = 'active';
        }
        
        // Convert available_sizes array to comma-separated string if it's an array
        if (isset($data['available_sizes']) && is_array($data['available_sizes'])) {
            $data['available_sizes'] = implode(', ', $data['available_sizes']);
        }
        
        return $data;
    }

    private function generateUniqueSku(): string
    {
        // Generate a simple unique SKU based on timestamp and random number
        $timestamp = time();
        $random = mt_rand(1000, 9999);
        $sku = 'SKU-' . $timestamp . '-' . $random;
        
        // Ensure uniqueness
        while (Product::where('sku', $sku)->exists()) {
            $random = mt_rand(1000, 9999);
            $sku = 'SKU-' . $timestamp . '-' . $random;
        }
        
        return $sku;
    }
}
