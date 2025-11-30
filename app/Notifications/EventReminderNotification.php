<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\EventReminder;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class EventReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Event $event,
        public ?EventReminder $reminder = null
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $startTime = $this->event->start_time 
            ? Carbon::parse($this->event->start_time)->format('g:i A')
            : '9:00 AM';
        
        // Handle start_time as time string (HH:MM:SS) or datetime
        if ($this->event->start_time && is_string($this->event->start_time)) {
            $timeParts = explode(' ', $this->event->start_time);
            $startTime = count($timeParts) > 1 
                ? Carbon::parse($this->event->start_time)->format('g:i A')
                : Carbon::createFromFormat('H:i:s', $this->event->start_time)->format('g:i A');
        }
        
        $eventDateTime = $this->event->start_date->format('M d, Y') . ' at ' . $startTime;
        
        $message = (new MailMessage)
            ->subject("Reminder: {$this->event->name} starts in 5 minutes")
            ->greeting('Hello ' . ($notifiable->first_name ?? $notifiable->name) . '!')
            ->line("This is a reminder that **{$this->event->name}** starts in 5 minutes.");
        
        if ($this->event->company_name) {
            $message->line("**Company:** {$this->event->company_name}");
        }
        
        $message->line("**Date & Time:** {$eventDateTime}");
        
        if ($this->event->description) {
            $message->line("**Description:** {$this->event->description}");
        }
        
        if ($this->reminder && $this->reminder->notes) {
            $message->line("**Your Note:** {$this->reminder->notes}");
        }
        
        $message->action('View Event', url('/admin/events/' . $this->event->id))
            ->line('Thank you for using our system!');

        return $message;
    }

    public function toDatabase($notifiable): array
    {
        $startTime = '9:00 AM';
        
        // Handle start_time as time string (HH:MM:SS) or datetime
        if ($this->event->start_time) {
            if (is_string($this->event->start_time)) {
                $timeParts = explode(' ', $this->event->start_time);
                $startTime = count($timeParts) > 1 
                    ? Carbon::parse($this->event->start_time)->format('g:i A')
                    : Carbon::createFromFormat('H:i:s', $this->event->start_time)->format('g:i A');
            } else {
                $startTime = Carbon::parse($this->event->start_time)->format('g:i A');
            }
        }
        
        $eventDateTime = $this->event->start_date->format('M d, Y') . ' at ' . $startTime;
        
        return [
            'title' => 'Event Reminder',
            'message' => "{$this->event->name} starts in 5 minutes",
            'body' => "Event: {$this->event->name}\nDate & Time: {$eventDateTime}",
            'type' => 'event_reminder',
            'format' => 'filament',
            'status' => 'info',
            'icon' => 'heroicon-o-calendar',
            'iconColor' => 'primary',
            'event_id' => $this->event->id,
            'event_name' => $this->event->name,
            'actions' => [
                [
                    'label' => 'View Event',
                    'url' => \App\Filament\Resources\EventResource::getUrl('view', ['record' => $this->event->id]),
                ],
            ],
        ];
    }
}
