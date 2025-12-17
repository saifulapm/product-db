<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PhotoshootResource\Pages;
use App\Filament\Resources\PhotoshootResource\RelationManagers;
use App\Models\Photoshoot;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PhotoshootResource extends Resource
{
    protected static ?string $model = Photoshoot::class;

    protected static ?string $navigationIcon = 'heroicon-o-camera';

    protected static ?string $navigationLabel = 'Photoshoots';

    protected static ?string $navigationGroup = 'Creative';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Shoot Details')
                    ->schema([
                        Forms\Components\TextInput::make('shoot_name')
                            ->label('Shoot Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('shoot_type')
                            ->label('Shoot Type')
                            ->required()
                            ->options([
                                'Campaign' => 'Campaign',
                                'Studio Spotlight' => 'Studio Spotlight',
                                'Behind The Scenes' => 'Behind The Scenes',
                            ])
                            ->live()
                            ->placeholder('Select shoot type'),
                        Forms\Components\DatePicker::make('date')
                            ->label('Date')
                            ->required()
                            ->default(now()->addMonths(3))
                            ->native(false),
                        Forms\Components\Select::make('photographer')
                            ->label('Photographer')
                            ->relationship('photographerUser', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Select photographer'),
                    ])
                    ->columns(2),
                
                // Mood Board Section
                Forms\Components\Section::make('Mood Board')
                    ->schema([
                        Forms\Components\TextInput::make('mood_board_url')
                            ->label('Mood Board URL')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://www.pinterest.com/username/board-name/')
                            ->helperText('Paste your Pinterest board URL here')
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\View::make('filament.resources.photoshoot-resource.components.pinterest-board-preview')
                            ->viewData(function ($get) {
                                return [
                                    'url' => $get('mood_board_url'),
                                ];
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->extraAttributes([
                        'style' => 'padding-left: 0; padding-right: 0;',
                    ]),
                
                // Campaign Location Section
                Forms\Components\Section::make('Location')
                    ->schema([
                        Forms\Components\TextInput::make('campaign_location')
                            ->label('Location')
                            ->maxLength(255)
                            ->placeholder('Shoot location')
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\View::make('filament.resources.photoshoot-resource.components.location-preview')
                            ->viewData(function ($get) {
                                return ['location' => $get('campaign_location')];
                            })
                            ->visible(fn ($get) => !empty($get('campaign_location')))
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($get) => $get('shoot_type') === 'Campaign')
                    ->columns(1),
                
                // Campaign Models Section
                Forms\Components\Section::make('Models')
                    ->schema([
                        Forms\Components\Repeater::make('campaign_models')
                            ->label('Models')
                            ->itemLabel(function (array $state) {
                                if (empty($state['model_id'])) {
                                    return 'New Model';
                                }
                                $model = \App\Models\ShootModel::find($state['model_id']);
                                if (!$model) {
                                    return 'New Model';
                                }
                                
                                $selfieUrl = $model->selfie_url ?? null;
                                if ($selfieUrl) {
                                    if (!filter_var($selfieUrl, FILTER_VALIDATE_URL)) {
                                        $selfieUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($selfieUrl);
                                    }
                                    // Ensure absolute URL
                                    if (!str_starts_with($selfieUrl, 'http')) {
                                        $selfieUrl = url($selfieUrl);
                                    }
                                } else {
                                    $selfieUrl = url('/images/placeholder-model.png');
                                }
                                
                                return new \Illuminate\Support\HtmlString(
                                    "<div style='display: flex; align-items: center; gap: 10px;'>
                                        <img src='" . htmlspecialchars($selfieUrl) . "' alt='" . htmlspecialchars($model->name) . "' style='width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 2px solid #e5e7eb; flex-shrink: 0; background: #f3f4f6;' onerror=\"this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';\">
                                        <div style='display: none; width: 32px; height: 32px; border-radius: 50%; border: 2px solid #e5e7eb; background: #f3f4f6; flex-shrink: 0;'></div>
                                        <span style='font-weight: 600;'>" . htmlspecialchars($model->name) . "</span>
                                    </div>"
                                );
                            })
                            ->collapsible()
                            ->schema([
                                Forms\Components\Select::make('model_id')
                                    ->label('Model')
                                    ->options(function () {
                                        return \App\Models\ShootModel::orderBy('name')->get()->mapWithKeys(function ($model) {
                                            $selfieUrl = $model->selfie_url ?? '/images/placeholder-model.png';
                                            if ($selfieUrl && !filter_var($selfieUrl, FILTER_VALIDATE_URL)) {
                                                $selfieUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($selfieUrl);
                                            }
                                            
                                            $optionLabel = "
                                                <div style='display: flex; align-items: center; gap: 12px; padding: 4px 0;'>
                                                    <img src='{$selfieUrl}' alt='{$model->name}' style='width: 40px; height: 40px; border-radius: 8px; object-fit: cover; border: 2px solid #e5e7eb; flex-shrink: 0;' onerror=\"this.src='/images/placeholder-model.png'; this.onerror=null;\">
                                                    <div style='flex: 1; min-width: 0;'>
                                                        <div style='font-weight: 600; color: #111827;'>{$model->name}</div>
                                                        " . ($model->height ? "<div style='font-size: 0.875rem; color: #6b7280;'>Height: {$model->height}</div>" : "") . "
                                                    </div>
                                                </div>
                                            ";
                                            return [$model->id => $optionLabel];
                                        })->toArray();
                                    })
                                    ->getSearchResultsUsing(function (string $search) {
                                        return \App\Models\ShootModel::query()
                                            ->where('name', 'like', "%{$search}%")
                                            ->limit(50)
                                            ->get()
                                            ->mapWithKeys(function ($model) {
                                                $selfieUrl = $model->selfie_url ?? '/images/placeholder-model.png';
                                                if ($selfieUrl && !filter_var($selfieUrl, FILTER_VALIDATE_URL)) {
                                                    $selfieUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($selfieUrl);
                                                }
                                                
                                                $optionLabel = "
                                                    <div style='display: flex; align-items: center; gap: 12px; padding: 4px 0;'>
                                                        <img src='{$selfieUrl}' alt='{$model->name}' style='width: 40px; height: 40px; border-radius: 8px; object-fit: cover; border: 2px solid #e5e7eb; flex-shrink: 0;' onerror=\"this.src='/images/placeholder-model.png'; this.onerror=null;\">
                                                        <div style='flex: 1; min-width: 0;'>
                                                            <div style='font-weight: 600; color: #111827;'>{$model->name}</div>
                                                            " . ($model->height ? "<div style='font-size: 0.875rem; color: #6b7280;'>Height: {$model->height}</div>" : "") . "
                                                        </div>
                                                    </div>
                                                ";
                                                return [$model->id => $optionLabel];
                                            })->toArray();
                                    })
                                    ->getOptionLabelUsing(function ($value) {
                                        $model = \App\Models\ShootModel::find($value);
                                        return $model ? $model->name : '';
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->placeholder('Select a model')
                                    ->allowHtml(),
                                Forms\Components\View::make('filament.resources.photoshoot-resource.components.model-preview')
                                    ->viewData(function ($get) {
                                        $modelId = $get('model_id');
                                        if (!$modelId) {
                                            return ['model' => null];
                                        }
                                        $model = \App\Models\ShootModel::find($modelId);
                                        return ['model' => $model];
                                    })
                                    ->visible(fn ($get) => !empty($get('model_id')))
                                    ->columnSpanFull(),
                                Forms\Components\Repeater::make('outfits')
                                    ->label('Outfits')
                                    ->schema([
                                        Forms\Components\TextInput::make('outfit_name')
                                            ->label('Outfit Name')
                                            ->maxLength(255)
                                            ->placeholder('e.g., Look 1, Casual Outfit, etc.'),
                                        Forms\Components\Repeater::make('outfit_images')
                                            ->label('Outfit Images')
                                            ->schema([
                                                Forms\Components\TextInput::make('url')
                                                    ->label('Image URL')
                                                    ->url()
                                                    ->required()
                                                    ->maxLength(500)
                                                    ->placeholder('https://...')
                                                    ->live()
                                                    ->columnSpanFull(),
                                                Forms\Components\Placeholder::make('image_preview')
                                                    ->label('Preview')
                                                    ->content(function ($get) {
                                                        $url = $get('url');
                                                        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
                                                            return new \Illuminate\Support\HtmlString('<p class="text-gray-500 text-sm">Enter a valid URL to see preview</p>');
                                                        }
                                                        return new \Illuminate\Support\HtmlString(
                                                            '<div class="mt-2">
                                                                <img src="' . htmlspecialchars($url) . '" 
                                                                     alt="Outfit preview" 
                                                                     style="max-width: 100%; max-height: 300px; border-radius: 8px; border: 2px solid #e5e7eb; object-fit: contain;"
                                                                     onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'block\';">
                                                                <p style="display: none; color: #ef4444; font-size: 0.875rem; margin-top: 0.5rem;">Failed to load image. Please check the URL.</p>
                                                            </div>'
                                                        );
                                                    })
                                                    ->visible(fn ($get) => !empty($get('url')))
                                                    ->columnSpanFull(),
                                            ])
                                            ->itemLabel(fn (array $state): ?string => $state['url'] ?? null)
                                            ->defaultItems(1)
                                            ->collapsible()
                                            ->columnSpanFull(),
                                    ])
                                    ->defaultItems(1)
                                    ->itemLabel(fn (array $state): ?string => $state['outfit_name'] ?? 'New Outfit')
                                    ->collapsible()
                                    ->collapsed(fn ($livewire) => $livewire instanceof \App\Filament\Resources\PhotoshootResource\Pages\ViewPhotoshoot)
                                    ->addActionLabel('Add Outfit')
                                    ->reorderable()
                                    ->columnSpanFull(),
                            ])
                            ->defaultItems(1)
                            ->itemLabel(fn (array $state): ?string => isset($state['model_id']) && $state['model_id'] ? \App\Models\ShootModel::find($state['model_id'])?->name ?? 'New Model' : 'New Model')
                            ->collapsible()
                            ->addActionLabel('Add Model')
                            ->reorderable()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($get) => $get('shoot_type') === 'Campaign')
                    ->columns(1),
                
                // Campaign Deliverables Section
                Forms\Components\Section::make('Campaign Deliverables')
                    ->schema([
                        Forms\Components\Section::make('Video Deliverables')
                            ->schema([
                                Forms\Components\CheckboxList::make('campaign_deliverables_video')
                                    ->label('Video')
                                    ->options([
                                        'BTS Reel x 1 - Landscape' => 'BTS Reel x 1 - Landscape',
                                        'BTS Reel x 3 - Portrait' => 'BTS Reel x 3 - Portrait',
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ])
                            ->collapsible()
                            ->collapsed(false),
                        Forms\Components\Section::make('Photo Deliverables')
                            ->schema([
                                Forms\Components\Checkbox::make('campaign_deliverables_photo')
                                    ->label('Photos')
                                    ->columnSpanFull(),
                            ])
                            ->collapsible()
                            ->collapsed(false),
                        Forms\Components\TextInput::make('campaign_deliverables_url')
                            ->label('Deliverables URL')
                            ->url()
                            ->maxLength(500)
                            ->placeholder('https://...')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($get) => $get('shoot_type') === 'Campaign')
                    ->columns(1),
                
                // Studio Spotlight Section
                Forms\Components\Section::make('Studio Spotlight Details')
                    ->schema([
                        Forms\Components\TextInput::make('studio_name')
                            ->label('Studio Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('studio_contact_name')
                            ->label('Studio Contact Name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('studio_phone_number')
                            ->label('Phone Number')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('studio_email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('studio_social_media')
                            ->label('Social Media')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://...')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('studio_location')
                            ->label('Location')
                            ->rows(2)
                            ->maxLength(500)
                            ->placeholder('Enter full address')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('studio_notes')
                            ->label('Notes')
                            ->rows(4)
                            ->maxLength(2000)
                            ->placeholder('Additional notes about the studio spotlight shoot...')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($get) => $get('shoot_type') === 'Studio Spotlight')
                    ->columns(2),
                
                // Studio Spotlight Deliverables Section
                Forms\Components\Section::make('Studio Spotlight Deliverables')
                    ->schema([
                        Forms\Components\Section::make('Video Deliverables')
                            ->schema([
                                Forms\Components\CheckboxList::make('studio_spotlight_deliverables_video')
                                    ->label('Video')
                                    ->options([
                                        '1 x Landscape Video 5-7 seconds long' => '1 x Landscape Video (16:9) 5-7 seconds long',
                                        '2 x Portrait Videos 5-7 seconds long' => '2 x Portrait Videos (4:5) 5-7 seconds long',
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ])
                            ->collapsible()
                            ->collapsed(false),
                        Forms\Components\Section::make('Photo Deliverables')
                            ->schema([
                                Forms\Components\Section::make('Outside Studio')
                                    ->schema([
                                        Forms\Components\CheckboxList::make('studio_spotlight_deliverables_photo_outside')
                                            ->label('Outside Studio')
                                            ->options([
                                                '2 x Landscape images from outside of studio' => '2 x Landscape images',
                                                '3 x Portrait images from outside of studio' => '3 x Portrait images',
                                            ])
                                            ->columns(2)
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible()
                                    ->collapsed(false),
                                Forms\Components\Section::make('Inside Studio')
                                    ->schema([
                                        Forms\Components\CheckboxList::make('studio_spotlight_deliverables_photo_inside')
                                            ->label('Inside Studio')
                                            ->options([
                                                '10 x Portrait images from inside of studio' => '10 x Portrait images',
                                                '5 x Landscape images from inside of studio' => '5 x Landscape images',
                                            ])
                                            ->columns(2)
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible()
                                    ->collapsed(false),
                            ])
                            ->collapsible()
                            ->collapsed(false),
                        Forms\Components\TextInput::make('studio_spotlight_deliverables_url')
                            ->label('Deliverables URL')
                            ->url()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($get) => $get('shoot_type') === 'Studio Spotlight')
                    ->columns(1),
                
                // Behind The Scenes Section
                Forms\Components\Section::make('Behind The Scenes Details')
                    ->schema([
                        Forms\Components\Placeholder::make('bts_info')
                            ->label('Behind The Scenes Deliverables')
                            ->content('Behind The Scenes-specific fields will appear here'),
                        // Add behind the scenes-specific fields here
                    ])
                    ->visible(fn ($get) => $get('shoot_type') === 'Behind The Scenes')
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('shoot_name')
                    ->label('Shoot Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->url(fn ($record): string => route('filament.admin.resources.photoshoots.view', $record))
                    ->color('primary')
                    ->extraAttributes(['class' => 'underline']),
                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('photographer')
                    ->label('Photographer')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('shoot_day_point_of_contact')
                    ->label('Point of Contact')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('completed')
                    ->label('Completed')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
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
            'index' => Pages\ListPhotoshoots::route('/'),
            'create' => Pages\CreatePhotoshoot::route('/create'),
            'view' => Pages\ViewPhotoshoot::route('/{record}'),
            'edit' => Pages\EditPhotoshoot::route('/{record}/edit'),
        ];
    }
}
