<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DashboardHeader;
use App\Filament\Widgets\SendNotificationWidget;
use App\Filament\Widgets\TasksDueWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Actions\Action;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class CustomDashboard extends BaseDashboard
{
    public bool $showNotificationsPanel = false;

    public function getTitle(): string | Htmlable
    {
        $user = auth()->user();
        $firstName = $user->first_name ?? $user->name;
        return 'Hello ' . $firstName;
    }

    public function getVisibleWidgets(): array
    {
        $widgets = parent::getVisibleWidgets();

        $teamNotesWidgets = [];
        $otherWidgets = [];
        $notificationWidgets = [];

        $hasTasksDueWidget = false;
        
        foreach ($widgets as $widget) {
            $widgetClass = $this->resolveWidgetClass($widget);

            if ($widgetClass === DashboardHeader::class) {
                $teamNotesWidgets[] = $widget;
                continue;
            }

            if ($widgetClass === SendNotificationWidget::class) {
                $notificationWidgets[] = $widget;
                continue;
            }
            
            if ($widgetClass === TasksDueWidget::class) {
                $hasTasksDueWidget = true;
                $otherWidgets[] = $widget;
                continue;
            }

            $otherWidgets[] = $widget;
        }
        
        // Add TasksDueWidget if not already in the list
        if (!$hasTasksDueWidget) {
            array_unshift($otherWidgets, TasksDueWidget::class);
        }

        // Remove SendNotificationWidget from dashboard
        // return array_merge($notificationWidgets, $otherWidgets, $teamNotesWidgets);
        return array_merge($otherWidgets, $teamNotesWidgets);
    }

    public function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('notifications')
                ->icon('heroicon-o-bell')
                ->badge(fn () => $this->getUnreadNotificationsCount() > 0 ? $this->getUnreadNotificationsCount() : null)
                ->badgeColor('danger')
                ->color('gray')
                ->action(function () {
                    $this->showNotificationsPanel = true;
                }),
        ];
    }

    public function getUnreadNotificationsCount(): int
    {
        return DatabaseNotification::query()
            ->where('notifiable_type', \App\Models\User::class)
            ->where('notifiable_id', Auth::id())
            ->whereNull('read_at')
            ->count();
    }

    public function getNotifications()
    {
        return DatabaseNotification::query()
            ->where('notifiable_type', \App\Models\User::class)
            ->where('notifiable_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
    }

    public function markAsRead(string $notificationId): void
    {
        $notification = DatabaseNotification::find($notificationId);
        if ($notification && $notification->notifiable_id === Auth::id()) {
            $notification->markAsRead();
        }
    }

    public function markAllAsRead(): void
    {
        DatabaseNotification::query()
            ->where('notifiable_type', \App\Models\User::class)
            ->where('notifiable_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function closeNotificationsPanel(): void
    {
        $this->showNotificationsPanel = false;
    }

    private function resolveWidgetClass(string | object $widget): ?string
    {
        if (is_string($widget)) {
            return $widget;
        }

        if (is_object($widget) && method_exists($widget, 'getWidget')) {
            return $widget->getWidget();
        }

        return null;
    }
}
