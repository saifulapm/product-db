<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Search Section -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Search Products
                    </label>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        id="search"
                        placeholder="Search by product name, SKU, category, or description..."
                        class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                    />
                </div>
            </div>
        </div>

        <!-- Results Section -->
        <div>
            @if(empty($search))
                <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                    <p class="text-lg font-medium">Start typing to search for products</p>
                    <p class="text-sm mt-2">Search by product name, SKU, category, or description</p>
                </div>
            @else
                @php
                    $products = $this->getProducts();
                @endphp

                @if($products->count() > 0)
                    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                        Found {{ $products->count() }} {{ Str::plural('product', $products->count()) }}
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($products as $product)
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
                                <!-- Product Name -->
                                <div class="mb-3">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                                        {{ $product->name }}
                                    </h3>
                                </div>

                                <!-- B2B Pricing & Minimums -->
                                @if($product->minimums || $product->printed_embroidered_1_logo || $product->printed_embroidered_2_logos || $product->printed_embroidered_3_logos)
                                    <div class="space-y-2 mb-4 border-t border-gray-200 dark:border-gray-700 pt-3">
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">B2B Price & Minimums</h4>
                                        
                                        @if($product->minimums)
                                            <div class="flex justify-between items-center">
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Minimums:</span>
                                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $product->minimums }}</span>
                                            </div>
                                        @endif
                                        
                                        @if($product->printed_embroidered_1_logo)
                                            <div class="flex justify-between items-center">
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">1 Logo:</span>
                                                <span class="text-sm font-semibold text-gray-900 dark:text-white">${{ number_format($product->printed_embroidered_1_logo, 2) }}</span>
                                            </div>
                                        @endif
                                        
                                        @if($product->printed_embroidered_2_logos)
                                            <div class="flex justify-between items-center">
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">2 Logos:</span>
                                                <span class="text-sm font-semibold text-gray-900 dark:text-white">${{ number_format($product->printed_embroidered_2_logos, 2) }}</span>
                                            </div>
                                        @endif
                                        
                                        @if($product->printed_embroidered_3_logos)
                                            <div class="flex justify-between items-center">
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">3 Logos:</span>
                                                <span class="text-sm font-semibold text-gray-900 dark:text-white">${{ number_format($product->printed_embroidered_3_logos, 2) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <!-- Product Details - Hidden but kept for search functionality -->
                                <div class="hidden">
                                    @if($product->product_type)
                                        <span data-product-type="{{ $product->product_type }}">{{ $product->product_type }}</span>
                                    @endif
                                    @if($product->category)
                                        <span data-category="{{ $product->category }}">{{ $product->category }}</span>
                                    @endif
                                    @if($product->has_variants && $product->variants->count() > 0)
                                        <span data-variants="{{ $product->variants->count() }}">{{ $product->variants->count() }} {{ Str::plural('variant', $product->variants->count()) }}</span>
                                    @endif
                                </div>

                                <!-- View Product Link -->
                                <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                                    <a 
                                        href="{{ \App\Filament\Resources\ProductResource::getUrl('view', ['record' => $product]) }}"
                                        class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 font-medium"
                                    >
                                        View Product Details →
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-lg font-medium">No products found</p>
                        <p class="text-sm mt-2">Try adjusting your search terms</p>
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-filament-panels::page>
