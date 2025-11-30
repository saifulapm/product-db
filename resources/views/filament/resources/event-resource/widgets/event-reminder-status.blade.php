<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Your Reminder Status
        </x-slot>
        
        <div class="space-y-4">
            @php
                $reminder = $this->getReminder();
                $hasEmail = $this->hasEmailReminder();
                $hasSms = $this->hasSmsReminder();
            @endphp
            
            @if($reminder)
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2">
                            @if($hasEmail)
                                <x-heroicon-o-envelope class="w-5 h-5 text-success-600 dark:text-success-400" />
                                <span class="text-sm font-medium text-gray-900 dark:text-white">Email Reminder</span>
                                <x-filament::badge color="success" size="sm">Active</x-filament::badge>
                            @else
                                <x-heroicon-o-envelope class="w-5 h-5 text-gray-400" />
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Email Reminder</span>
                                @if($this->canReceiveEmail())
                                    <x-filament::badge color="gray" size="sm">Not Set</x-filament::badge>
                                @else
                                    <x-filament::badge color="gray" size="sm">No Email Address</x-filament::badge>
                                @endif
                            @endif
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2">
                            @if($hasSms)
                                <x-heroicon-o-device-phone-mobile class="w-5 h-5 text-success-600 dark:text-success-400" />
                                <span class="text-sm font-medium text-gray-900 dark:text-white">SMS Reminder</span>
                                <x-filament::badge color="success" size="sm">Active</x-filament::badge>
                            @else
                                <x-heroicon-o-device-phone-mobile class="w-5 h-5 text-gray-400" />
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">SMS Reminder</span>
                                @if($this->canReceiveSms())
                                    <x-filament::badge color="gray" size="sm">Not Set</x-filament::badge>
                                @else
                                    @if(!auth()->user()->phone)
                                        <x-filament::badge color="gray" size="sm">No Phone Number</x-filament::badge>
                                    @else
                                        <x-filament::badge color="gray" size="sm">Twilio Not Configured</x-filament::badge>
                                    @endif
                                @endif
                            @endif
                        </div>
                    </div>
                    
                    @if($this->getReminderNotes())
                        <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Your Notes:</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $this->getReminderNotes() }}</p>
                        </div>
                    @endif
                    
                    <div class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                        <p>Reminder will be sent 5 minutes before the event starts.</p>
                    </div>
                </div>
            @else
                <div class="text-center py-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">You haven't set a reminder for this event yet.</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">Click "Set Reminder" above to receive notifications.</p>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

