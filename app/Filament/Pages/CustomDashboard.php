<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DashboardHeader;
use App\Filament\Widgets\SendNotificationWidget;
use App\Filament\Widgets\TasksDueWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

class CustomDashboard extends BaseDashboard
{

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
