<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Product Pricing Overview</x-slot>

            <p class="text-sm text-gray-600">
                Track multiple products and their calculated pricing at a glance. Graphic area cost is fixed at
                ${{ number_format(\App\Filament\Pages\EthosProductPricing::PRICE_PER_SQUARE_INCH, 2) }} per square inch.
                Use the “Add Product” action above the table to capture a new quote.
            </p>
        </x-filament::section>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
