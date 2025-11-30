<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Ethos')
            ->brandLogo(asset('images/ethos-logo.svg'))
            ->favicon(asset('images/ethos-logo.svg'))
            ->colors([
                'primary' => Color::Blue,
                // Tailwind color palette - all available colors
                'red' => Color::Red,
                'orange' => Color::Orange,
                'amber' => Color::Amber,
                'yellow' => Color::Yellow,
                'lime' => Color::Lime,
                'green' => Color::Green,
                'emerald' => Color::Emerald,
                'teal' => Color::Teal,
                'cyan' => Color::Cyan,
                'sky' => Color::Sky,
                'blue' => Color::Blue,
                'indigo' => Color::Indigo,
                'violet' => Color::Violet,
                'purple' => Color::Purple,
                'fuchsia' => Color::Fuchsia,
                'pink' => Color::Pink,
                'rose' => Color::Rose,
                'slate' => Color::Slate,
                'gray' => Color::Gray,
                'zinc' => Color::Zinc,
                'neutral' => Color::Neutral,
                'stone' => Color::Stone,
            ])
            ->navigationGroups([
                NavigationGroup::make('Tasks'),
                NavigationGroup::make('Design Tools'),
                NavigationGroup::make('Mockups'),
                NavigationGroup::make('In House Print'),
                NavigationGroup::make('Embroidery'),
                NavigationGroup::make('Headwear'),
                NavigationGroup::make('Patches'),
                NavigationGroup::make('Socks'),
                NavigationGroup::make('Bottles'),
                NavigationGroup::make('Towels'),
                NavigationGroup::make('Inventory'),
                NavigationGroup::make('Incoming Shipments'),
                NavigationGroup::make('Events'),
                NavigationGroup::make('Customer Service'),
                NavigationGroup::make('Data'),
                NavigationGroup::make('Admin'),
            ])
            ->navigationItems([
                NavigationItem::make('Patches')
                    ->group('Patches')
                    ->icon('heroicon-o-squares-2x2')
                    ->url(fn (): string => \App\Filament\Resources\PatchResource::getUrl())
                    ->sort(0)
                    ->visible(fn (): bool => auth()->check() && auth()->user()->hasPermission('patches.view')),
                NavigationItem::make('Submissions')
                    ->group('Mockups')
                    ->icon('heroicon-o-photo')
                    ->url(fn (): string => \App\Filament\Resources\MockupsSubmissionResource::getUrl('index'))
                    ->sort(1)
                    ->visible(fn (): bool => auth()->check() && auth()->user()->hasPermission('mockups.submissions.view')),
                NavigationItem::make('Inventory')
                    ->group('Inventory')
                    ->icon('heroicon-o-cube')
                    ->url(fn (): string => \App\Filament\Pages\Inventory::getUrl())
                    ->sort(0)
                    ->visible(fn (): bool => auth()->check()),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverPages(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->pages([
                \App\Filament\Pages\CustomDashboard::class,
                \App\Filament\Pages\ProfileSettings::class,
                \App\Filament\Pages\ManageDtfInHousePrints::class,
                \App\Filament\Pages\QuickCadBuilder::class,
                \App\Filament\Pages\PoSubmission::class,
                \App\Filament\Pages\Inventory::class,
            ])
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->url(fn (): string => \App\Filament\Pages\ProfileSettings::getUrl())
                    ->icon('heroicon-o-user-circle'),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->routes(function (Panel $panel) {
                Route::get('/products/download-csv-template', function () {
                    $headers = [
                        'name',
                        'sku',
                        'supplier',
                        'product_type',
                        'website_url',
                        'base_color',
                        'tone_on_tone_darker',
                        'tone_on_tone_lighter',
                        'notes',
                        'fabric',
                        'available_sizes',
                        'price',
                        'cost',
                        'stock_quantity',
                        'min_stock_level',
                        'status',
                        'description',
                        'category',
                        'brand',
                        'weight',
                        'dimensions',
                        'barcode',
                        'is_featured',
                        'hs_code',
                        'parent_product',
                        'care_instructions',
                        'lead_times',
                        'customization_methods',
                        'model_size',
                        'starting_from_price',
                        'minimums',
                        'has_variants',
                        'cad_download',
                    ];
                    
                    // Create CSV content with headers
                    $csvContent = implode(',', $headers) . "\n";
                    
                    // Add sample row with example data
                    $sampleRow = [
                        'Sample Product',
                        'SKU-12345',
                        'Sample Supplier',
                        'T-Shirt',
                        'https://example.com/product',
                        '#ffffff',
                        '#e8e8e8',
                        '#f7f7f7',
                        'Sample notes',
                        'Cotton',
                        'S,M,L,XL',
                        '29.99',
                        '15.00',
                        '100',
                        '10',
                        'active',
                        'Product description',
                        'Apparel',
                        'Brand Name',
                        '0.5',
                        '10x12x2',
                        '123456789',
                        'false',
                        '',
                        '',
                        'Machine wash',
                        '2-3 weeks',
                        '',
                        'M',
                        '29.99',
                        '',
                        'false',
                        '',
                    ];
                    $csvContent .= implode(',', array_map(function($value) {
                        // Escape commas and quotes in CSV
                        if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
                            return '"' . str_replace('"', '""', $value) . '"';
                        }
                        return $value;
                    }, $sampleRow)) . "\n";
                    
                    return \Illuminate\Support\Facades\Response::streamDownload(function () use ($csvContent) {
                        echo $csvContent;
                    }, 'product_template.csv', [
                        'Content-Type' => 'text/csv; charset=UTF-8',
                    ]);
                })->name('resources.products.download-csv-template');
                
                // Secure CAD file download route
                Route::get('/media/{media}/download', function (Media $media) {
                    // Check if the media belongs to a product and user has access
                    $model = $media->model;
                    if ($model && method_exists($model, 'getMedia')) {
                        // Verify the media collection is 'cad_download'
                        if ($media->collection_name === 'cad_download') {
                            $diskName = $media->disk;
                            $path = $media->getPath();
                            
                            // Use Storage facade for better compatibility
                            $storage = Storage::disk($diskName);
                            
                            // Check if file exists
                            if (!$storage->exists($path)) {
                                abort(404, 'File not found: ' . $path);
                            }
                            
                            // Create a streamed response for download
                            return new StreamedResponse(function () use ($storage, $path) {
                                $stream = $storage->readStream($path);
                                if ($stream === false) {
                                    abort(500, 'Could not read file');
                                }
                                fpassthru($stream);
                                if (is_resource($stream)) {
                                    fclose($stream);
                                }
                            }, 200, [
                                'Content-Type' => $storage->mimeType($path) ?: 'application/octet-stream',
                                'Content-Disposition' => 'attachment; filename="' . $media->file_name . '"',
                                'Content-Length' => $storage->size($path),
                            ]);
                        }
                    }
                    
                    abort(404);
                })->name('media.download');
            });
    }
}

