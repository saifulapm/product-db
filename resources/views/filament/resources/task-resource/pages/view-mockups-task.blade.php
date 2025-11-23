@php
    $record = $this->getRecord();
    $submissionId = '#' . $record->id;
    $products = $record->mockups_products ?? [];
@endphp

<div class="space-y-6">
        <!-- Header Section -->
        <div class="flex justify-between items-start border-b pb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">MOCKUPS - {{ $record->title }}</h1>
                <div class="mt-2">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">MOCKUP SUBMISSION</span>
                    <span class="text-sm text-gray-600 dark:text-gray-400 ml-2">{{ $submissionId }}</span>
                </div>
            </div>
            <a href="#" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                Book A Design Call
            </a>
        </div>

        <!-- Customer Information -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">CUSTOMER INFORMATION</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">CUSTOMER NAME:</span>
                    <span class="text-sm text-gray-900 dark:text-white ml-2">{{ $record->mockups_customer_name ?? 'N/A' }}</span>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">COMPANY NAME:</span>
                    <span class="text-sm text-gray-900 dark:text-white ml-2">{{ $record->mockups_company_name ?? 'N/A' }}</span>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">EMAIL ADDRESS:</span>
                    <a href="mailto:{{ $record->mockups_customer_email }}" class="text-sm text-primary-600 dark:text-primary-400 ml-2 hover:underline">
                        {{ $record->mockups_customer_email ?? 'N/A' }}
                    </a>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">PHONE NUMBER:</span>
                    <a href="tel:{{ $record->mockups_customer_phone }}" class="text-sm text-gray-900 dark:text-white ml-2">
                        {{ $record->mockups_customer_phone ?? 'N/A' }}
                    </a>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">INSTAGRAM:</span>
                    <a href="https://instagram.com/{{ ltrim($record->mockups_instagram ?? '', '@') }}" target="_blank" class="text-sm text-primary-600 dark:text-primary-400 ml-2 hover:underline">
                        {{ $record->mockups_instagram ?? 'N/A' }}
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-6">
            <!-- Left Column: Products List -->
            <div class="col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="font-semibold mb-4 text-gray-900 dark:text-white">PRODUCTS</h3>
                    <ol class="space-y-2">
                        @foreach($products as $index => $product)
                            <li class="text-sm text-gray-700 dark:text-gray-300">
                                {{ $index + 1 }}. {{ $product['product_name'] ?? 'Unnamed Product' }}
                                @if(!empty($product['style']))
                                    - {{ $product['style'] }}
                                @endif
                                @if(!empty($product['color']))
                                    - {{ $product['color'] }}
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </div>

                <!-- Note Section -->
                @if(!empty($record->mockups_notes))
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 mt-4">
                        <h3 class="font-semibold mb-2 text-gray-900 dark:text-white">NOTE</h3>
                        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $record->mockups_notes }}</p>
                    </div>
                @endif
            </div>

            <!-- Right Column: Product Mockups -->
            <div class="col-span-3 space-y-6">
                @foreach($products as $index => $product)
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                        <div class="grid grid-cols-3 gap-6">
                            <!-- Left: Mockup Display -->
                            <div class="col-span-2">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ strtoupper($product['product_name'] ?? 'Unnamed Product') }}
                                        @if(!empty($product['color']))
                                            {{ $product['color'] }}
                                        @endif
                                        @if(!empty($product['style']))
                                            {{ $product['style'] }}
                                        @endif
                                        Full Color Printed Logos
                                    </h3>
                                    @if(!empty($product['product_pdf']))
                                        <a href="{{ asset('storage/' . $product['product_pdf']) }}" target="_blank" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                            </svg>
                                        </a>
                                    @endif
                                </div>
                                
                                <!-- Mockup Images -->
                                <div class="bg-gray-100 dark:bg-gray-900 rounded-lg p-4 mb-4 min-h-[400px] flex items-center justify-center">
                                    @if(!empty($product['product_pdf']))
                                        <iframe src="{{ asset('storage/' . $product['product_pdf']) }}" class="w-full h-full min-h-[400px] rounded"></iframe>
                                    @else
                                        <div class="text-center text-gray-500 dark:text-gray-400">
                                            <svg class="w-16 h-16 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            <p>No mockup uploaded</p>
                                            <p class="text-xs mt-1">Upload PDF in edit mode</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Right: Review Controls -->
                            <div class="col-span-1 space-y-4">
                                <div>
                                    <h4 class="font-semibold text-sm text-gray-900 dark:text-white mb-1">PRODUCTS:</h4>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        {{ $index + 1 }}. {{ $product['product_name'] ?? 'Unnamed Product' }}
                                        @if(!empty($product['style']))
                                            - {{ $product['style'] }}
                                        @endif
                                    </p>
                                </div>

                                <div>
                                    <h4 class="font-semibold text-sm text-gray-900 dark:text-white mb-1">PRICING:</h4>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">Front OR Back Logos - $13</p>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">Front AND Back Logos - $18</p>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">Front logo = 4x2.35</p>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">We will size grade this x 0.25" per size</p>
                                </div>

                                <div>
                                    <h4 class="font-semibold text-sm text-gray-900 dark:text-white mb-1">MINIMUMS:</h4>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">No minimums</p>
                                </div>

                                <div>
                                    <h4 class="font-semibold text-sm text-gray-900 dark:text-white mb-2">MAKE A DECISION:</h4>
                                    <div class="space-y-2">
                                        <label class="flex items-center">
                                            <input type="radio" name="decision_{{ $index }}" value="approve" class="mr-2" 
                                                wire:click="approveProduct({{ $index }})"
                                                @if(($product['status'] ?? '') === 'approved') checked @endif>
                                            <span class="text-sm text-gray-700 dark:text-gray-300">Approve these designs</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="radio" name="decision_{{ $index }}" value="changes" class="mr-2"
                                                wire:click="requestChanges({{ $index }})"
                                                @if(($product['status'] ?? '') === 'changes_requested') checked @endif>
                                            <span class="text-sm text-gray-700 dark:text-gray-300">Request changes</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="radio" name="decision_{{ $index }}" value="delete" class="mr-2"
                                                wire:click="deleteProduct({{ $index }})"
                                                @if(($product['status'] ?? '') === 'deleted') checked @endif>
                                            <span class="text-sm text-gray-700 dark:text-gray-300">Delete these designs</span>
                                        </label>
                                    </div>
                                </div>

                                <div>
                                    <h4 class="font-semibold text-sm text-gray-900 dark:text-white mb-2">ADJUSTMENT NOTES:</h4>
                                    <textarea 
                                        wire:model.defer="productNotes.{{ $index }}"
                                        wire:blur="saveProductNotes({{ $index }}, $event.target.value)"
                                        class="w-full border border-gray-300 dark:border-gray-600 rounded p-2 text-sm dark:bg-gray-700 dark:text-white"
                                        rows="4"
                                        placeholder="Please be as detailed here as possible">{{ $product['adjustment_notes'] ?? '' }}</textarea>
                                </div>

                                <div class="space-y-2 pt-2">
                                    <button class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600 text-sm">
                                        Upload Reference Attachment
                                    </button>
                                    <button class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600 text-sm">
                                        Upload Voice Note
                                    </button>
                                    <a href="{{ \App\Filament\Resources\TaskResource::getUrl('edit', ['record' => $record]) }}" class="block w-full px-4 py-2 bg-primary-600 text-white rounded hover:bg-primary-700 text-sm text-center">
                                        Save & Proceed
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

