<?php

namespace App\Filament\Components;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationsBell extends Component
{
    public bool $showNotificationsPanel = false;

    public function getUnreadNotificationsCountProperty(): int
    {
        return DatabaseNotification::query()
            ->where('notifiable_type', \App\Models\User::class)
            ->where('notifiable_id', Auth::id())
            ->whereNull('read_at')
            ->count();
    }

    public function getNotificationsProperty()
    {
        return DatabaseNotification::query()
            ->where('notifiable_type', \App\Models\User::class)
            ->where('notifiable_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
    }

    public function toggleNotificationsPanel(): void
    {
        $this->showNotificationsPanel = !$this->showNotificationsPanel;
    }

    public function markAsRead(string $notificationId): void
    {
        $notification = DatabaseNotification::find($notificationId);
        if ($notification && $notification->notifiable_id === Auth::id()) {
            $notification->markAsRead();
        }
        $this->dispatch('notifications-updated');
    }

    public function toggleReadStatus(string $notificationId): void
    {
        $notification = DatabaseNotification::find($notificationId);
        if ($notification && $notification->notifiable_id === Auth::id()) {
            if ($notification->read_at) {
                // Mark as unread
                $notification->update(['read_at' => null]);
            } else {
                // Mark as read
                $notification->markAsRead();
            }
        }
        $this->dispatch('notifications-updated');
    }

    public function markAllAsRead(): void
    {
        DatabaseNotification::query()
            ->where('notifiable_type', \App\Models\User::class)
            ->where('notifiable_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        $this->dispatch('notifications-updated');
    }

    public function closeNotificationsPanel(): void
    {
        $this->showNotificationsPanel = false;
    }

    public function render()
    {
        return view('filament.components.notifications-bell');
    }
}

