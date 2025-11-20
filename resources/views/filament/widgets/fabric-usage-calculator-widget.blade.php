<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-2">
                    <span>Active Fabric Usage Calculator</span>
                    @if($currentPoId)
                        <span class="text-xs text-gray-500 dark:text-gray-400">({{ $poName }})</span>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <button 
                        wire:click="resetAllSizes"
                        type="button"
                        class="px-4 py-2 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium transition-colors"
                    >
                        <span class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Reset All Sizes
                        </span>
                    </button>
                </div>
            </div>
        </x-slot>

        <div class="space-y-6">
            <!-- Fabric Roll Minimum Input -->
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <div class="flex items-center gap-4">
                    <label for="fabricRollMinimum" class="text-sm font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">
                        Fabric Roll Minimum (m²):
                    </label>
                    <input 
                        type="text"
                        wire:model="fabricRollMinimum"
                        id="fabricRollMinimum"
                        readonly
                        class="w-32 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm bg-gray-100 dark:bg-gray-800 cursor-not-allowed opacity-75"
                        value="411.45"
                    />
                </div>
            </div>

            <!-- Products Table -->
            @if(count($products) > 0)
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Product Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sample Code</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">Fabric / pcs in m²</th>
                            <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap w-16">XS</th>
                            <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap w-16">S</th>
                            <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap w-16">M</th>
                            <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap w-16">L</th>
                            <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap w-16">XL</th>
                            <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap w-16">Total</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap w-24">Calculator</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($products as $index => $product)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-3">
                                <input 
                                    type="text"
                                    wire:model="products.{{ $index }}.product_name"
                                    readonly
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm bg-gray-100 dark:bg-gray-800 cursor-not-allowed opacity-75"
                                    placeholder="Product Name"
                                    style="min-width: 150px;"
                                />
                            </td>
                            <td class="px-4 py-3">
                                <input 
                                    type="text"
                                    wire:model="products.{{ $index }}.sample_code"
                                    readonly
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm bg-gray-100 dark:bg-gray-800 cursor-not-allowed opacity-75"
                                    placeholder="Sample Code"
                                    style="min-width: 120px;"
                                />
                            </td>
                            <td class="px-4 py-3">
                                <input 
                                    type="number"
                                    wire:model="products.{{ $index }}.fabric_per_piece"
                                    readonly
                                    step="0.01"
                                    min="0"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm bg-gray-100 dark:bg-gray-800 cursor-not-allowed opacity-75"
                                    placeholder="0.00"
                                    style="min-width: 120px;"
                                />
                            </td>
                            @if($this->isScrunchie($index))
                            <!-- Single size input for scrunchie -->
                            <td colspan="5" class="px-2 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <label class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">Quantity:</label>
                                    <input 
                                        type="number"
                                        wire:model.live="products.{{ $index }}.quantity"
                                        step="1"
                                        min="0"
                                        class="w-24 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 text-center"
                                        placeholder="0"
                                    />
                                </div>
                            </td>
                            @else
                            @foreach($this->getSizes() as $sizeKey => $sizeLabel)
                            <td class="px-2 py-3 whitespace-nowrap">
                                <input 
                                    type="number"
                                    wire:model.live="products.{{ $index }}.{{ $sizeKey }}"
                                    step="1"
                                    min="0"
                                    class="w-16 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 text-center"
                                    placeholder="0"
                                />
                            </td>
                            @endforeach
                            @endif
                            <td class="px-2 py-3 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100 text-center">
                                    {{ $this->getProductTotalPcs($index) }}
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ number_format($this->getProductFabricUsed($index), 2) }}
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Summary Section -->
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 mt-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Total Fabric Used</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ number_format($this->getTotalFabricUsed(), 2) }} m²
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Fabric Roll Minimum</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ number_format($fabricRollMinimum, 2) }} m²
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Remaining Fabric</div>
                        <div class="text-2xl font-bold {{ $this->getRemainingFabric() >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ number_format($this->getRemainingFabric(), 2) }} m²
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p>No products added yet. Click "Add Product" to start calculating fabric usage.</p>
            </div>
            @endif

            <!-- Notes -->
            <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                <p>• 100pcs minimum</p>
                <p>• 10% Tolerance of the total qty</p>
            </div>
        </div>
    </x-filament::section>

    <!-- Separator -->
    <div style="margin-top: 3rem; margin-bottom: 3rem; border-top: 1px solid #d1d5db;" class="dark:border-gray-700"></div>

    <!-- Duplicate Section -->
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-2">
                    <span>Suede 1/2 Zip Calculation</span>
                    @if($currentPoId)
                        <span class="text-xs text-gray-500 dark:text-gray-400">({{ $poName }})</span>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <button 
                        wire:click="resetAllSuedeSizes"
                        type="button"
                        class="px-4 py-2 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium transition-colors"
                    >
                        <span class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Reset All Sizes
                        </span>
                    </button>
                </div>
            </div>
        </x-slot>

        <div class="space-y-6">
            <!-- Minimum Order Qty Input -->
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <div class="flex items-center gap-4">
                    <label for="suedeMinimumOrderQty" class="text-sm font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">
                        Minimum order qty:
                    </label>
                    <input 
                        type="text"
                        wire:model="suedeMinimumOrderQty"
                        id="suedeMinimumOrderQty"
                        readonly
                        class="w-32 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm bg-gray-100 dark:bg-gray-800 cursor-not-allowed opacity-75"
                        value="100"
                    />
                    <span class="text-sm text-gray-500 dark:text-gray-400">pcs</span>
                </div>
            </div>

            <!-- Products Table -->
            @if(count($suedeProducts) > 0)
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Product Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sample Code</th>
                            <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap w-24">Small/Medium</th>
                            <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap w-24">Large/XLarge</th>
                            <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap w-16">Total</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap w-24">Calculator</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($suedeProducts as $index => $product)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-3">
                                <input 
                                    type="text"
                                    wire:model="suedeProducts.{{ $index }}.product_name"
                                    readonly
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm bg-gray-100 dark:bg-gray-800 cursor-not-allowed opacity-75"
                                    placeholder="Product Name"
                                    style="min-width: 150px;"
                                />
                            </td>
                            <td class="px-4 py-3">
                                <input 
                                    type="text"
                                    wire:model="suedeProducts.{{ $index }}.sample_code"
                                    readonly
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm bg-gray-100 dark:bg-gray-800 cursor-not-allowed opacity-75"
                                    placeholder="Sample Code"
                                    style="min-width: 120px;"
                                />
                            </td>
                            @foreach($this->getSuedeSizes() as $sizeKey => $sizeLabel)
                            <td class="px-2 py-3 whitespace-nowrap">
                                <input 
                                    type="number"
                                    wire:model.live="suedeProducts.{{ $index }}.{{ $sizeKey }}"
                                    step="1"
                                    min="0"
                                    class="w-24 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 text-center"
                                    placeholder="0"
                                />
                            </td>
                            @endforeach
                            <td class="px-2 py-3 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100 text-center">
                                    {{ $this->getSuedeProductTotalPcs($index) }}
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ number_format($this->getSuedeProductFabricUsed($index), 2) }}
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Summary Section -->
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 mt-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Minimum order qty</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ $suedeMinimumOrderQty }} pcs
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Total Quantity</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ $this->getSuedeTotalPcs() }} pcs
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Remaining qty</div>
                        <div class="text-2xl font-bold {{ $this->getSuedeRemainingQty() >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $this->getSuedeRemainingQty() }} pcs
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p>No products added yet. Click "Add Product" to start calculating fabric usage.</p>
            </div>
            @endif

            <!-- Notes -->
            <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                <p>• 100pcs minimum</p>
                <p>• 10% Tolerance of the total qty</p>
            </div>
        </div>
    </x-filament::section>

    <!-- Save PO Modal -->
    @if($showSaveModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('showSaveModal') }" x-show="show" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" x-on:click="show = false"></div>
            
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        {{ $currentPoId ? 'Update PO Submission' : 'Save PO Submission' }}
                    </h3>
                    
                    <div class="mb-4">
                        <label for="poName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            PO Name
                        </label>
                        <input 
                            type="text"
                            wire:model="poName"
                            id="poName"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            placeholder="Enter PO name..."
                            wire:keydown.enter="savePo"
                        />
                    </div>
                </div>
                
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button 
                        wire:click="savePo"
                        type="button"
                        class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm"
                    >
                        {{ $currentPoId ? 'Update' : 'Save' }}
                    </button>
                    <button 
                        wire:click="closeSaveModal"
                        type="button"
                        class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Load PO Modal -->
    @if($showLoadModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('showLoadModal') }" x-show="show" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" x-on:click="show = false"></div>
            
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        Load PO Submission
                    </h3>
                    
                    @if(count($this->getSavedPos()) > 0)
                    <div class="space-y-2 max-h-96 overflow-y-auto">
                        @foreach($this->getSavedPos() as $po)
                        <div class="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                            <div class="flex-1">
                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $po->name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    Created: {{ $po->created_at->format('M d, Y g:i A') }}
                                    @if($po->updated_at != $po->created_at)
                                        • Updated: {{ $po->updated_at->format('M d, Y g:i A') }}
                                    @endif
                                </div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                    {{ count($po->products ?? []) }} product(s) • Fabric Roll: {{ number_format($po->fabric_roll_minimum, 2) }} m²
                                </div>
                            </div>
                            <div class="flex items-center gap-2 ml-4">
                                <button 
                                    wire:click="loadPo({{ $po->id }})"
                                    type="button"
                                    class="px-3 py-1.5 bg-primary-600 hover:bg-primary-700 text-white rounded text-sm font-medium transition-colors"
                                >
                                    Load
                                </button>
                                <button 
                                    wire:click="deletePo({{ $po->id }})"
                                    wire:confirm="Are you sure you want to delete this PO submission?"
                                    type="button"
                                    class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded text-sm font-medium transition-colors"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <p>No saved PO submissions found.</p>
                    </div>
                    @endif
                </div>
                
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button 
                        wire:click="closeLoadModal"
                        type="button"
                        class="w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</x-filament-widgets::widget>

