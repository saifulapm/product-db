<x-filament-widgets::widget>
    <div class="widget-content">
        <x-filament::section>
            <x-slot name="header">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
                        </svg>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Feature Announcements</p>
                    </div>
                    {{ $this->getAction('send_notification') }}
                </div>
            </x-slot>
            <div class="text-sm text-gray-600 dark:text-gray-400 pt-4">
                <p class="mb-2">Use this section to notify all team members about new features, updates, or important announcements.</p>
                @if(!$this->canSendNotifications())
                    <p class="text-warning-600 dark:text-warning-400 font-medium">Only Super Admins can send feature announcements.</p>
                @endif
            </div>
        </x-filament::section>
    </div>
    <x-filament-actions::modals />
</x-filament-widgets::widget>

