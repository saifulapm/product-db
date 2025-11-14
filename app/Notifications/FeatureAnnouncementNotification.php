<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class FeatureAnnouncementNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => 'feature',
            'format' => 'filament',
            'body' => $this->message,
            'status' => 'info',
            'icon' => 'heroicon-o-sparkles',
            'iconColor' => 'primary',
            'actions' => [],
        ];
    }
}

