<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\TableWidget;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Notifications\Notification;
use Barryvdh\DomPDF\Facade\Pdf;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class QuickCadBuilderWidget extends TableWidget
{
    protected static ?int $sort = 50;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->whereNotNull('cad_download')
                    ->orWhereHas('media', function ($query) {
                        $query->where('collection_name', 'cad_download');
                    })
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->product_type)
                    ->color('primary')
                    ->url(fn ($record) => route('filament.admin.resources.products.edit', ['record' => $record->id]))
                    ->openUrlInNewTab(),
                TextColumn::make('base_color')
                    ->label('Base Color')
                    ->searchable()
                    ->sortable(),
            ])
            ->selectable()
            ->headerActions([
                \Filament\Tables\Actions\Action::make('download_pdf')
                    ->label('Download Combined PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Download Combined CAD PDF')
                    ->modalDescription('This will create a multi-page PDF with all selected CAD files. Continue?')
                    ->action(function () {
                        $records = $this->getSelectedTableRecords();
                        \Log::info('Quick CAD Builder - Header action called with ' . $records->count() . ' records');
                        \Log::info('Quick CAD Builder - Record IDs: ' . $records->pluck('id')->implode(','));
                        return $this->downloadCombinedPdf($records);
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('download_combined_pdf')
                        ->label('Download Combined PDF')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Download Combined CAD PDF')
                        ->modalDescription('This will create a multi-page PDF with all selected CAD files. Continue?')
                        ->action(function ($records) {
                            \Log::info('Quick CAD Builder - Bulk action called with ' . $records->count() . ' records');
                            \Log::info('Quick CAD Builder - Record IDs: ' . $records->pluck('id')->implode(','));
                            return $this->downloadCombinedPdf($records);
                        }),
                ]),
            ])
            ->defaultSort('name', 'asc')
            ->paginated([10, 25, 50, 100]);
    }

    protected function downloadCombinedPdf($records)
    {
        if ($records->isEmpty()) {
            Notification::make()
                ->title('No Products Selected')
                ->body('Please select products to download.')
                ->warning()
                ->send();
            return null;
        }

        try {
            // Convert to array of IDs to ensure we're working with fresh data
            $productIds = $records->pluck('id')->toArray();
            \Log::info('Quick CAD Builder - Selected Product IDs: ' . json_encode($productIds));
            
            // Fetch fresh products from database and maintain selection order
            $products = Product::whereIn('id', $productIds)->get()->sortBy(function ($product) use ($productIds) {
                return array_search($product->id, $productIds);
            })->values();
            
            \Log::info('Quick CAD Builder - Fetched ' . $products->count() . ' products from database');
            
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            $mergedPdf = new Fpdi();
            $totalPagesAdded = 0;
            
            // Merge all CAD PDFs
            foreach ($products as $product) {
                \Log::info('Quick CAD Builder - Processing product: ' . $product->id . ' - ' . $product->name);
                $cadPath = $this->getCadFilePath($product, $tempDir);
                \Log::info('Quick CAD Builder - Product ' . $product->id . ' CAD path: ' . ($cadPath ?? 'null'));
                if ($cadPath && file_exists($cadPath)) {
                    try {
                        // Check if file is an image
                        $mimeType = mime_content_type($cadPath);
                        if (str_starts_with($mimeType, 'image/')) {
                            // Convert image to PDF
                            $cadPath = $this->convertImageToPdf($cadPath, $product, $tempDir);
                            if (!$cadPath) {
                                continue;
                            }
                        }
                        
                        $pageCount = $mergedPdf->setSourceFile($cadPath);
                        \Log::info('Quick CAD Builder - Product ' . $product->id . ' has ' . $pageCount . ' pages');
                        $totalPagesAdded += $pageCount;
                        
                        for ($i = 1; $i <= $pageCount; $i++) {
                            $tplId = $mergedPdf->importPage($i);
                            $size = $mergedPdf->getTemplateSize($tplId);
                            
                            $mergedPdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                            $mergedPdf->useTemplate($tplId);
                        }
                        
                        // Clean up downloaded CAD file if it's in temp directory
                        if (str_starts_with($cadPath, $tempDir)) {
                            @unlink($cadPath);
                        }
                    } catch (\Exception $e) {
                        \Log::error('Quick CAD Builder - Error processing product ' . $product->id . ': ' . $e->getMessage());
                        continue;
                    }
                } else {
                    \Log::warning('Quick CAD Builder - Product ' . $product->id . ' has no valid CAD path or file not found');
                }
            }
            
            \Log::info('Quick CAD Builder: ' . $products->count() . ' products processed, ' . $totalPagesAdded . ' total pages added');
            
            $filename = 'quick-cad-' . date('Y-m-d-His') . Str::random(4) . '.pdf';
            
            return response()->streamDownload(function () use ($mergedPdf) {
                echo $mergedPdf->Output('', 'S');
            }, $filename, ['Content-Type' => 'application/pdf']);
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Could not generate PDF: ' . $e->getMessage())
                ->danger()
                ->send();
            return null;
        }
    }
    
    private function getCadFilePath($product, $tempDir): ?string
    {
        try {
            $mediaFiles = $product->getMedia('cad_download');
            if ($mediaFiles->isNotEmpty()) {
                $path = $mediaFiles->first()->getPath();
                if (file_exists($path)) {
                    return $path;
                }
            }
            
            $cadUrl = $product->cad_download;
            if (!$cadUrl) {
                return null;
            }
            
            if (filter_var($cadUrl, FILTER_VALIDATE_URL)) {
                $response = Http::timeout(30)->get($cadUrl);
                if ($response->successful()) {
                    $extension = pathinfo(parse_url($cadUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'pdf';
                    $tempPath = $tempDir . '/cad-' . Str::random(10) . '.' . $extension;
                    file_put_contents($tempPath, $response->body());
                    return $tempPath;
                }
            } else {
                $paths = [
                    storage_path('app/public/' . $cadUrl),
                    public_path($cadUrl),
                    str_starts_with($cadUrl, '/') ? $cadUrl : null,
                ];
                
                foreach ($paths as $path) {
                    if ($path && file_exists($path)) {
                        return $path;
                    }
                }
            }
        } catch (\Exception $e) {
        }
        
        return null;
    }
    
    private function convertImageToPdf(string $imagePath, Product $product, string $tempDir): ?string
    {
        try {
            $pdf = Pdf::loadView('filament.widgets.image-to-pdf', [
                'imagePath' => $imagePath,
                'productName' => $product->name,
            ])
                ->setPaper('letter', 'portrait')
                ->setOption('isRemoteEnabled', true);
            
            $pdfPath = $tempDir . '/image-' . $product->id . '-' . Str::random(10) . '.pdf';
            file_put_contents($pdfPath, $pdf->output());
            
            return $pdfPath;
        } catch (\Exception $e) {
            \Log::error('Quick CAD Builder - Error converting image to PDF for product ' . $product->id . ': ' . $e->getMessage());
            return null;
        }
    }
}

