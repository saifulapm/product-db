<x-filament-panels::page>
    {{-- Notifications Slide-in Panel --}}
    <div 
        x-data="{ open: @entangle('showNotificationsPanel') }"
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        x-cloak
        class="fixed inset-0 z-50 overflow-hidden"
        style="display: none;"
    >
        {{-- Backdrop --}}
        <div 
            class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity"
            x-on:click="$wire.closeNotificationsPanel()"
        ></div>

        {{-- Panel --}}
        <div class="fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div class="w-screen max-w-md">
                <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-gray-800 shadow-xl">
                    {{-- Header --}}
                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Notifications</h2>
                        <div class="flex items-center gap-2">
                            @if($this->getUnreadNotificationsCount() > 0)
                                <button
                                    wire:click="markAllAsRead"
                                    class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
                                >
                                    Mark all as read
                                </button>
                            @endif
                            <button
                                wire:click="closeNotificationsPanel"
                                class="rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500"
                            >
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Notifications List --}}
                    <div class="flex-1 overflow-y-auto px-6 py-4">
                        @forelse($this->getNotifications() as $notification)
                            @php
                                $data = $notification->data;
                                $isRead = $notification->read_at !== null;
                            @endphp
                            <div 
                                class="mb-4 rounded-lg border p-4 transition-colors {{ $isRead ? 'bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-700' : 'bg-white dark:bg-gray-800 border-primary-200 dark:border-primary-700 shadow-sm' }}"
                                wire:key="notification-{{ $notification->id }}"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            @if(!$isRead)
                                                <span class="h-2 w-2 rounded-full bg-primary-600"></span>
                                            @endif
                                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                                {{ $data['title'] ?? 'Notification' }}
                                            </h3>
                                        </div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            {{ $data['message'] ?? $data['body'] ?? '' }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
                                            {{ $notification->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                    @if(!$isRead)
                                        <button
                                            wire:click="markAsRead('{{ $notification->id }}')"
                                            class="ml-2 text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400"
                                            title="Mark as read"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No notifications</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">You're all caught up!</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
