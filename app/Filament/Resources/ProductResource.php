<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Repeater;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Response;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Product Database';

    protected static ?string $modelLabel = 'Product';

    protected static ?string $pluralModelLabel = 'Product Database';

    protected static ?string $navigationGroup = 'Design Tools';

    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Product Details')
                    ->schema([
                        Grid::make(2)
            ->schema([
                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true),
                                Forms\Components\TextInput::make('website_url')
                                    ->label('Website URL')
                                    ->url()
                                    ->maxLength(500),
                            ]),
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('supplier')
                                    ->maxLength(100),
                                Forms\Components\TextInput::make('product_type')
                                    ->label('Product Type')
                                    ->maxLength(100),
                            ]),
                        Forms\Components\TagsInput::make('available_sizes')
                            ->label('Available Sizes')
                            ->placeholder('Enter sizes (e.g., S, M, L, XL)')
                            ->helperText('Enter the sizes available for this product. Press Enter or comma to add each size.')
                            ->separator(',')
                            ->suggestions(['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL', '6XL', '28', '30', '32', '34', '36', '38', '40', '42', '44', '46'])
                            ->columnSpanFull(),
                        Forms\Components\ColorPicker::make('base_color')
                            ->label('Base Color (Illustrator)')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $set('base_color_hex', $state);
                                }
                            })
                            ->helperText('Base color used in Illustrator for this product')
                            ->extraAttributes(['style' => 'border: 1px solid #ccc; border-radius: 4px;'])
                            ->columnSpanFull(),
                        Forms\Components\Hidden::make('base_color_hex')
                            ->default('#FFFFFF'),
                        Grid::make(2)
                            ->schema([
                                Forms\Components\ColorPicker::make('tone_on_tone_darker')
                                    ->label('Tone on Tone Hex Code (Darker Color)')
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $set('tone_on_tone_darker_hex', $state);
                                        }
                                    })
                                    ->helperText('Select darker tone color')
                                    ->extraAttributes(['style' => 'border: 1px solid #ccc; border-radius: 4px;']),
                                Forms\Components\Hidden::make('tone_on_tone_darker_hex')
                                    ->default('#000000'),
                                Forms\Components\ColorPicker::make('tone_on_tone_lighter')
                                    ->label('Tone on Tone Hex Code (Lighter Color)')
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $set('tone_on_tone_lighter_hex', $state);
                                        }
                                    })
                                    ->helperText('Select lighter tone color')
                                    ->extraAttributes(['style' => 'border: 1px solid #ccc; border-radius: 4px;']),
                                Forms\Components\Hidden::make('tone_on_tone_lighter_hex')
                                    ->default('#FFFFFF'),
                            ]),
                    ])
                    ->columns(2),

                Section::make('B2B Price & Minimums')
                    ->schema([
                        Forms\Components\TextInput::make('minimums')
                            ->label('Minimums')
                            ->placeholder('e.g., "No minimums" or "12 pieces"')
                            ->helperText('Minimum order quantity or requirements')
                            ->columnSpanFull(),
                        Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('printed_embroidered_1_logo')
                                    ->label('Printed / Embroidered - 1 Logo')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01)
                                    ->placeholder('0.00')
                                    ->helperText('Price for 1 logo customization'),
                                Forms\Components\TextInput::make('printed_embroidered_2_logos')
                                    ->label('Printed / Embroidered - 2 Logos')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01)
                                    ->placeholder('0.00')
                                    ->helperText('Price for 2 logos customization'),
                                Forms\Components\TextInput::make('printed_embroidered_3_logos')
                                    ->label('Printed / Embroidered - 3 Logos')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01)
                                    ->placeholder('0.00')
                                    ->helperText('Price for 3 logos customization'),
                            ]),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Section::make('CAD Download')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('cad_download')
                            ->collection('cad_download')
                            ->label('CAD Download')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->helperText('Upload a PDF or image file for the CAD reference. Note: PHP upload limits may restrict file size.')
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->imagePreviewHeight('200')
                            ->disk('public')
                            ->visibility('public')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                Section::make('Notes')
                    ->schema([
                        Forms\Components\RichEditor::make('notes')
                            ->label('Product Notes')
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
                    ->columnSpanFull(),
                    ])
                    ->collapsible(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->product_type)
                    ->url(fn ($record) => route('filament.admin.resources.products.view', $record))
                    ->color('primary'),
                Tables\Columns\TextColumn::make('base_color')
                    ->label('Base Color')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'N/A';
                        return new \Illuminate\Support\HtmlString(
                            '<div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 20px; height: 20px; background-color: ' . $state . '; border: 1px solid #ccc; border-radius: 3px;"></div>
                                <span>' . $state . '</span>
                            </div>'
                        );
                    })
                    ->html(),
                Tables\Columns\TextColumn::make('tone_on_tone_darker')
                    ->label('Tone on Tone (Darker)')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'N/A';
                        return new \Illuminate\Support\HtmlString(
                            '<div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 20px; height: 20px; background-color: ' . $state . '; border: 1px solid #ccc; border-radius: 3px;"></div>
                                <span>' . $state . '</span>
                            </div>'
                        );
                    })
                    ->html(),
                Tables\Columns\TextColumn::make('tone_on_tone_lighter')
                    ->label('Tone on Tone (Lighter)')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'N/A';
                        return new \Illuminate\Support\HtmlString(
                            '<div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 20px; height: 20px; background-color: ' . $state . '; border: 1px solid #ccc; border-radius: 3px;"></div>
                                <span>' . $state . '</span>
                            </div>'
                        );
                    })
                    ->html(),
                Tables\Columns\TextColumn::make('minimums')
                    ->label('Minimums')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ?: 'N/A'),
                Tables\Columns\TextColumn::make('printed_embroidered_1_logo')
                    ->label('1 Logo Price')
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        return $state ? '$' . number_format($state, 2) : 'N/A';
                    }),
                Tables\Columns\TextColumn::make('printed_embroidered_2_logos')
                    ->label('2 Logos Price')
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        return $state ? '$' . number_format($state, 2) : 'N/A';
                    }),
                Tables\Columns\TextColumn::make('printed_embroidered_3_logos')
                    ->label('3 Logos Price')
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        return $state ? '$' . number_format($state, 2) : 'N/A';
                    }),
                Tables\Columns\TextColumn::make('cad_download')
                    ->label('CAD Download')
                    ->html()
                    ->state(function ($record) {
                        // Check Media Library for uploaded CAD file
                        $mediaFiles = $record->getMedia('cad_download');
                        if ($mediaFiles->isNotEmpty()) {
                            $firstMedia = $mediaFiles->first();
                            $downloadUrl = route('filament.admin.media.download', $firstMedia->id);
                            $fileName = $firstMedia->file_name;
                            
                            return new \Illuminate\Support\HtmlString(
                                '<a href="' . $downloadUrl . '" target="_blank" download="' . htmlspecialchars($fileName) . '" style="color: #3b82f6; text-decoration: underline; font-weight: 500; display: inline-flex; align-items: center; gap: 4px;">
                                    <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                    Download
                                </a>'
                            );
                        }
                        
                        // No CAD file uploaded
                        return new \Illuminate\Support\HtmlString('<span style="color: #9ca3af;">-</span>');
                    }),
                Tables\Columns\TextColumn::make('fabric')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('available_sizes')
                    ->label('Sizes')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('last_inventory_sync')
                    ->label('Last Sync')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'discontinued' => 'Discontinued',
                    ]),
                SelectFilter::make('supplier')
                    ->options(fn () => Product::distinct()->pluck('supplier', 'supplier')->filter()),
                SelectFilter::make('product_type')
                    ->options(fn () => Product::distinct()->pluck('product_type', 'product_type')->filter()),
                TernaryFilter::make('is_featured')
                    ->label('Featured Products')
                    ->boolean()
                    ->trueLabel('Featured only')
                    ->falseLabel('Not featured')
                    ->native(false),
                Tables\Filters\Filter::make('low_stock')
                    ->query(fn (Builder $query): Builder => $query->where('stock_quantity', '<=', 10))
                    ->toggle(),
                Tables\Filters\Filter::make('out_of_stock')
                    ->query(fn (Builder $query): Builder => $query->where('stock_quantity', 0))
                    ->toggle(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('update_status')
                        ->label('Update Status')
                        ->icon('heroicon-o-check-circle')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('Status')
                                ->options([
                                    'active' => 'Active',
                                    'inactive' => 'Inactive',
                                    'discontinued' => 'Discontinued',
                                ])
                                ->required()
                                ->default('active'),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $records->each(function (Product $record) use ($data) {
                                $record->update(['status' => $data['status']]);
                            });
                            
                            Notification::make()
                                ->title('Status updated successfully')
                                ->success()
                                ->body('Updated ' . $records->count() . ' product(s) to ' . $data['status'])
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('update_supplier')
                        ->label('Update Supplier')
                        ->icon('heroicon-o-building-office')
                        ->form([
                            Forms\Components\TextInput::make('supplier')
                                ->label('Supplier')
                                ->maxLength(100),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $records->each(function (Product $record) use ($data) {
                                $record->update(['supplier' => $data['supplier']]);
                            });
                            
                            Notification::make()
                                ->title('Supplier updated successfully')
                                ->success()
                                ->body('Updated ' . $records->count() . ' product(s)')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('update_product_type')
                        ->label('Update Product Type')
                        ->icon('heroicon-o-tag')
                        ->form([
                            Forms\Components\TextInput::make('product_type')
                                ->label('Product Type')
                                ->maxLength(100),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $records->each(function (Product $record) use ($data) {
                                $record->update(['product_type' => $data['product_type']]);
                            });
                            
                            Notification::make()
                                ->title('Product Type updated successfully')
                                ->success()
                                ->body('Updated ' . $records->count() . ' product(s)')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('update_base_color')
                        ->label('Update Base Color')
                        ->icon('heroicon-o-paint-brush')
                        ->form([
                            Forms\Components\ColorPicker::make('base_color')
                                ->label('Base Color')
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $records->each(function (Product $record) use ($data) {
                                $record->update([
                                    'base_color' => $data['base_color'],
                                    'base_color_hex' => $data['base_color'],
                                ]);
                            });
                            
                            Notification::make()
                                ->title('Base Color updated successfully')
                                ->success()
                                ->body('Updated ' . $records->count() . ' product(s)')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('update_tone_darker')
                        ->label('Update Tone on Tone (Darker)')
                        ->icon('heroicon-o-swatch')
                        ->form([
                            Forms\Components\ColorPicker::make('tone_on_tone_darker')
                                ->label('Tone on Tone (Darker Color)')
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $records->each(function (Product $record) use ($data) {
                                $record->update([
                                    'tone_on_tone_darker' => $data['tone_on_tone_darker'],
                                    'tone_on_tone_darker_hex' => $data['tone_on_tone_darker'],
                                ]);
                            });
                            
                            Notification::make()
                                ->title('Tone on Tone (Darker) updated successfully')
                                ->success()
                                ->body('Updated ' . $records->count() . ' product(s)')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('update_tone_lighter')
                        ->label('Update Tone on Tone (Lighter)')
                        ->icon('heroicon-o-swatch')
                        ->form([
                            Forms\Components\ColorPicker::make('tone_on_tone_lighter')
                                ->label('Tone on Tone (Lighter Color)')
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $records->each(function (Product $record) use ($data) {
                                $record->update([
                                    'tone_on_tone_lighter' => $data['tone_on_tone_lighter'],
                                    'tone_on_tone_lighter_hex' => $data['tone_on_tone_lighter'],
                                ]);
                            });
                            
                            Notification::make()
                                ->title('Tone on Tone (Lighter) updated successfully')
                                ->success()
                                ->body('Updated ' . $records->count() . ' product(s)')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('update_fabric')
                        ->label('Update Fabric')
                        ->icon('heroicon-o-squares-2x2')
                        ->form([
                            Forms\Components\TextInput::make('fabric')
                                ->label('Fabric')
                                ->maxLength(255),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $records->each(function (Product $record) use ($data) {
                                $record->update(['fabric' => $data['fabric']]);
                            });
                            
                            Notification::make()
                                ->title('Fabric updated successfully')
                                ->success()
                                ->body('Updated ' . $records->count() . ' product(s)')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('update_available_sizes')
                        ->label('Update Available Sizes')
                        ->icon('heroicon-o-list-bullet')
                        ->form([
                            Forms\Components\TextInput::make('available_sizes')
                                ->label('Available Sizes')
                                ->helperText('Enter sizes separated by commas (e.g., S, M, L, XL)')
                                ->maxLength(255),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $records->each(function (Product $record) use ($data) {
                                $record->update(['available_sizes' => $data['available_sizes']]);
                            });
                            
                            Notification::make()
                                ->title('Available Sizes updated successfully')
                                ->success()
                                ->body('Updated ' . $records->count() . ' product(s)')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('update_minimums')
                        ->label('Update Minimums')
                        ->icon('heroicon-o-currency-dollar')
                    ->form([
                            Forms\Components\TextInput::make('minimums')
                                ->label('Minimums')
                                ->placeholder('e.g., "No minimums" or "12 pieces"')
                                ->helperText('Minimum order quantity or requirements')
                                ->maxLength(255),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            if (isset($data['minimums']) && $data['minimums'] !== null && $data['minimums'] !== '') {
                                $records->each(function (Product $record) use ($data) {
                                    $record->update(['minimums' => $data['minimums']]);
                                });
                                
                                Notification::make()
                                    ->title('Minimums updated successfully')
                                    ->success()
                                    ->body('Updated ' . $records->count() . ' product(s)')
                                    ->send();
                                    } else {
                            Notification::make()
                                    ->title('No minimums value provided')
                                    ->warning()
                                    ->body('Please enter a minimums value')
                                ->send();
                        }
                    })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('update_b2b_pricing')
                        ->label('Update B2B Pricing')
                        ->icon('heroicon-o-currency-dollar')
                    ->form([
                            Forms\Components\TextInput::make('printed_embroidered_1_logo')
                                ->label('Printed / Embroidered - 1 Logo')
                                ->numeric()
                                ->prefix('$')
                                ->step(0.01)
                                ->placeholder('0.00')
                                ->helperText('Price for 1 logo customization'),
                            Forms\Components\TextInput::make('printed_embroidered_2_logos')
                                ->label('Printed / Embroidered - 2 Logos')
                                ->numeric()
                                ->prefix('$')
                                ->step(0.01)
                                ->placeholder('0.00')
                                ->helperText('Price for 2 logos customization'),
                            Forms\Components\TextInput::make('printed_embroidered_3_logos')
                                ->label('Printed / Embroidered - 3 Logos')
                                ->numeric()
                                ->prefix('$')
                                ->step(0.01)
                                ->placeholder('0.00')
                                ->helperText('Price for 3 logos customization'),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $updateData = [];
                            
                            // Only include fields that have values
                            if (isset($data['printed_embroidered_1_logo']) && $data['printed_embroidered_1_logo'] !== null && $data['printed_embroidered_1_logo'] !== '') {
                                $updateData['printed_embroidered_1_logo'] = (float) $data['printed_embroidered_1_logo'];
                            }
                            if (isset($data['printed_embroidered_2_logos']) && $data['printed_embroidered_2_logos'] !== null && $data['printed_embroidered_2_logos'] !== '') {
                                $updateData['printed_embroidered_2_logos'] = (float) $data['printed_embroidered_2_logos'];
                            }
                            if (isset($data['printed_embroidered_3_logos']) && $data['printed_embroidered_3_logos'] !== null && $data['printed_embroidered_3_logos'] !== '') {
                                $updateData['printed_embroidered_3_logos'] = (float) $data['printed_embroidered_3_logos'];
                            }
                            
                            if (!empty($updateData)) {
                                $records->each(function (Product $record) use ($updateData) {
                                    $record->update($updateData);
                                });
                            
                            Notification::make()
                                    ->title('B2B Pricing updated successfully')
                                ->success()
                                    ->body('Updated ' . $records->count() . ' product(s)')
                                ->send();
                            } else {
                            Notification::make()
                                    ->title('No pricing data provided')
                                    ->warning()
                                    ->body('Please enter at least one pricing value')
                                ->send();
                        }
                    })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
                ->actions([])
            ->headerActions([
                Tables\Actions\Action::make('quick_refresh')
                    ->label('Quick Refresh')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->action(function () {
                        try {
                            $googleSheetsService = new \App\Services\GoogleSheetsService();
                            
                            // Use your saved Google Sheet settings
                            $spreadsheetId = '1hq_9x_iKVz2gRLu1__yQUlmBBdJ0k8XkO8OW78s8DFA';
                            $sheetRange = 'A:Z';
                            
                            // Read data from Google Sheets
                            $sheetData = $googleSheetsService->readSheet($spreadsheetId, $sheetRange);
                            
                            if (empty($sheetData)) {
                                Notification::make()
                                    ->title('No data found in Google Sheet')
                                    ->warning()
                                    ->send();
                                return;
                            }
                            
                            // Parse products from sheet data
                            $products = $googleSheetsService->parseProductsFromSheet($sheetData);
                            
                            if (empty($products)) {
                                Notification::make()
                                    ->title('No valid products found in Google Sheet')
                                    ->warning()
                                    ->send();
                                return;
                            }
                            
                            // Clear existing products first (optional - remove this if you want to keep existing)
                            // Product::truncate();
                            
                            // Import products
                            $imported = 0;
                            $updated = 0;
                            $errors = [];
                            
                            foreach ($products as $index => $productData) {
                                try {
                                    // Add default values for all required fields
                                    $productData = array_merge([
                                        'status' => 'active',
                                        'stock_quantity' => 0,
                                        'min_stock_level' => 0,
                                        'is_featured' => false,
                                        'has_variants' => false,
                                        'price' => 0.00,
                                        'cost' => 0.00,
                                    ], $productData);
                                    
                                    // Remove only null values, keep empty strings and valid data
                                    $productData = array_filter($productData, function($value) {
                                        return $value !== null;
                                    });
                                    
                                    if (isset($productData['name']) && $productData['name']) {
                                        // Generate consistent Ethos ID for matching (same Ethos ID for same product)
                                        $ethosId = $productData['sku'] ?? $this->generateConsistentEthosId($productData['name'], $productData['supplier'] ?? '');
                                        $productData['sku'] = $ethosId;
                                        
                                        // Check if product already exists by Ethos ID (most reliable)
                                        $existingProduct = Product::where('sku', $ethosId)->first();
                                        
                                        if ($existingProduct) {
                                            // Update existing product with new data
                                            $existingProduct->update($productData);
                                            $updated++;
                                        } else {
                                            // Create new product
                                            Product::create($productData);
                                            $imported++;
                                        }
                                    }
                                } catch (\Exception $e) {
                                    $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                                }
                            }
                            
                            $message = "Refresh complete! Imported {$imported} new products, updated {$updated} existing products.";
                            if (!empty($errors)) {
                                $message .= " Errors: " . implode(', ', array_slice($errors, 0, 3));
                                if (count($errors) > 3) {
                                    $message .= " and " . (count($errors) - 3) . " more errors.";
                                }
                            }
                            
                            Notification::make()
                                ->title($message)
                                ->success()
                                ->send();
                                
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error refreshing from Google Sheets: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Refresh Product Data')
                    ->modalDescription('This will sync the latest data from your Google Sheet. Existing products will be updated, new products will be added.')
                    ->modalSubmitActionLabel('Refresh Now'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    private function findBestMatch(array $headers, array $possibleNames): ?string
    {
        foreach ($possibleNames as $name) {
            foreach ($headers as $header) {
                if (strtolower($header) === strtolower($name)) {
                    return $header;
                }
            }
        }
        
        // Try partial matches
        foreach ($possibleNames as $name) {
            foreach ($headers as $header) {
                if (strpos(strtolower($header), strtolower($name)) !== false) {
                    return $header;
                }
            }
        }
        
        return null;
    }

    private function generateConsistentEthosId(string $productName, string $supplier = ''): string
    {
        // Create a consistent Ethos ID based on product name and supplier
        // This ensures the same product always gets the same Ethos ID
        $hash = md5($productName . $supplier);
        $numericHash = hexdec(substr($hash, 0, 8));
        
        // Convert to 10-digit format starting from 1
        $ethosNumber = ($numericHash % 9999999999) + 1;
        
        return 'EiD' . str_pad($ethosNumber, 10, '0', STR_PAD_LEFT);
    }

    private function getNextEthosId(): string
    {
        // Get the highest existing Ethos ID number
        $lastProduct = Product::where('sku', 'like', 'EiD%')
            ->orderByRaw('CAST(SUBSTRING(sku, 4) AS UNSIGNED) DESC')
            ->first();
        
        if ($lastProduct) {
            $lastNumber = (int) substr($lastProduct->sku, 3);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return 'EiD' . str_pad($nextNumber, 10, '0', STR_PAD_LEFT);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        // Use a more efficient count query instead of loading all models
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        // Use a more efficient count query
        $lowStockCount = static::getModel()::whereRaw('stock_quantity <= min_stock_level')->count();
        return $lowStockCount > 0 ? 'gray' : null;
    }
}
