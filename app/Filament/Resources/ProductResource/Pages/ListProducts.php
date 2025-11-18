<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductResource\Widgets\ProductsHeader;
use App\Models\TeamNote;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Response;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];

        // Add team notes edit action
        $teamNote = TeamNote::firstOrCreate(['page' => 'products'], ['content' => '']);
        
        $actions[] = Action::make('edit_team_notes')
            ->label('Edit Team Notes')
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->form([
                RichEditor::make('content')
                    ->label('Team Notes')
                    ->placeholder('Enter your notes here. You can use HTML tags like <h3>Heading</h3> and <br> for line breaks.')
                    ->helperText('You can use HTML tags like <h3>, <h2>, <br>, <p>, <strong>, <em>, etc.')
                    ->toolbarButtons([
                        'attachFiles',
                        'blockquote',
                        'bold',
                        'bulletList',
                        'codeBlock',
                        'h2',
                        'h3',
                        'italic',
                        'link',
                        'orderedList',
                        'redo',
                        'strike',
                        'underline',
                        'undo',
                    ])
                    ->default(mb_convert_encoding($teamNote->content ?: '', 'UTF-8', 'UTF-8')),
            ])
            ->action(function (array $data): void {
                // Clean and ensure UTF-8 encoding
                $content = $data['content'] ?? '';
                
                // Strip invalid UTF-8 characters
                $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
                $content = iconv('UTF-8', 'UTF-8//IGNORE', $content);
                
                $teamNote = TeamNote::firstOrNew(['page' => 'products']);
                $teamNote->content = $content;
                $teamNote->save();

                Notification::make()
                    ->title('Notes updated successfully!')
                    ->success()
                    ->send();
            })
            ->requiresConfirmation(false)
            ->modalHeading('Edit Team Notes')
            ->modalSubmitActionLabel('Save');

        return $actions;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ProductsHeader::class,
        ];
    }

    public function downloadCsvTemplate()
    {
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
        
        return Response::streamDownload(function () use ($csvContent) {
            echo $csvContent;
        }, 'product_template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
