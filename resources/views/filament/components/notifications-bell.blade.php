<div class="relative">
    {{-- Bell Icon Button --}}
    <button
        wire:click="toggleNotificationsPanel"
        type="button"
        class="relative flex items-center justify-center rounded-lg p-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
        title="Notifications"
    >
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        @if($this->unreadNotificationsCount > 0)
            <span class="absolute top-0 right-0 flex h-5 w-5 items-center justify-center rounded-full bg-danger-600 text-xs font-bold text-white">
                {{ $this->unreadNotificationsCount > 99 ? '99+' : $this->unreadNotificationsCount }}
            </span>
        @endif
    </button>

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
        style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 9999;"
        wire:ignore.self
    >
        {{-- Backdrop --}}
        <div 
            style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0, 0, 0, 0.5);"
            x-on:click="$wire.closeNotificationsPanel()"
        ></div>

        {{-- Panel --}}
        <div style="position: fixed; top: 0; right: 0; bottom: 0; width: 384px; max-width: 90vw; z-index: 10000;">
            <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-gray-800 shadow-xl">
                    {{-- Header --}}
                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Notifications</h2>
                        <div class="flex items-center gap-2">
                            @if($this->unreadNotificationsCount > 0)
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
                        @forelse($this->notifications as $notification)
                            @php
                                $data = $notification->data;
                                $isRead = $notification->read_at !== null;
                                $isTaskRelated = in_array($data['type'] ?? '', ['task_assigned', 'task_comment', 'task_mention']);
                                $isMention = ($data['type'] ?? '') === 'task_mention';
                                $taskUrl = $isTaskRelated && isset($data['actions'][0]['url']) ? $data['actions'][0]['url'] : null;
                            @endphp
                            <div 
                                class="mb-4 rounded-lg border p-4 transition-colors cursor-pointer {{ $isRead ? 'bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800' : ($isMention ? 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-700 shadow-sm hover:border-yellow-300 dark:hover:border-yellow-600' : 'bg-white dark:bg-gray-800 border-primary-200 dark:border-primary-700 shadow-sm hover:border-primary-300 dark:hover:border-primary-600') }}"
                                wire:key="notification-{{ $notification->id }}"
                                @if($taskUrl)
                                    onclick="window.location.href='{{ $taskUrl }}'"
                                @endif
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            @if(!$isRead)
                                                <span class="h-2 w-2 rounded-full {{ $isMention ? 'bg-yellow-600' : 'bg-primary-600' }} flex-shrink-0"></span>
                                            @endif
                                            @if($isMention)
                                                <span class="text-yellow-600 dark:text-yellow-400 font-bold text-sm flex-shrink-0">@</span>
                                            @endif
                                            <h3 class="text-sm font-semibold {{ $isMention ? 'text-yellow-900 dark:text-yellow-100' : 'text-gray-900 dark:text-white' }}">
                                                {{ $data['title'] ?? 'Notification' }}
                                            </h3>
                                        </div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            {{ $data['message'] ?? $data['body'] ?? '' }}
                                        </p>
                                        @if(isset($data['body']) && $data['body'] !== $data['message'])
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                {{ $data['body'] }}
                                            </p>
                                        @endif
                                        @if($isTaskRelated && isset($data['task_title']))
                                            <p class="text-xs text-primary-600 dark:text-primary-400 mt-1 font-medium">
                                                @if(isset($data['is_subtask']) && $data['is_subtask'] && isset($data['parent_task_title']))
                                                    Subtask: {{ $data['task_title'] }} (Parent: {{ $data['parent_task_title'] }})
                                                @else
                                                    Task: {{ $data['task_title'] }}
                                                @endif
                                            </p>
                                        @endif
                                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
                                            {{ $notification->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                    <div class="flex items-start gap-2 ml-2">
                                        @if($taskUrl)
                                            <a 
                                                href="{{ $taskUrl }}"
                                                onclick="event.stopPropagation();"
                                                class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 hover:underline flex-shrink-0"
                                                title="View Task"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                                </svg>
                                            </a>
                                        @endif
                                        <button
                                            wire:click.stop="toggleReadStatus('{{ $notification->id }}')"
                                            class="text-xs flex-shrink-0 transition-colors {{ $isRead ? 'text-gray-400 hover:text-primary-600 dark:text-gray-500 dark:hover:text-primary-400' : 'text-primary-600 hover:text-primary-700 dark:text-primary-400' }}"
                                            title="{{ $isRead ? 'Mark as unread' : 'Mark as read' }}"
                                        >
                                            @if($isRead)
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                            @else
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            @endif
                                        </button>
                                    </div>
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

