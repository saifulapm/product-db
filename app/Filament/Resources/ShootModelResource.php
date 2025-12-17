<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShootModelResource\Pages;
use App\Filament\Resources\ShootModelResource\RelationManagers;
use App\Models\ShootModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ShootModelResource extends Resource
{
    protected static ?string $model = ShootModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationLabel = 'Models';

    protected static ?string $navigationGroup = 'Creative';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Personal Information')
                    ->schema([
                        Forms\Components\DatePicker::make('submission_date')
                            ->label('Submission Date')
                            ->displayFormat('Y-m-d')
                            ->default(now())
                            ->native(false),
                        Forms\Components\TextInput::make('first_name')
                            ->label('First Name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('last_name')
                            ->label('Last Name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone_number')
                            ->label('Phone Number')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('social_media')
                            ->label('Social Media')
                            ->rows(2)
                            ->maxLength(500),
                        Forms\Components\TextInput::make('height')
                            ->label('Height')
                            ->maxLength(50),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Preferences & Details')
                    ->schema([
                        Forms\Components\Textarea::make('coffee_order')
                            ->label('Coffee Order')
                            ->rows(2)
                            ->maxLength(500),
                        Forms\Components\Textarea::make('food_allergies')
                            ->label('Food Allergies')
                            ->rows(2)
                            ->maxLength(500),
                        Forms\Components\CheckboxList::make('tops_size')
                            ->label('Tops Size (you can select multiple sizes)')
                            ->options([
                                'XSmall' => 'XSmall',
                                'Small' => 'Small',
                                'Medium' => 'Medium',
                                'Large' => 'Large',
                                'XLarge' => 'XLarge',
                                '2XLarge' => '2XLarge',
                            ])
                            ->columns(2)
                            ->descriptions([
                                'XSmall' => '',
                                'Small' => '',
                                'Medium' => '',
                                'Large' => '',
                                'XLarge' => '',
                                '2XLarge' => '',
                            ])
                            ->searchable(false),
                        Forms\Components\CheckboxList::make('bottoms_size')
                            ->label('Bottoms Size (you can select multiple sizes)')
                            ->options([
                                'XSmall' => 'XSmall',
                                'Small' => 'Small',
                                'Medium' => 'Medium',
                                'Large' => 'Large',
                                'XLarge' => 'XLarge',
                                '2XLarge' => '2XLarge',
                            ])
                            ->columns(2)
                            ->searchable(false),
                        Forms\Components\CheckboxList::make('availability')
                            ->label('Availability')
                            ->required()
                            ->options([
                                'Monday Mornings (7am - 12pm)' => 'Monday Mornings (7am - 12pm)',
                                'Monday Afternoons (12pm - 5pm)' => 'Monday Afternoons (12pm - 5pm)',
                                'Monday Evenings (5pm - 10pm)' => 'Monday Evenings (5pm - 10pm)',
                                'Tuesday Mornings (7am - 12pm)' => 'Tuesday Mornings (7am - 12pm)',
                                'Tuesday Afternoons (12pm - 5pm)' => 'Tuesday Afternoons (12pm - 5pm)',
                                'Tuesday Evenings (5pm - 10pm)' => 'Tuesday Evenings (5pm - 10pm)',
                                'Wednesday Mornings (7am - 12pm)' => 'Wednesday Mornings (7am - 12pm)',
                                'Wednesday Afternoons (12pm - 5pm)' => 'Wednesday Afternoons (12pm - 5pm)',
                                'Wednesday Evenings (5pm - 10pm)' => 'Wednesday Evenings (5pm - 10pm)',
                                'Thursday Mornings (7am - 12pm)' => 'Thursday Mornings (7am - 12pm)',
                                'Thursday Afternoons (12pm - 5pm)' => 'Thursday Afternoons (12pm - 5pm)',
                                'Thursday Evenings (5pm - 10pm)' => 'Thursday Evenings (5pm - 10pm)',
                                'Friday Mornings (7am - 12pm)' => 'Friday Mornings (7am - 12pm)',
                                'Friday Afternoons (12pm - 5pm)' => 'Friday Afternoons (12pm - 5pm)',
                                'Friday Evenings (5pm - 10pm)' => 'Friday Evenings (5pm - 10pm)',
                                'Saturday Mornings (7am - 12pm)' => 'Saturday Mornings (7am - 12pm)',
                                'Saturday Afternoons (12pm - 5pm)' => 'Saturday Afternoons (12pm - 5pm)',
                                'Saturday Evenings (5pm - 10pm)' => 'Saturday Evenings (5pm - 10pm)',
                                'Sunday Mornings (7am - 12pm)' => 'Sunday Mornings (7am - 12pm)',
                                'Sunday Afternoons (12pm - 5pm)' => 'Sunday Afternoons (12pm - 5pm)',
                                'Sunday Evenings (5pm - 10pm)' => 'Sunday Evenings (5pm - 10pm)',
                            ])
                            ->columns(3)
                            ->gridDirection('row')
                            ->searchable(false)
                            ->helperText('Select all time slots when you are available (at least one required)')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Media')
                    ->schema([
                        Forms\Components\TextInput::make('selfie_url')
                            ->label('Selfie Image URL')
                            ->url()
                            ->maxLength(500)
                            ->placeholder('https://...')
                            ->helperText('Enter the URL to the model\'s selfie image')
                            ->columnSpanFull(),
                        Forms\Components\Placeholder::make('selfie_preview')
                            ->label('Selfie Preview')
                            ->content(fn ($record) => $record?->selfie_url 
                                ? new \Illuminate\Support\HtmlString('<img src="' . htmlspecialchars($record->selfie_url) . '" style="max-width: 300px; max-height: 300px; border-radius: 8px;" class="mt-2" />')
                                : 'No image available')
                            ->visible(fn ($record) => !empty($record?->selfie_url)),
                    ]),
                Forms\Components\Section::make('Google Sheets Data')
                    ->schema([
                        Forms\Components\Placeholder::make('google_sheets_timestamp')
                            ->label('Form Submitted')
                            ->content(fn ($record) => $record?->google_sheets_timestamp 
                                ? $record->google_sheets_timestamp->format('Y-m-d H:i:s') 
                                : 'N/A'),
                        Forms\Components\KeyValue::make('google_sheets_data')
                            ->label('Raw Form Response Data')
                            ->disabled()
                            ->visible(fn ($record) => !empty($record?->google_sheets_data)),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn ($record) => !empty($record?->google_sheets_data)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('selfie_url')
                    ->label('')
                    ->circular()
                    ->size(50)
                    ->defaultImageUrl('/images/placeholder-model.png')
                    ->getStateUsing(function ($record) {
                        if (!$record || !$record->selfie_url) {
                            return null;
                        }
                        // If it's already a full URL, return it
                        if (filter_var($record->selfie_url, FILTER_VALIDATE_URL)) {
                            return $record->selfie_url;
                        }
                        // Otherwise, generate URL from path
                        return \Illuminate\Support\Facades\Storage::disk('public')->url($record->selfie_url);
                    }),
                Tables\Columns\TextColumn::make('name')
                    ->label('Model Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('height')
                    ->label('Height')
                    ->searchable()
                    ->sortable(),
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
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
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
            'index' => Pages\ListShootModels::route('/'),
            'create' => Pages\CreateShootModel::route('/create'),
            'view' => Pages\ViewShootModel::route('/{record}'),
            'edit' => Pages\EditShootModel::route('/{record}/edit'),
        ];
    }
}
