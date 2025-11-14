<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex items-center justify-between bg-blue-50 p-4 rounded-lg">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Hats Gallery</h1>
                <p class="text-gray-600">Manage sourcing-ready hat styles</p>
            </div>
            <div class="flex space-x-3">
                <button
                    wire:click="openAddSockModal"
                    class="inline-flex items-center px-6 py-3 border border-transparent shadow-sm text-base font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add New Hat
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6">
            @foreach($this->socks as $sock)
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 overflow-hidden border border-gray-200">
                    <div class="p-4 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 truncate">{{ $sock->name }}</h3>
                            <div class="flex items-center space-x-2">
                                @if($sock->is_active)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Available
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Unavailable
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="p-4 space-y-4">
                        <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden">
                            @if($sock->images && count($sock->images) > 0)
                                <img src="{{ Storage::url($sock->images[0]) }}" 
                                     alt="{{ $sock->name }}" 
                                     class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            @endif
                        </div>

                        <h3 class="text-lg font-semibold text-gray-900 text-center">{{ $sock->name }}</h3>

                        <div class="space-y-1">
                            @if($sock->description)
                                @php
                                    $bulletPoints = array_filter(array_map('trim', explode("\n", $sock->description)));
                                @endphp
                                @foreach($bulletPoints as $point)
                                    @if($point)
                                        <div class="flex items-start space-x-2">
                                            <span class="text-blue-500 mt-1">•</span>
                                            <span class="text-sm text-gray-700">{{ $point }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            @endif
                        </div>

                        <div class="text-center">
                            <span class="text-lg font-bold text-blue-600">${{ number_format($sock->price, 2) }}</span>
                        </div>
                    </div>

                    <div class="px-4 py-3 bg-gray-50 border-t border-gray-100">
                        <div class="flex items-center justify-between">
                            <div class="flex space-x-2">
                                <button 
                                    wire:click="toggleActive({{ $sock->id }})"
                                    class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                >
                                    {{ $sock->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                                
                                <button 
                                    wire:click="deleteSock({{ $sock->id }})"
                                    wire:confirm="Are you sure you want to delete this sock?"
                                    class="inline-flex items-center px-3 py-1 border border-red-300 shadow-sm text-xs font-medium rounded text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                >
                                    Delete
                                </button>
                            </div>
                            
                            <span class="text-xs text-gray-500">
                                Added {{ $sock->created_at->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($this->socks->isEmpty())
            <div class="xl:col-span-6 lg:col-span-3 md:col-span-2 col-span-1 bg-white rounded-lg shadow-md p-6 text-center text-gray-500">
                <p class="text-lg">No sock styles available yet.</p>
                <p class="mt-2">Click "Add New Sock" to get started!</p>
            </div>
        @endif
    </div>

    @if($showAddSockModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeAddSockModal"></div>
                
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit.prevent="addSock">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                                        Add New Sock Style
                                    </h3>
                                    
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Sock Style</label>
                                            <input type="text" wire:model="sockName" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="e.g., Athletic Crew Socks" required>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Bullet Points</label>
                                            <textarea wire:model="sockDescription" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Enter each bullet point on a new line:&#10;• Moisture-wicking technology&#10;• Cushioned sole for comfort&#10;• Reinforced heel and toe"></textarea>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Height of Sock Ribbing</label>
                                            <input type="text" wire:model="sockRibbingHeight" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="e.g., 2 inches, 5cm">
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Starting Price</label>
                                            <input type="number" step="0.01" wire:model="sockPrice" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="0.00">
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Fabric</label>
                                            <input type="text" wire:model="sockFabric" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="e.g., Cotton Blend, Merino Wool">
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Primary Material</label>
                                            <select wire:model="sockMaterial" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                                <option value="">Select Material</option>
                                                <option value="cotton">Cotton</option>
                                                <option value="wool">Wool</option>
                                                <option value="synthetic">Synthetic</option>
                                                <option value="bamboo">Bamboo</option>
                                                <option value="merino">Merino Wool</option>
                                                <option value="blend">Blend</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Add Sock
                            </button>
                            <button type="button" wire:click="closeAddSockModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <style>
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</x-filament-panels::page>

