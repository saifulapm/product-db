<div>
    <x-filament-panels::page>
        <div>
            @php
                $record = $this->getRecord();
            @endphp

        <!-- Mockup Details Notes Container -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6 mb-6">
            <!-- Header with ID, Send Button, and Status Button -->
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        Mockup Submission : #{{ $record->tracking_number ?? 'N/A' }}
                    </h2>
                </div>
                @php
                    $isClosed = $record->is_completed ?? false;
                    $status = $isClosed ? 'Closed' : 'Open';
                @endphp
                <button 
                    type="button"
                    onclick="openStatusModal()"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2 {{ $isClosed ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-green-600 text-white hover:bg-green-700' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>
                    </svg>
                    <span id="status-text">{{ $status }}</span>
                </button>
            </div>
            
            <!-- Information Grid - 4 Columns Per Row -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <!-- Submission Date -->
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Submission Date</p>
                    <p class="text-sm text-gray-900 dark:text-white">{{ $record->submission_date ? $record->submission_date->format('M d, Y') : ($record->created_at ? $record->created_at->format('M d, Y') : 'N/A') }}</p>
                </div>

                <!-- Customer Name -->
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Customer Name</p>
                    <p class="text-sm text-gray-900 dark:text-white">{{ $record->customer_name ?? 'N/A' }}</p>
                </div>

                <!-- Email -->
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Email</p>
                    <p class="text-sm text-gray-900 dark:text-white">{{ $record->customer_email ?? 'N/A' }}</p>
                </div>

                <!-- Phone -->
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Phone</p>
                    <p class="text-sm text-gray-900 dark:text-white">{{ $record->customer_phone ?? 'N/A' }}</p>
                </div>

                <!-- Company Name -->
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Company Name</p>
                    <p class="text-sm text-gray-900 dark:text-white">{{ $record->company_name ?? 'N/A' }}</p>
                </div>

                <!-- Website -->
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Website</p>
                    <p class="text-sm text-gray-900 dark:text-white">{{ $record->website ?? 'N/A' }}</p>
                </div>

                <!-- Instagram -->
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Instagram</p>
                    <p class="text-sm text-gray-900 dark:text-white">{{ $record->instagram ?? 'N/A' }}</p>
                </div>

                <!-- Status -->
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</p>
                    @if($isClosed)
                        <span class="fi-badge inline-flex items-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30">
                            Closed
                        </span>
                    @else
                        <span class="fi-badge inline-flex items-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30">
                            Open
                        </span>
                    @endif
                </div>
            </div>

            <!-- Notes Section -->
            @if($record->notes)
                <div style="margin-top: 10px; padding-top: 10px;" class="border-t border-gray-200 dark:border-gray-700">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Notes</p>
                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $record->notes }}</p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Divider Line -->
        <div class="border-t border-gray-200 dark:border-gray-700" style="margin-top: 20px; margin-bottom: 20px;"></div>

        <!-- Expand/Collapse Buttons, Upload PDF, and Send to Client -->
        <div class="flex gap-2 mb-4 items-center">
            <button 
                type="button"
                onclick="expandAllProducts()"
                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                Expand All
            </button>
            <button 
                type="button"
                onclick="collapseAllProducts()"
                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                Collapse All
            </button>
            <div x-data="{ showModal: false }">
                <button 
                    type="button"
                    x-on:click="showModal = true"
                    class="px-4 py-2 text-sm font-medium text-white bg-primary-600 dark:bg-primary-500 border border-primary-700 dark:border-primary-600 rounded-lg hover:bg-primary-700 dark:hover:bg-primary-600 transition-colors">
                    Upload PDF
                </button>
                
                <!-- Modal using Alpine.js -->
                <div 
                    x-show="showModal"
                    x-cloak
                    class="fixed inset-0 z-50 overflow-y-auto"
                    style="display: none;"
                    x-on:click.away="showModal = false"
                >
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" x-on:click="showModal = false"></div>
                        
                        <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl dark:bg-gray-800 sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                            <form 
                                method="POST" 
                                action="{{ route('mockups.upload-pdf', $record->id) }}"
                                enctype="multipart/form-data"
                                x-on:submit.prevent="
                                    const formData = new FormData($event.target);
                                    formData.append('_token', document.querySelector('meta[name=csrf-token]').content);
                                    
                                    fetch('{{ route('mockups.upload-pdf', $record->id) }}', {
                                        method: 'POST',
                                        body: formData
                                    })
                                    .then(response => {
                                        if (response.ok || response.redirected) {
                                            showModal = false;
                                            window.location.reload();
                                        } else {
                                            alert('Error uploading PDF');
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error:', error);
                                        alert('Error uploading PDF');
                                    });
                                "
                            >
                                @csrf
                                <div class="px-4 pt-5 pb-4 bg-white dark:bg-gray-800 sm:p-6 sm:pb-4">
                                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white mb-4">
                                        Upload PDF
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                        Upload a PDF file. Each page will be mapped to products in order: Page 1 → Product 1 Front, Page 2 → Product 1 Back, Page 3 → Product 2 Front, etc.
                                    </p>
                                    <div>
                                        <input 
                                            type="file" 
                                            name="pdfFile"
                                            id="pdfFile"
                                            accept=".pdf"
                                            required
                                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-primary-900 dark:file:text-primary-300"
                                        />
                                    </div>
                                </div>
                                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 sm:px-6 sm:flex sm:flex-row-reverse">
                                    <button 
                                        type="submit"
                                        class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-primary-600 border border-transparent rounded-md shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm"
                                    >
                                        Submit
                                    </button>
                                    <button 
                                        type="button"
                                        x-on:click="showModal = false"
                                        class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div x-data="{ showSendModal: false, notes: '' }">
                <button
                    type="button"
                    x-on:click="showSendModal = true"
                    class="px-4 py-2 text-sm font-medium text-white bg-primary-600 dark:bg-primary-500 border border-primary-700 dark:border-primary-600 rounded-lg hover:bg-primary-700 dark:hover:bg-primary-600 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <span>Send Submission to Client</span>
                </button>

                <!-- Filament-styled Modal -->
                <div 
                    x-show="showSendModal"
                    x-cloak
                    class="fixed inset-0 z-50 overflow-y-auto"
                    style="display: none;"
                    x-on:click.away="showSendModal = false"
                >
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75" x-on:click="showSendModal = false"></div>
                        
                        <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white dark:bg-gray-800 rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    Send Submission to Client
                                </h3>
                            </div>
                            
                            <div class="px-6 py-4">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Notes
                                        </label>
                                        <textarea 
                                            x-model="notes"
                                            rows="6"
                                            placeholder="Add any notes or comments about this submission..."
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm resize-none"
                                        ></textarea>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            These notes will be included when sending the submission to the client.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                                <button
                                    type="button"
                                    x-on:click="showSendModal = false; notes = ''"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    x-on:click="sendSubmissionToClient({{ $record->id }}, notes); showSendModal = false; notes = ''"
                                    class="px-4 py-2 text-sm font-medium text-white bg-primary-600 dark:bg-primary-500 border border-primary-700 dark:border-primary-600 rounded-lg hover:bg-primary-700 dark:hover:bg-primary-600 transition-colors">
                                    Send
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Status Filter -->
            <div class="ml-auto">
                <label for="statusFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Filter by Status
                </label>
                <select 
                    id="statusFilter"
                    onchange="filterProductsByStatus(this.value)"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">All Statuses</option>
                    <option value="Pending">Pending</option>
                    <option value="Approved">Approved</option>
                    <option value="Revisions Requested">Revisions Requested</option>
                    <option value="Awaiting Response from Client">Awaiting Response from Client</option>
                    <option value="Removed">Removed</option>
                </select>
            </div>
        </div>

        <!-- Products List -->
        @php
            $products = $record->products ?? [];
        @endphp

        @if(!empty($products))
            <div class="space-y-4">
                @foreach($products as $index => $product)
                    @php
                        $productStatus = $product['status'] ?? 'Pending';
                    @endphp
                    <details class="product-details group bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden" data-product-index="{{ $index }}" data-product-status="{{ $productStatus }}" style="background-color: #ffffff !important;">
                        <summary class="cursor-pointer p-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-gray-400 group-open:rotate-90 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                    {{ $index + 1 }}. {{ $product['product_name'] ?? 'Unnamed Product' }}@if(!empty($product['color']))
                                        <span class="text-gray-600 dark:text-gray-400 font-normal"> - {{ $product['color'] }}</span>
                                    @endif
                                </h3>
                            </div>
                            @php
                                $status = $product['status'] ?? 'Pending';
                                $statusBgColors = [
                                    'Pending' => '#6b7280',
                                    'Approved' => '#10b981',
                                    'Revisions Requested' => '#eab308',
                                    'Awaiting Response from Client' => '#3b82f6',
                                    'Removed' => '#ef4444',
                                ];
                                $statusBgColor = $statusBgColors[$status] ?? $statusBgColors['Pending'];
                            @endphp
                            <div class="px-3 py-1.5 rounded-lg status-badge" style="background-color: {{ $statusBgColor }}; color: white;">
                                <span class="text-xs font-medium">{{ $status }}</span>
                            </div>
                        </summary>
                        
                        <div class="p-4 pt-2.5 border-t border-gray-200" style="background-color: #ffffff !important; padding-top: 10px;">
                            <!-- Pricing and Minimums Section (Above Images) -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Pricing & Minimums
                                </label>
                                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                                    <div class="flex flex-row gap-4">
                                        <!-- Minimums Column (Left) -->
                                        <div class="flex-1">
                                            <h4 class="text-xs font-bold text-gray-900 dark:text-white mb-2 uppercase tracking-wide">MINIMUMS</h4>
                                            <textarea 
                                                id="minimums-{{ $index }}"
                                                data-product-index="{{ $index }}"
                                                rows="3"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm resize-none"
                                                placeholder="Enter minimums (e.g., No minimums, 12 pieces, etc.)"
                                            >{{ $product['minimums'] ?? 'No minimums' }}</textarea>
                                        </div>

                                        <!-- Pricing Column (Right) -->
                                        <div class="flex-1">
                                            <h4 class="text-xs font-bold text-gray-900 dark:text-white mb-2 uppercase tracking-wide">PRICING</h4>
                                            <textarea 
                                                id="pricing-{{ $index }}"
                                                data-product-index="{{ $index }}"
                                                rows="3"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm resize-none"
                                                placeholder="Enter pricing (e.g., Front OR Back Logos - $13&#10;Front AND Back Logos - $18)"
                                            >{{ $product['pricing'] ?? 'Front OR Back Logos - $13' . "\n" . 'Front AND Back Logos - $18' }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col md:flex-row gap-4 items-stretch">
                                <!-- Front Image Upload Field -->
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Front Image
                                    </label>
                                    @php
                                        $frontFile = is_array($product['front_pdf'] ?? null) ? ($product['front_pdf'][0] ?? null) : ($product['front_pdf'] ?? null);
                                        $frontPath = $frontFile ? asset('storage/' . ltrim($frontFile, '/')) : null;
                                        $frontExtension = $frontFile ? strtolower(pathinfo($frontFile, PATHINFO_EXTENSION)) : null;
                                    @endphp
                                    
                                    <div class="w-full">
                                        @if($frontPath)
                                            <div class="mb-3 product-image-container border border-gray-200 rounded-lg overflow-hidden relative group" style="aspect-ratio: 4/5;">
                                                @if(in_array($frontExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                                    <img src="{{ $frontPath }}" alt="Front view" class="w-full h-full object-contain" style="background-color: #ffffff !important;">
                                                @else
                                                    <iframe src="{{ $frontPath }}#toolbar=0&navpanes=0&scrollbar=0" class="w-full h-full bg-white" style="background-color: #ffffff !important;" frameborder="0"></iframe>
                                                @endif
                                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-opacity flex items-center justify-center opacity-0 group-hover:opacity-100">
                                                    <label for="front-upload-{{ $index }}" class="cursor-pointer px-4 py-2 bg-white text-gray-900 rounded-lg shadow-lg text-sm font-medium hover:bg-gray-50">
                                                        Replace Image
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2 mb-2">
                                                <a href="{{ $frontPath }}" target="_blank" class="text-xs text-primary-600 dark:text-primary-400 hover:underline">
                                                    View Full Image
                                                </a>
                                                <span class="text-xs text-gray-400">|</span>
                                                <label for="front-upload-{{ $index }}" class="text-xs text-primary-600 dark:text-primary-400 hover:underline cursor-pointer">
                                                    Upload/Replace
                                                </label>
                                            </div>
                                        @else
                                            <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center hover:border-gray-400 dark:hover:border-gray-500 transition-colors" style="aspect-ratio: 4/5; min-height: 300px;">
                                                <div class="flex flex-col items-center justify-center h-full">
                                                    <svg class="w-12 h-12 text-gray-400 dark:text-gray-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">No front image uploaded</p>
                                                    <label for="front-upload-{{ $index }}" class="cursor-pointer px-3 py-1.5 bg-primary-600 text-white rounded-lg text-xs font-medium hover:bg-primary-700 transition-colors inline-block">
                                                        Upload Image
                                                    </label>
                                                </div>
                                            </div>
                                        @endif
                                        <input 
                                            type="file" 
                                            id="front-upload-{{ $index }}"
                                            data-product-index="{{ $index }}"
                                            data-record-id="{{ $record->id }}"
                                            data-image-type="front"
                                            accept="image/*,.pdf"
                                            class="hidden"
                                            onchange="uploadProductImage(this)"
                                        >
                                    </div>
                                </div>

                                <!-- Back Image Upload Field -->
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Back Image
                                    </label>
                                    @php
                                        $backFile = is_array($product['back_pdf'] ?? null) ? ($product['back_pdf'][0] ?? null) : ($product['back_pdf'] ?? null);
                                        $backPath = $backFile ? asset('storage/' . ltrim($backFile, '/')) : null;
                                        $backExtension = $backFile ? strtolower(pathinfo($backFile, PATHINFO_EXTENSION)) : null;
                                    @endphp
                                    
                                    <div class="w-full">
                                        @if($backPath)
                                            <div class="mb-3 product-image-container border border-gray-200 rounded-lg overflow-hidden relative group" style="aspect-ratio: 4/5;">
                                                @if(in_array($backExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                                    <img src="{{ $backPath }}" alt="Back view" class="w-full h-full object-contain" style="background-color: #ffffff;">
                                                @else
                                                    <iframe src="{{ $backPath }}#toolbar=0&navpanes=0&scrollbar=0" class="w-full h-full bg-white" style="background-color: #ffffff !important;" frameborder="0"></iframe>
                                                @endif
                                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-opacity flex items-center justify-center opacity-0 group-hover:opacity-100">
                                                    <label for="back-upload-{{ $index }}" class="cursor-pointer px-4 py-2 bg-white text-gray-900 rounded-lg shadow-lg text-sm font-medium hover:bg-gray-50">
                                                        Replace Image
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2 mb-2">
                                                <a href="{{ $backPath }}" target="_blank" class="text-xs text-primary-600 dark:text-primary-400 hover:underline">
                                                    View Full Image
                                                </a>
                                                <span class="text-xs text-gray-400">|</span>
                                                <label for="back-upload-{{ $index }}" class="text-xs text-primary-600 dark:text-primary-400 hover:underline cursor-pointer">
                                                    Upload/Replace
                                                </label>
                                            </div>
                                        @else
                                            <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center hover:border-gray-400 dark:hover:border-gray-500 transition-colors" style="aspect-ratio: 4/5; min-height: 300px;">
                                                <div class="flex flex-col items-center justify-center h-full">
                                                    <svg class="w-12 h-12 text-gray-400 dark:text-gray-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">No back image uploaded</p>
                                                    <label for="back-upload-{{ $index }}" class="cursor-pointer px-3 py-1.5 bg-primary-600 text-white rounded-lg text-xs font-medium hover:bg-primary-700 transition-colors inline-block">
                                                        Upload Image
                                                    </label>
                                                </div>
                                            </div>
                                        @endif
                                        <input 
                                            type="file" 
                                            id="back-upload-{{ $index }}"
                                            data-product-index="{{ $index }}"
                                            data-record-id="{{ $record->id }}"
                                            data-image-type="back"
                                            accept="image/*,.pdf"
                                            class="hidden"
                                            onchange="uploadProductImage(this)"
                                        >
                                    </div>
                                </div>

                                <!-- Status and Notes Section -->
                                <div class="md:w-64 flex-shrink-0 flex flex-col space-y-4">
                                    <!-- Status Dropdown -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Status
                                        </label>
                                        <select 
                                            id="status-{{ $index }}"
                                            data-product-index="{{ $index }}"
                                            data-record-id="{{ $record->id }}"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
                                        >
                                            <option value="Pending" {{ ($product['status'] ?? 'Pending') === 'Pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="Approved" {{ ($product['status'] ?? 'Pending') === 'Approved' ? 'selected' : '' }}>Approved</option>
                                            <option value="Revisions Requested" {{ ($product['status'] ?? 'Pending') === 'Revisions Requested' ? 'selected' : '' }}>Revisions Requested</option>
                                            <option value="Awaiting Response from Client" {{ ($product['status'] ?? 'Pending') === 'Awaiting Response from Client' ? 'selected' : '' }}>Awaiting Response from Client</option>
                                            <option value="Removed" {{ ($product['status'] ?? 'Pending') === 'Removed' ? 'selected' : '' }}>Removed</option>
                                        </select>
                                    </div>

                                    <!-- Notes Section -->
                                    <div class="flex-1 flex flex-col">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Notes
                                        </label>
                                        <textarea 
                                            id="notes-{{ $index }}"
                                            data-product-index="{{ $index }}"
                                            data-record-id="{{ $record->id }}"
                                            rows="8"
                                            class="flex-1 w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm resize-none"
                                            placeholder="Add notes for this product..."
                                        >{{ $product['notes'] ?? '' }}</textarea>
                                        
                                        <!-- Save Product Button -->
                                        <button
                                            type="button"
                                            onclick="saveProduct({{ $index }}, {{ $record->id }})"
                                            class="mt-3 w-full px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors font-medium text-sm"
                                        >
                                            Save Product
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </details>
                @endforeach
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">No products added yet.</p>
            </div>
        @endif

        <!-- Mockup Status Modal -->
        <div id="statusModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Change Mockup Status</h3>
                <div class="flex gap-3 mb-4">
                    <button
                        type="button"
                        onclick="changeStatus(false)"
                        class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                        Open
                    </button>
                    <button
                        type="button"
                        onclick="changeStatus(true)"
                        class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium">
                        Closed
                    </button>
                </div>
                <div class="flex justify-end">
                    <button
                        type="button"
                        onclick="closeStatusModal()"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
        </div>

        <!-- Product Status Modal -->
        <div id="productStatusModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Change Product Status</h3>
                <div class="space-y-2 mb-4">
                    <button
                        type="button"
                        onclick="changeProductStatus('Pending')"
                        class="w-full px-4 py-2 bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors font-medium text-left">
                        Pending
                    </button>
                    <button
                        type="button"
                        onclick="changeProductStatus('Approved')"
                        class="w-full px-4 py-2 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded-lg hover:bg-green-200 dark:hover:bg-green-800 transition-colors font-medium text-left">
                        Approved
                    </button>
                    <button
                        type="button"
                        onclick="changeProductStatus('Revisions Requested')"
                        class="w-full px-4 py-2 bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 rounded-lg hover:bg-yellow-200 dark:hover:bg-yellow-800 transition-colors font-medium text-left">
                        Revisions Requested
                    </button>
                    <button
                        type="button"
                        onclick="changeProductStatus('Awaiting Response from Client')"
                        class="w-full px-4 py-2 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors font-medium text-left">
                        Awaiting Response from Client
                    </button>
                    <button
                        type="button"
                        onclick="changeProductStatus('Removed')"
                        class="w-full px-4 py-2 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 rounded-lg hover:bg-red-200 dark:hover:bg-red-800 transition-colors font-medium text-left">
                        Removed
                    </button>
                </div>
                <div class="flex justify-end">
                    <button
                        type="button"
                        onclick="closeProductStatusModal()"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
        </div>

        <script>
        function openStatusModal() {
            document.getElementById('statusModal').classList.remove('hidden');
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
        }

        function changeStatus(isCompleted) {
            @this.call('updateStatus', isCompleted)
                .then(() => {
                    closeStatusModal();
                    // Reload the page to ensure everything updates
                    window.location.reload();
                })
                .catch((error) => {
                    console.error('Error updating status:', error);
                    alert('Failed to update status. Please try again.');
                });
        }

        // Close modal when clicking outside
        document.getElementById('statusModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeStatusModal();
            }
        });

        // Product Status Modal Functions
        let currentProductIndex = null;
        let currentRecordId = null;

        function openProductStatusModal(productIndex, recordId) {
            currentProductIndex = productIndex;
            currentRecordId = recordId;
            document.getElementById('productStatusModal').classList.remove('hidden');
        }

        function closeProductStatusModal() {
            document.getElementById('productStatusModal').classList.add('hidden');
            currentProductIndex = null;
            currentRecordId = null;
        }

        function changeProductStatus(status) {
            if (currentProductIndex === null || currentRecordId === null) {
                return;
            }
            
            updateProductStatus(currentProductIndex, currentRecordId, status);
            closeProductStatusModal();
        }

        // Close product status modal when clicking outside
        document.getElementById('productStatusModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeProductStatusModal();
            }
        });
        function toggleMockupStatus() {
            // This will need to be connected to a backend endpoint to update the status
            // For now, just show an alert
            const currentStatus = document.querySelector('button[onclick="toggleMockupStatus()"]').textContent.includes('Open') ? 'Open' : 'Closed';
            const newStatus = currentStatus === 'Open' ? 'Closed' : 'Open';
            
            if (confirm(`Change mockup status from ${currentStatus} to ${newStatus}?`)) {
                // TODO: Add AJAX call to update status
                console.log('Status change requested:', newStatus);
            }
        }

        function expandAllProducts() {
            const productDetails = document.querySelectorAll('details.product-details');
            productDetails.forEach(details => {
                details.open = true;
            });
        }

        function collapseAllProducts() {
            const productDetails = document.querySelectorAll('details.product-details');
            productDetails.forEach(details => {
                details.open = false;
            });
        }

        function filterProductsByStatus(status) {
            const productDetails = document.querySelectorAll('details.product-details');
            productDetails.forEach(details => {
                const productStatus = details.getAttribute('data-product-status');
                if (status === '' || productStatus === status) {
                    details.style.display = '';
                } else {
                    details.style.display = 'none';
                }
            });
        }

        // Update file name display when file is selected (for the modal)
        document.addEventListener('DOMContentLoaded', () => {
            const fileInput = document.getElementById('pdfFile');
            if (fileInput) {
                fileInput.addEventListener('change', function(e) {
                    if (e.target.files.length > 0) {
                        console.log('File selected:', e.target.files[0].name);
                    }
                });
            }
        });

        // Save product (status, notes, minimums, and pricing)
        function saveProduct(productIndex, recordId) {
            const statusSelect = document.getElementById(`status-${productIndex}`);
            const notesTextarea = document.getElementById(`notes-${productIndex}`);
            const minimumsTextarea = document.getElementById(`minimums-${productIndex}`);
            const pricingTextarea = document.getElementById(`pricing-${productIndex}`);
            
            if (!statusSelect || !notesTextarea || !minimumsTextarea || !pricingTextarea) {
                alert('Error: Could not find product fields');
                return;
            }
            
            const status = statusSelect.value;
            const notes = notesTextarea.value;
            const minimums = minimumsTextarea.value;
            const pricing = pricingTextarea.value;
            
            // Save status, notes, minimums, and pricing in one request
            fetch('{{ route("mockups.save-product", ":id") }}'.replace(':id', recordId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({
                    product_index: productIndex,
                    status: status,
                    notes: notes,
                    minimums: minimums,
                    pricing: pricing
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the status badge in the summary
                    const details = document.querySelector(`details[data-product-index="${productIndex}"]`);
                    if (details) {
                        const statusBadge = details.querySelector('summary .status-badge');
                        if (statusBadge) {
                            const statusSpan = statusBadge.querySelector('span');
                            if (statusSpan) {
                                statusSpan.textContent = status;
                            }
                            // Update badge color
                            const statusColors = {
                                'Pending': '#6b7280',
                                'Approved': '#10b981',
                                'Revisions Requested': '#eab308',
                                'Awaiting Response from Client': '#3b82f6',
                                'Removed': '#ef4444',
                            };
                            const bgColor = statusColors[status] || statusColors['Pending'];
                            statusBadge.style.backgroundColor = bgColor;
                            statusBadge.style.color = 'white';
                            statusBadge.className = 'px-3 py-1.5 rounded-lg status-badge';
                        }
                    }
                    
                    // Show success message
                    alert('Product saved successfully!');
                } else {
                    alert('Error saving product: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving product');
            });
        }

        // Upload product image (front or back)
        function uploadProductImage(input) {
            const file = input.files[0];
            if (!file) {
                return;
            }
            
            const productIndex = input.dataset.productIndex;
            const recordId = input.dataset.recordId;
            const imageType = input.dataset.imageType; // 'front' or 'back'
            
            const formData = new FormData();
            formData.append('file', file);
            formData.append('product_index', productIndex);
            formData.append('image_type', imageType);
            formData.append('_token', document.querySelector('meta[name=csrf-token]').content);
            
            // Show loading state
            const container = input.closest('.flex-1');
            const originalContent = container.innerHTML;
            container.innerHTML = '<div class="flex items-center justify-center p-8"><div class="text-sm text-gray-500">Uploading...</div></div>';
            
            fetch('{{ route("mockups.upload-product-image", ":id") }}'.replace(':id', recordId), {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload page to show new image
                    window.location.reload();
                } else {
                    container.innerHTML = originalContent;
                    alert('Error uploading image: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                container.innerHTML = originalContent;
                alert('Error uploading image');
            });
        }

        // Update product status
        function updateProductStatus(productIndex, recordId, status) {
            fetch('{{ route("mockups.update-product-status", ":id") }}'.replace(':id', recordId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({
                    product_index: productIndex,
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the status badge in the summary
                    const details = document.querySelector(`details[data-product-index="${productIndex}"]`);
                    if (details) {
                        const statusBadge = details.querySelector('summary .status-badge');
                        if (statusBadge) {
                            const statusSpan = statusBadge.querySelector('span');
                            if (statusSpan) {
                                statusSpan.textContent = status;
                            }
                            // Update badge color
                            const statusColors = {
                                'Pending': '#6b7280',
                                'Approved': '#10b981',
                                'Revisions Requested': '#eab308',
                                'Awaiting Response from Client': '#3b82f6',
                                'Removed': '#ef4444',
                            };
                            const bgColor = statusColors[status] || statusColors['Pending'];
                            statusBadge.style.backgroundColor = bgColor;
                            statusBadge.style.color = 'white';
                            statusBadge.className = 'px-3 py-1.5 rounded-lg status-badge';
                        }
                    }
                } else {
                    alert('Error updating status: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating product status');
            });
        }
        </script>

        <!-- Divider Line -->
        <div class="border-t border-gray-200 dark:border-gray-700 my-6"></div>

        <!-- Chat Section -->
        <div class="mt-8 pt-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Team Chat</h2>
        
        <!-- Chat Messages -->
        <div id="chatMessages" class="bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700 p-4 mb-4" style="max-height: 400px; overflow-y: auto;">
            @php
                $comments = $record->comments()->with('user')->orderBy('created_at', 'asc')->get();
            @endphp
            
            @if($comments->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No messages yet. Start the conversation!</p>
            @else
                @foreach($comments as $comment)
                    <div class="mb-4 pb-4 border-b border-gray-200 dark:border-gray-700 last:border-b-0 last:pb-0">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 rounded-full bg-primary-500 flex items-center justify-center text-white text-sm font-medium">
                                    {{ strtoupper(substr($comment->user->name ?? 'U', 0, 1)) }}
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $comment->user->name ?? 'Unknown User' }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $comment->created_at->format('M d, Y g:i A') }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $comment->message }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <!-- Chat Input Form -->
        <form id="chatForm" class="flex gap-2">
            @csrf
            <textarea 
                id="chatMessage"
                name="message"
                rows="3"
                placeholder="Type your message..."
                class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm resize-none"
                required
            ></textarea>
            <button
                type="submit"
                class="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors font-medium text-sm self-end">
                Send
            </button>
        </form>

        <!-- Send Submission to Client Button at Bottom -->
        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700" style="padding-bottom: 30px;">
            <div x-data="{ showSendModal: false, notes: '' }">
                <button
                    type="button"
                    x-on:click="showSendModal = true"
                    class="px-6 py-3 text-sm font-medium text-white bg-primary-600 dark:bg-primary-500 border border-primary-700 dark:border-primary-600 rounded-lg hover:bg-primary-700 dark:hover:bg-primary-600 transition-colors flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <span>Send Submission to Client</span>
                </button>

                <!-- Filament-styled Modal -->
                <div 
                    x-show="showSendModal"
                    x-cloak
                    class="fixed inset-0 z-50 overflow-y-auto"
                    style="display: none;"
                    x-on:click.away="showSendModal = false"
                >
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75" x-on:click="showSendModal = false"></div>
                        
                        <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white dark:bg-gray-800 rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    Send Submission to Client
                                </h3>
                            </div>
                            
                            <div class="px-6 py-4">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Notes
                                        </label>
                                        <textarea 
                                            x-model="notes"
                                            rows="6"
                                            placeholder="Add any notes or comments about this submission..."
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm resize-none"
                                        ></textarea>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            These notes will be included when sending the submission to the client.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                                <button
                                    type="button"
                                    x-on:click="showSendModal = false; notes = ''"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    x-on:click="sendSubmissionToClient({{ $record->id }}, notes); showSendModal = false; notes = ''"
                                    class="px-4 py-2 text-sm font-medium text-white bg-primary-600 dark:bg-primary-500 border border-primary-700 dark:border-primary-600 rounded-lg hover:bg-primary-700 dark:hover:bg-primary-600 transition-colors">
                                    Send
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>

        <script>
        // Handle chat form submission
        document.getElementById('chatForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const messageInput = document.getElementById('chatMessage');
            const message = messageInput.value.trim();
            
            if (!message) {
                return;
            }
            
            const formData = new FormData();
            formData.append('message', message);
            formData.append('_token', document.querySelector('meta[name=csrf-token]').content);
            
            // Disable form while sending
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'Sending...';
            
            fetch('{{ route("mockups.add-comment", $record->id) }}', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear input
                    messageInput.value = '';
                    
                    // Reload page to show new message
                    window.location.reload();
                } else {
                    alert('Error sending message: ' + (data.message || 'Unknown error'));
                    submitButton.disabled = false;
                    submitButton.textContent = 'Send';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error sending message');
                submitButton.disabled = false;
                submitButton.textContent = 'Send';
            });
        });

        // Send submission to client
        function sendSubmissionToClient(recordId, notes) {
            fetch('{{ route("mockups.send-to-client", ":id") }}'.replace(':id', recordId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({
                    notes: notes || ''
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Submission sent to client successfully!');
                } else {
                    alert('Error sending submission: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error sending submission to client');
            });
        }
        </script>
        </div>
        </div>
    </x-filament-panels::page>
</div>
