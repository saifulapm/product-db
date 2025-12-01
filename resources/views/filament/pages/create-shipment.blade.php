<x-filament-panels::page>
    <div class="space-y-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Create A Shipment</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Select a garment and quantity to find the best fitting supply for shipping.
            </p>
        </div>

        {{ $this->form }}

        @if($this->recommendedSupply)
            <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Recommended Supply
                </h3>
                
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Supply Name</label>
                            <p class="mt-1 text-sm text-gray-900 dark:text-white font-semibold">
                                {{ $this->recommendedSupply['name'] }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                            <p class="mt-1 text-sm text-gray-900 dark:text-white">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($this->recommendedSupply['type'] === 'box') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @elseif($this->recommendedSupply['type'] === 'mailer') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                    @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                    @endif">
                                    {{ ucfirst($this->recommendedSupply['type']) }}
                                </span>
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Dimensions</label>
                            <p class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ number_format($this->recommendedSupply['length'], 2) }}" × 
                                {{ number_format($this->recommendedSupply['width'], 2) }}" × 
                                {{ number_format($this->recommendedSupply['height'], 2) }}" 
                                ({{ number_format($this->recommendedSupply['length'] * $this->recommendedSupply['width'] * $this->recommendedSupply['height'], 2) }} in³)
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Available Inventory</label>
                            <p class="mt-1 text-sm text-gray-900 dark:text-white font-semibold">
                                {{ number_format($this->recommendedSupply['quantity'] ?? 0) }} {{ ($this->recommendedSupply['quantity'] ?? 0) == 1 ? 'piece' : 'pieces' }}
                            </p>
                        </div>
                        @if($this->recommendedSupply['waste_percentage'] > 0)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Waste Percentage</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">
                                    {{ $this->recommendedSupply['waste_percentage'] }}% unused space
                                </p>
                            </div>
                        @endif
                    </div>

                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <a 
                            href="{{ route('filament.admin.resources.supplies.edit', $this->recommendedSupply['id']) }}"
                            class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            View Supply Details
                        </a>
                        <button
                            type="button"
                            wire:click="commitShipment"
                            class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            Commit Shipment
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>

