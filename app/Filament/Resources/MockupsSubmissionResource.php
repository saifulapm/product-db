<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MockupsSubmissionResource\Pages;
use App\Models\MockupsSubmission;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MockupsSubmissionResource extends Resource
{
    protected static ?string $model = MockupsSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'Submissions';

    protected static ?string $navigationGroup = 'Mockups';

    protected static ?int $navigationSort = 1;

    public static function getSlug(): string
    {
        return 'mockups';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Submission Details')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Submission Title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter submission title'),
                        Forms\Components\DatePicker::make('submission_date')
                            ->label('Submission Date')
                            ->default(now())
                            ->displayFormat('M d, Y')
                            ->native(false),
                        Forms\Components\Select::make('assigned_to')
                            ->label('Assigned To')
                            ->relationship('assignedUser', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Select a user'),
                        Forms\Components\Select::make('priority')
                            ->label('Priority')
                            ->options([
                                1 => 'Low',
                                2 => 'Medium',
                                3 => 'High',
                                4 => 'Urgent',
                            ])
                            ->placeholder('Select priority (optional)'),
                    ])
                    ->columns(4),
                Forms\Components\Section::make('Customer Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('customer_name')
                                    ->label('Customer Name')
                                    ->maxLength(255)
                                    ->placeholder('Enter customer name')
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('customer_email')
                                    ->label('Email')
                                    ->email()
                                    ->maxLength(255)
                                    ->placeholder('customer@example.com')
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('customer_phone')
                                    ->label('Phone')
                                    ->tel()
                                    ->maxLength(255)
                                    ->placeholder('(555) 123-4567')
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('website')
                                    ->label('Website')
                                    ->maxLength(255)
                                    ->placeholder('https://example.com or www.example.com')
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('company_name')
                                    ->label('Company Name')
                                    ->maxLength(255)
                                    ->placeholder('Enter company name')
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('instagram')
                                    ->label('Instagram')
                                    ->maxLength(255)
                                    ->placeholder('@username')
                                    ->columnSpan(1),
                            ]),
                        Forms\Components\Textarea::make('notes')
                            ->label('Branding & Design Notes')
                            ->rows(6)
                            ->placeholder('Enter branding requirements, design notes, logo placement instructions, etc.')
                            ->columnSpanFull(),
                    ])
                    ->columns(12),
                Forms\Components\Section::make('Products')
                    ->schema([
                        FileUpload::make('combined_mockups_pdf')
                            ->label('Upload Combined Mockups PDF')
                            ->helperText('Upload a single PDF with all product mockups. Each page will be automatically assigned to products in order (Page 1 → Product 1, Page 2 → Product 2, etc.). This will overwrite individual product PDFs.')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(10240) // 10MB
                            ->directory('mockups/combined')
                            ->disk('public')
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->columnSpanFull()
                            ->visible(fn ($record) => !empty($record) && !empty($record->products) && count($record->products) > 0),
                        Forms\Components\Repeater::make('products')
                            ->label('Products')
                            ->schema([
                                Forms\Components\TextInput::make('product_name')
                                    ->label('Product Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., The Midweight Unisex Crewneck')
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('style')
                                    ->label('Style/Variant')
                                    ->maxLength(255)
                                    ->placeholder('e.g., Washed Maroon')
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('color')
                                    ->label('Color')
                                    ->maxLength(255)
                                    ->placeholder('e.g., Maroon, White, Ivory')
                                    ->columnSpan(2),
                                FileUpload::make('front_pdf')
                                    ->label('Front PDF')
                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                    ->maxSize(10240) // 10MB
                                    ->directory('mockups/products/front')
                                    ->disk('public')
                                    ->downloadable()
                                    ->openable()
                                    ->previewable()
                                    ->helperText('Upload front view of product')
                                    ->columnSpan(3),
                                FileUpload::make('back_pdf')
                                    ->label('Back PDF')
                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                    ->maxSize(10240) // 10MB
                                    ->directory('mockups/products/back')
                                    ->disk('public')
                                    ->downloadable()
                                    ->openable()
                                    ->previewable()
                                    ->helperText('Upload back view of product (optional)')
                                    ->columnSpan(3),
                            ])
                            ->columns(6)
                            ->defaultItems(0)
                            ->addActionLabel('Add Product')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => ($state['product_name'] ?? 'New Product') . ($state['style'] ? ' - ' . $state['style'] : ''))
                            ->reorderable(true)
                            ->columnSpanFull(),
                        FileUpload::make('graphics')
                            ->label('Graphics & Logo Files')
                            ->helperText('Upload customer-provided graphics, logos, and design files')
                            ->multiple()
                            ->acceptedFileTypes(['image/*', 'application/pdf', 'application/illustrator', 'application/postscript'])
                            ->maxSize(10240) // 10MB
                            ->directory('mockups/graphics')
                            ->disk('public')
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->columnSpanFull(),
                    ])
                    ->columns(12),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tracking_number')
                    ->label('Submission #')
                    ->formatStateUsing(fn ($state) => $state ? '#' . $state : 'N/A')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),
                Tables\Columns\TextColumn::make('submission_date')
                    ->label('Submission Date')
                    ->date('M d, Y')
                    ->sortable()
                    ->searchable(false),
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('is_completed')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => $state ? 'Closed' : 'Open')
                    ->badge()
                    ->color(fn ($state) => $state ? 'danger' : 'success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('product_status_counts')
                    ->label('Product Status')
                    ->formatStateUsing(function ($record) {
                        $counts = $record->getProductStatusCounts();
                        $parts = [];
                        
                        foreach ($counts as $status => $count) {
                            if ($count > 0) {
                                $parts[] = "{$status}: {$count}";
                            }
                        }
                        
                        return !empty($parts) ? implode(', ', $parts) : 'No products';
                    })
                    ->searchable(false)
                    ->sortable(false)
                    ->wrap()
                    ->size('sm')
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('assignedUser')
                    ->label('Assigned To')
                    ->relationship('assignedUser', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_completed')
                    ->label('Status')
                    ->placeholder('All submissions')
                    ->trueLabel('Completed only')
                    ->falseLabel('Incomplete only'),
            ])
            ->actions([
                // Actions removed - starting fresh
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_as_open')
                        ->label('Mark as Open')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each(function ($record) {
                                $record->update(['is_completed' => false]);
                            });
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Status Updated')
                                ->success()
                                ->body('Marked ' . $records->count() . ' submission(s) as Open')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('mark_as_closed')
                        ->label('Mark as Closed')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $closedCount = 0;
                            $skippedCount = 0;
                            
                            $records->each(function ($record) use (&$closedCount, &$skippedCount) {
                                // Only close if all products are Approved or Removed
                                if ($record->allProductsCompleted()) {
                                    $record->update(['is_completed' => true, 'completed_at' => now()]);
                                    $closedCount++;
                                } else {
                                    $skippedCount++;
                                }
                            });
                            
                            if ($closedCount > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Status Updated')
                                    ->success()
                                    ->body('Marked ' . $closedCount . ' submission(s) as Closed')
                                    ->send();
                            }
                            
                            if ($skippedCount > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Some Submissions Skipped')
                                    ->warning()
                                    ->body($skippedCount . ' submission(s) could not be closed because not all products are Approved or Removed')
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMockupsSubmissions::route('/'),
            'create' => Pages\CreateMockupsSubmission::route('/create'),
            'view' => Pages\ViewMockupsSubmission::route('/{record}'),
            'edit' => Pages\EditMockupsSubmission::route('/{record}/edit'),
        ];
    }
}
