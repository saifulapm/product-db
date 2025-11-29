<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DstFileResource\Pages;
use App\Models\DstFile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DstFileResource extends Resource
{
    protected static ?string $model = DstFile::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder-open';

    protected static ?string $navigationLabel = 'DST Files';

    protected static ?string $navigationGroup = 'Embroidery';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('dst-files.view');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('file_name')
                    ->required()
                    ->maxLength(255)
                    ->label('File Name'),
                Forms\Components\TextInput::make('file_path')
                    ->maxLength(255)
                    ->label('File Path'),
                Forms\Components\TextInput::make('design_name')
                    ->maxLength(255)
                    ->label('Design Name'),
                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->maxLength(1000)
                    ->label('Description'),
                Forms\Components\Select::make('file_type')
                    ->options([
                        'DST' => 'DST',
                        'EXP' => 'EXP',
                        'PES' => 'PES',
                        'PEC' => 'PEC',
                        'HUS' => 'HUS',
                        'VP3' => 'VP3',
                        'VIP' => 'VIP',
                        'JEF' => 'JEF',
                        'ART' => 'ART',
                        'OTHER' => 'Other',
                    ])
                    ->label('File Type'),
                Forms\Components\TextInput::make('stitch_count')
                    ->numeric()
                    ->label('Stitch Count'),
                Forms\Components\TextInput::make('thread_colors_needed')
                    ->maxLength(255)
                    ->label('Thread Colors Needed'),
                Forms\Components\TextInput::make('size_dimensions')
                    ->maxLength(255)
                    ->label('Size/Dimensions'),
                Forms\Components\Textarea::make('usage_instructions')
                    ->rows(3)
                    ->maxLength(500)
                    ->label('Usage Instructions'),
                Forms\Components\Textarea::make('application_notes')
                    ->rows(3)
                    ->maxLength(500)
                    ->label('Application Notes'),
                Forms\Components\TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->label('Sort Order'),
                Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->label('Active'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('file_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('design_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('file_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('stitch_count'),
                Tables\Columns\TextColumn::make('thread_colors_needed')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('size_dimensions'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
                Tables\Filters\SelectFilter::make('file_type')
                    ->options([
                        'DST' => 'DST',
                        'EXP' => 'EXP',
                        'PES' => 'PES',
                        'PEC' => 'PEC',
                        'HUS' => 'HUS',
                        'VP3' => 'VP3',
                        'VIP' => 'VIP',
                        'JEF' => 'JEF',
                        'ART' => 'ART',
                        'OTHER' => 'Other',
                    ])
                    ->label('File Type'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
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
            'index' => Pages\ManageDstFiles::route('/'),
            'create' => Pages\CreateDstFile::route('/create'),
            'edit' => Pages\EditDstFile::route('/{record}/edit'),
        ];
    }
}

