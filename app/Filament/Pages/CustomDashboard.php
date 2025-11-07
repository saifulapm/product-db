<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DashboardHeader;
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

        foreach ($widgets as $widget) {
            $widgetClass = $this->resolveWidgetClass($widget);

            if ($widgetClass === DashboardHeader::class) {
                $teamNotesWidgets[] = $widget;
                continue;
            }

            $otherWidgets[] = $widget;
        }

        return array_merge($otherWidgets, $teamNotesWidgets);
    }

    public function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
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
