<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name',
        'sku',
        'description',
        'price',
        'cost',
        'stock_quantity',
        'min_stock_level',
        'category',
        'brand',
        'status',
        'images',
        'specifications',
        'weight',
        'dimensions',
        'barcode',
        'is_featured',
        'published_at',
        // E-commerce fields
        'website_url',
        'hs_code',
        'parent_product',
        'supplier',
        'product_type',
        'fabric',
        'care_instructions',
        'lead_times',
        'available_sizes',
        'customization_methods',
        'model_size',
        'starting_from_price',
        'minimums',
        'last_inventory_sync',
        'has_variants',
        'notes',
        // Design Notes fields
        'cad_download',
        'tone_on_tone_lighter',
        'tone_on_tone_darker',
        'tone_on_tone_lighter_hex',
        'tone_on_tone_darker_hex',
        // Base color fields
        'base_color',
        'base_color_hex',
        // B2B Pricing fields
        'b2b_price',
        'printed_embroidered_1_logo',
        'printed_embroidered_2_logos',
        'printed_embroidered_3_logos',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'weight' => 'decimal:2',
        'starting_from_price' => 'decimal:2',
        'b2b_price' => 'decimal:2',
        'printed_embroidered_1_logo' => 'decimal:2',
        'printed_embroidered_2_logos' => 'decimal:2',
        'printed_embroidered_3_logos' => 'decimal:2',
        'images' => 'array',
        'specifications' => 'array',
        'customization_methods' => 'array',
        'is_featured' => 'boolean',
        'has_variants' => 'boolean',
        'published_at' => 'datetime',
        'last_inventory_sync' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'active',
        'stock_quantity' => 0,
        'min_stock_level' => 0,
        'is_featured' => false,
        'has_variants' => false,
    ];

    /**
     * Get the validation rules for the model.
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'min_stock_level' => ['required', 'integer', 'min:0'],
            'category' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::in(['active', 'inactive', 'discontinued'])],
            'images' => ['nullable', 'array'],
            'specifications' => ['nullable', 'array'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'dimensions' => ['nullable', 'string', 'max:50'],
            'barcode' => ['nullable', 'string', 'max:50'],
            'is_featured' => ['boolean'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    /**
     * Get the validation rules for updating the model.
     */
    public static function updateRules(int $id): array
    {
        $rules = self::rules();
        $rules['sku'] = ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($id)];
        return $rules;
    }

    /**
     * Check if product is low in stock.
     */
    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->min_stock_level;
    }

    /**
     * Check if product is out of stock.
     */
    public function isOutOfStock(): bool
    {
        return $this->stock_quantity <= 0;
    }

    /**
     * Check if product is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get the profit margin percentage.
     */
    public function getProfitMarginAttribute(): ?float
    {
        if (!$this->cost || $this->cost <= 0) {
            return null;
        }

        return round((($this->price - $this->cost) / $this->cost) * 100, 2);
    }

    /**
     * Scope for active products.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for featured products.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for products in stock.
     */
    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    /**
     * Get the variants for the product.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Get active variants for the product.
     */
    public function activeVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->where('is_active', true);
    }

    /**
     * Get the total inventory across all variants.
     */
    public function getTotalInventoryAttribute(): int
    {
        return $this->variants()->sum('inventory_quantity');
    }

    /**
     * Check if product has any variants in stock.
     */
    public function hasVariantsInStock(): bool
    {
        return $this->variants()->where('inventory_quantity', '>', 0)->exists();
    }

    /**
     * Get the lowest price among variants.
     */
    public function getLowestVariantPriceAttribute(): ?float
    {
        return $this->variants()->min('price');
    }

    /**
     * Get the highest price among variants.
     */
    public function getHighestVariantPriceAttribute(): ?float
    {
        return $this->variants()->max('price');
    }

    /**
     * Scope for low stock products.
     */
    public function scopeLowStock($query)
    {
        return $query->whereRaw('stock_quantity <= min_stock_level');
    }

    /**
     * Register media collections for the product.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cad_download')
            ->acceptsMimeTypes(['application/pdf'])
            ->singleFile();
        
        $this->addMediaCollection('product_images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    /**
     * Register media conversions for CAD PDFs.
     * 
     * Note: PDF conversion requires ImageMagick with Ghostscript.
     * GD driver cannot convert PDFs. Install imagick extension for this feature.
     */
    public function registerMediaConversions(Media $media = null): void
    {
        // PDF preview conversion - requires imagick extension
        // Uncomment when imagick is properly configured:
        // $this->addMediaConversion('preview')
        //     ->pdf()
        //     ->format('jpg')
        //     ->width(300)
        //     ->height(300)
        //     ->sharpen(10)
        //     ->nonQueued();
    }
}
