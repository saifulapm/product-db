<x-filament-panels::page>
    <style>
        .master-tasks-grid-container {
            width: 100% !important;
            max-width: 100% !important;
        }
        .master-tasks-grid {
            display: grid !important;
            grid-template-columns: repeat(1, minmax(0, 1fr)) !important;
        }
        @media (min-width: 640px) {
            .master-tasks-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }
        @media (min-width: 768px) {
            .master-tasks-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            }
        }
    </style>
    <div class="space-y-6 w-full max-w-full master-tasks-grid-container">
        <!-- Search and Sort Controls -->
        <div class="space-y-4 mb-6">
            <div class="flex flex-col sm:flex-row gap-4 items-end">
            <div class="flex-1">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search tasks by title..."
                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                />
            </div>
            </div>
            
            <!-- Filters -->
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="sm:w-64">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Task Type
                    </label>
                    <select
                        wire:model.live="taskTypeFilter"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                    >
                        <option value="">All Task Types</option>
                        @foreach($this->getTaskTypeOptions() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
            </div>
            <div class="sm:w-64">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Status
                    </label>
                <select
                        wire:model.live="statusFilter"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                >
                        <option value="">All Tasks</option>
                        <option value="incomplete">Incomplete Only</option>
                        <option value="complete">Complete Only</option>
                </select>
                </div>
            </div>
        </div>
        
        <!-- Incomplete Section -->
        @if(empty($statusFilter) || $statusFilter === 'incomplete')
        <div>
            <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Incomplete</h2>
            <div class="space-y-4 w-full">
                @forelse($this->getIncompleteTasks() as $task)
                    @php
                        $projectName = $task->project->name ?? '';
                        $isProductAdditions = strtolower($projectName) === 'product additions';
                        $isWebsiteImages = strtolower($projectName) === 'website images';
                        
                        if ($isProductAdditions) {
                            $bgColor = 'bg-purple-50 dark:bg-purple-950/50 dark:border-purple-700/50';
                            $borderColor = 'border-purple-200 dark:border-purple-700';
                            $hoverColor = 'hover:bg-purple-100 dark:hover:bg-purple-950/70';
                        } elseif ($isWebsiteImages) {
                            $bgColor = 'bg-blue-50 dark:bg-blue-950/50 dark:border-blue-700/50';
                            $borderColor = 'border-blue-200 dark:border-blue-700';
                            $hoverColor = 'hover:bg-blue-100 dark:hover:bg-blue-950/70';
                        } else {
                            $bgColor = 'bg-orange-50 dark:bg-orange-950/50 dark:border-orange-700/50';
                            $borderColor = 'border-orange-200 dark:border-orange-700';
                            $hoverColor = 'hover:bg-orange-100 dark:hover:bg-orange-950/70';
                        }
                    @endphp
                    <div 
                        class="{{ $bgColor }} {{ $hoverColor }} rounded-lg shadow-sm border {{ $borderColor }} flex items-center justify-between p-4 hover:shadow-md transition-all relative task-card w-full" 
                        data-task-id="{{ $task->id }}"
                        x-data="contextMenu()"
                        @contextmenu.prevent="positionMenu($event)"
                        @click.away="showMenu = false"
                    >
                        <!-- Context Menu -->
                        <div 
                            x-show="showMenu"
                            x-cloak
                            class="absolute z-50 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 min-w-[120px]"
                            style="display: none; left: 0; top: 0;"
                            x-ref="contextMenu"
                            :style="`left: ${menuX}px; top: ${menuY}px;`"
                        >
                            <button 
                                wire:click="editTask({{ $task->id }})"
                                class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Edit
                            </button>
                            <button 
                                wire:click="deleteTask({{ $task->id }})"
                                wire:confirm="Are you sure you want to delete this task?"
                                class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Delete
                            </button>
                        </div>
                        
                        <!-- List View Layout - Single Row -->
                        <div class="flex items-center flex-1 min-w-0 gap-4">
                            <!-- Task Name -->
                            <div class="flex-shrink-0 min-w-0" style="flex-basis: 200px;">
                                <a href="{{ $this->getViewTaskUrl($task) }}" class="text-sm font-semibold text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 underline block truncate">
                                {{ $task->title }}
                            </a>
                        </div>
                            
                            <!-- Progress Bar -->
                            <div class="flex-1 min-w-0 px-4">
                                @php
                                    $progressPercentage = $this->getProgressPercentage($task);
                                    $allSubtasksCompleted = $task->subtasks->count() > 0 && $task->subtasks->where('is_completed', true)->count() === $task->subtasks->count();
                                    $gradient = $allSubtasksCompleted 
                                        ? 'linear-gradient(to right, #9ca3af, #6b7280, #4b5563)' 
                                        : 'linear-gradient(to right, #93c5fd, #3b82f6, #1e40af)';
                                @endphp
                                <div class="w-full bg-gray-200 dark:bg-gray-700/50 rounded-full h-3 shadow-inner">
                                    <div 
                                        class="h-3 rounded-full transition-all duration-300"
                                        style="width: {{ $progressPercentage }}%; background: {{ $gradient }}; min-width: 2px;"
                                    ></div>
                        </div>
                            </div>
                            
                            <!-- Subtask Count -->
                            <div class="flex-shrink-0">
                                @if($task->subtasks->count() > 0)
                                    <div class="text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap font-medium">
                                        {{ $task->subtasks->where('is_completed', true)->count() }}/{{ $task->subtasks->count() }} subtasks
                                    </div>
                                @else
                                    <div class="text-sm text-gray-400 dark:text-gray-500 whitespace-nowrap">
                                        0 subtasks
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-8 text-gray-500 dark:text-gray-400">
                        No incomplete tasks
                    </div>
                @endforelse
            </div>
        </div>
        @endif

        <!-- Separator -->
        @if(empty($statusFilter))
        <div class="border-t border-gray-300 dark:border-gray-700 my-6"></div>
        @endif

        <!-- Complete Section -->
        @if(empty($statusFilter) || $statusFilter === 'complete')
        <div>
            <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Complete</h2>
            <div class="space-y-4 w-full">
                @forelse($this->getCompleteTasks() as $task)
                    <div 
                        class="bg-gray-200 dark:bg-gray-700 dark:border-gray-600 rounded-lg shadow-sm border border-gray-300 dark:border-gray-600 flex items-center justify-between p-4 hover:shadow-md hover:bg-gray-300 dark:hover:bg-gray-600 transition-all relative task-card w-full" 
                        data-task-id="{{ $task->id }}"
                        x-data="contextMenu()"
                        @contextmenu.prevent="positionMenu($event)"
                        @click.away="showMenu = false"
                    >
                        <!-- Context Menu -->
                        <div 
                            x-show="showMenu"
                            x-cloak
                            class="absolute z-50 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 min-w-[120px]"
                            style="display: none; left: 0; top: 0;"
                            x-ref="contextMenu"
                            :style="`left: ${menuX}px; top: ${menuY}px;`"
                        >
                            <button 
                                wire:click="editTask({{ $task->id }})"
                                class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Edit
                            </button>
                            <button 
                                wire:click="markTaskAsIncomplete({{ $task->id }})"
                                wire:confirm="Are you sure you want to mark this task as incomplete?"
                                class="w-full text-left px-4 py-2 text-sm text-orange-600 dark:text-orange-400 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Mark as Incomplete
                            </button>
                            <button 
                                wire:click="deleteTask({{ $task->id }})"
                                wire:confirm="Are you sure you want to delete this task?"
                                class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Delete
                            </button>
                        </div>
                        
                        <!-- List View Layout - Single Row -->
                        <div class="flex items-center flex-1 min-w-0 gap-4">
                            <!-- Task Name -->
                            <div class="flex-shrink-0 min-w-0" style="flex-basis: 200px;">
                                <a href="{{ $this->getViewTaskUrl($task) }}" class="text-sm font-semibold text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 underline block truncate">
                                {{ $task->title }}
                            </a>
                        </div>
                            
                            <!-- Progress Bar -->
                            <div class="flex-1 min-w-0 px-4">
                                @php
                                    $progressPercentage = $this->getProgressPercentage($task);
                                    $allSubtasksCompleted = $task->subtasks->count() > 0 && $task->subtasks->where('is_completed', true)->count() === $task->subtasks->count();
                                    $gradient = $allSubtasksCompleted 
                                        ? 'linear-gradient(to right, #9ca3af, #6b7280, #4b5563)' 
                                        : 'linear-gradient(to right, #93c5fd, #3b82f6, #1e40af)';
                                @endphp
                                <div class="w-full bg-gray-200 dark:bg-gray-700/50 rounded-full h-3 shadow-inner">
                                    <div 
                                        class="h-3 rounded-full transition-all duration-300"
                                        style="width: {{ $progressPercentage }}%; background: {{ $gradient }}; min-width: 2px;"
                                    ></div>
                        </div>
                            </div>
                            
                            <!-- Subtask Count -->
                            <div class="flex-shrink-0">
                                @if($task->subtasks->count() > 0)
                                    <div class="text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap font-medium">
                                        {{ $task->subtasks->where('is_completed', true)->count() }}/{{ $task->subtasks->count() }} subtasks
                                    </div>
                                @else
                                    <div class="text-sm text-gray-400 dark:text-gray-500 whitespace-nowrap">
                                        0 subtasks
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-8 text-gray-500 dark:text-gray-400">
                        No completed tasks
                    </div>
                @endforelse
            </div>
        </div>
        @endif

        <!-- Incoming Shipments Section -->
        <div class="mt-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Incoming Shipments</h2>
                <a 
                    href="{{ \App\Filament\Resources\IncomingShipmentResource::getUrl('create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Shipment
                </a>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tracking #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Carrier</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Supplier</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Expected Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Items</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($this->getIncomingShipments() as $shipment)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">
                                        @if($shipment->name)
                                            {{ $shipment->name }}
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        @if($shipment->tracking_number)
                                            {{ $shipment->tracking_number }}
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $shipment->carrier ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $shipment->supplier ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $statusColors = [
                                                'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                'in_transit' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                                'received' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                'delayed' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                                'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                            ];
                                            $statusLabels = [
                                                'pending' => 'Pending',
                                                'in_transit' => 'In Transit',
                                                'received' => 'Received',
                                                'delayed' => 'Delayed',
                                                'cancelled' => 'Cancelled',
                                            ];
                                            $color = $statusColors[$shipment->status] ?? $statusColors['pending'];
                                            $label = $statusLabels[$shipment->status] ?? ucfirst($shipment->status);
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $color }}">
                                            {{ $label }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        @if($shipment->expected_date)
                                            {{ $shipment->expected_date->format('M d, Y') }}
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        @if($shipment->items && is_array($shipment->items) && count($shipment->items) > 0)
                                            @php
                                                $count = count($shipment->items);
                                                $totalQty = array_sum(array_column($shipment->items, 'quantity'));
                                                $uniqueStyles = count(array_unique(array_filter(array_column($shipment->items, 'style'))));
                                            @endphp
                                            {{ $count }} line(s) - {{ $uniqueStyles }} style(s) - {{ $totalQty }} pcs
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">No items</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a 
                                            href="{{ $this->getViewShipmentUrl($shipment) }}"
                                            class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300"
                                        >
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        No incoming shipments yet. 
                                        <a 
                                            href="{{ \App\Filament\Resources\IncomingShipmentResource::getUrl('create') }}"
                                            class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300 ml-1"
                                        >
                                            Add your first shipment
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('contextMenu', () => ({
                showMenu: false,
                menuX: 0,
                menuY: 0,
                positionMenu(event) {
                    this.showMenu = true;
                    const rect = event.currentTarget.getBoundingClientRect();
                    this.menuX = event.clientX - rect.left;
                    this.menuY = event.clientY - rect.top;
                    
                    // Adjust position on next tick after menu is rendered
                    this.$nextTick(() => {
                        const menu = this.$refs.contextMenu;
                        if (!menu) return;
                        
                        const menuRect = menu.getBoundingClientRect();
                        const viewportWidth = window.innerWidth;
                        const viewportHeight = window.innerHeight;
                        
                        let left = this.menuX;
                        let top = this.menuY;
                        
                        // Adjust if menu would go off screen
                        if (left + menuRect.width > viewportWidth) {
                            left = viewportWidth - menuRect.width - 10;
                        }
                        if (top + menuRect.height > viewportHeight) {
                            top = viewportHeight - menuRect.height - 10;
                        }
                        
                        menu.style.left = left + 'px';
                        menu.style.top = top + 'px';
                    });
                }
            }));
        });
    </script>
</x-filament-panels::page>

