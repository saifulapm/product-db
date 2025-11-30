<?php

namespace App\Filament\Resources\EventResource\Widgets;

use App\Models\Event;
use App\Models\EventReminder;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Config;
use Livewire\Attributes\Reactive;

class EventReminderStatus extends Widget
{
    protected static string $view = 'filament.resources.event-resource.widgets.event-reminder-status';
    
    protected int | string | array $columnSpan = 'full';
    
    #[Reactive]
    public ?Event $record = null;
    
    public function getReminder(): ?EventReminder
    {
        $user = auth()->user();
        if (!$user || !$this->record) {
            return null;
        }
        
        return EventReminder::where('event_id', $this->record->id)
            ->where('user_id', $user->id)
            ->first();
    }
    
    public function hasEmailReminder(): bool
    {
        $reminder = $this->getReminder();
        if (!$reminder) {
            return false;
        }
        
        $user = auth()->user();
        return $user && !empty($user->email);
    }
    
    public function hasSmsReminder(): bool
    {
        $reminder = $this->getReminder();
        if (!$reminder) {
            return false;
        }
        
        $user = auth()->user();
        if (!$user || !$user->phone) {
            return false;
        }
        
        // Check if Twilio is configured
        try {
            $twilioConfigured = !empty(Config::get('services.twilio.account_sid')) 
                && !empty(Config::get('services.twilio.auth_token'))
                && !empty(Config::get('services.twilio.from'));
            return $twilioConfigured;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function canReceiveEmail(): bool
    {
        $user = auth()->user();
        return $user && !empty($user->email);
    }
    
    public function canReceiveSms(): bool
    {
        $user = auth()->user();
        if (!$user || !$user->phone) {
            return false;
        }
        
        try {
            return !empty(Config::get('services.twilio.account_sid')) 
                && !empty(Config::get('services.twilio.auth_token'))
                && !empty(Config::get('services.twilio.from'));
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function getReminderNotes(): ?string
    {
        $reminder = $this->getReminder();
        return $reminder?->notes;
    }
}

