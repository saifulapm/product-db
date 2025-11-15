<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PatchResource\Pages;
use App\Models\Patch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class PatchResource extends Resource
{
    protected static ?string $model = Patch::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationLabel = 'Patches';

    protected static ?string $navigationGroup = 'Patches';

    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Patch Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Patch Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('supplier')
                            ->label('Supplier')
                            ->maxLength(100),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(4)
                            ->columnSpanFull(),
                        FileUpload::make('image_reference')
                            ->label('Reference Image')
                            ->helperText('Upload a reference image for this patch')
                            ->acceptedFileTypes(['image/*'])
                            ->maxSize(10240) // 10MB
                            ->directory('patches/reference-images')
                            ->disk('public')
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->imagePreviewHeight('200')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('minimums')
                            ->label('Minimums')
                            ->default('10pcs')
                            ->maxLength(255)
                            ->placeholder('e.g., 10pcs, 12 pieces, 24 pieces')
                            ->helperText('Enter the minimum order quantity for this patch'),
                        Forms\Components\TextInput::make('backing')
                            ->label('Backing Type')
                            ->default('Iron On')
                            ->maxLength(255)
                            ->placeholder('e.g., Iron On, Sew On, Velcro')
                            ->helperText('Enter the backing type for this patch'),
                        Forms\Components\TextInput::make('lead_time')
                            ->label('Lead Time')
                            ->maxLength(100)
                            ->placeholder('e.g., 2-3 weeks, 4-6 weeks'),
                        Forms\Components\TagsInput::make('colors')
                            ->label('Available Colors')
                            ->placeholder('Add color names or hex codes')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_reference')
                    ->label('Image')
                    ->disk('public')
                    ->defaultImageUrl('/images/placeholder-product.png')
                    ->circular()
                    ->size(50),
                Tables\Columns\TextColumn::make('name')
                    ->label('Patch Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('minimums')
                    ->label('Minimums')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('backing')
                    ->label('Backing')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('lead_time')
                    ->label('Lead Time')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPatches::route('/'),
            'create' => Pages\CreatePatch::route('/create'),
            'edit' => Pages\EditPatch::route('/{record}/edit'),
        ];
    }
}
