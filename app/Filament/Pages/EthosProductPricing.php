<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class EthosProductPricing extends Page
{
    protected static ?string $navigationLabel = 'Product Pricing';

    protected static ?string $title = 'Ethos Product Pricing';

    protected static ?string $navigationGroup = 'Ethos';

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'ethos/product-pricing';

    protected static string $view = 'filament.pages.ethos-product-pricing';
}

