<?php

namespace App\Notifications;

use App\Models\Supply;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupplyReorderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Supply $supply
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $reorderLink = $this->supply->reorder_link 
            ? "Reorder Link: {$this->supply->reorder_link}"
            : 'No reorder link available';

        return (new MailMessage)
            ->subject("Reorder Alert: {$this->supply->name} - Low Inventory")
            ->greeting('Hello ' . ($notifiable->first_name ?? $notifiable->name) . '!')
            ->line("**{$this->supply->name}** has reached its reorder point.")
            ->line("**Current Quantity:** {$this->supply->quantity}")
            ->line("**Reorder Point:** {$this->supply->reorder_point}")
            ->line("**Type:** " . ucfirst($this->supply->type))
            ->when($this->supply->reorder_link, function ($message) {
                return $message->action('Reorder Now', $this->supply->reorder_link);
            })
            ->line('Please order more supplies to maintain inventory levels.')
            ->line('Thank you!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Reorder Alert',
            'message' => "{$this->supply->name} has reached its reorder point",
            'body' => "Current Quantity: {$this->supply->quantity}\nReorder Point: {$this->supply->reorder_point}",
            'type' => 'supply_reorder',
            'format' => 'filament',
            'status' => 'warning',
            'icon' => 'heroicon-o-exclamation-triangle',
            'iconColor' => 'warning',
            'supply_id' => $this->supply->id,
            'supply_name' => $this->supply->name,
            'actions' => [
                [
                    'label' => 'View Supply',
                    'url' => \App\Filament\Resources\SupplyResource::getUrl('edit', ['record' => $this->supply->id]),
                ],
            ],
        ];
    }
}
