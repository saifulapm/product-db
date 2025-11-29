<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoginInfoResource\Pages;
use App\Models\LoginInfo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LoginInfoResource extends Resource
{
    protected static ?string $model = LoginInfo::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Login Info';

    protected static ?string $navigationGroup = 'Customer Service';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('login-info.view');
    }

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('website_name')
                    ->required()
                    ->maxLength(255)
                    ->label('Website Name'),
                Forms\Components\TextInput::make('url')
                    ->url()
                    ->maxLength(255)
                    ->label('Website URL')
                    ->helperText('Enter the full website URL'),
                Forms\Components\TextInput::make('username')
                    ->maxLength(255)
                    ->label('Username/Email')
                    ->helperText('Enter login username or email'),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->maxLength(255)
                    ->label('Password')
                    ->helperText('Enter the login password'),
                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->maxLength(500)
                    ->label('Notes')
                    ->helperText('Add any additional notes or instructions'),
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
                Tables\Columns\TextColumn::make('website_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('url')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('username')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLoginInfos::route('/'),
            'create' => Pages\CreateLoginInfo::route('/create'),
            'edit' => Pages\EditLoginInfo::route('/{record}/edit'),
        ];
    }
}
