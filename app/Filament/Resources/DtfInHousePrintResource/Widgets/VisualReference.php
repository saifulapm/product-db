<?php

namespace App\Filament\Resources\DtfInHousePrintResource\Widgets;

use Filament\Widgets\Widget;

class VisualReference extends Widget
{
    protected static string $view = 'filament.resources.dtf-in-house-print-resource.widgets.visual-reference';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 100;
}

