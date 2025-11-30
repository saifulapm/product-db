<?php

namespace App\Filament\Resources\ShelfResource\Pages;

use App\Filament\Resources\ShelfResource;
use App\Models\Location;
use App\Models\Shelf;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListShelves extends ListRecords
{
    protected static string $resource = ShelfResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Action::make('bulk_add')
                ->label('Bulk Add Shelves')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->form([
                    Forms\Components\Wizard::make([
                        Forms\Components\Wizard\Step::make('Select Location')
                            ->schema([
                                Forms\Components\Select::make('location_id')
                                    ->label('Location')
                                    ->options(Location::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Select the location where these shelves will be added'),
                            ])
                            ->description('Choose the location for the shelves'),
                        Forms\Components\Wizard\Step::make('Bulk Paste Shelf Names')
                            ->schema([
                                Forms\Components\Textarea::make('shelf_names')
                                    ->label('Shelf Names')
                                    ->placeholder("Paste one shelf name per line\nExample:\nShelf A1\nShelf A2\nShelf B1")
                                    ->rows(15)
                                    ->required()
                                    ->helperText('Enter one shelf name per line. Each line will create a new shelf.'),
                            ])
                            ->description('Paste shelf names, one per line'),
                    ])
                    ->submitAction(
                        Action::make('submit')
                            ->label('Create Shelves')
                            ->submit('bulkCreateShelves')
                    ),
                ])
                ->action(function (array $data): void {
                    $locationId = $data['location_id'];
                    $shelfNames = $data['shelf_names'];
                    
                    // Parse shelf names from textarea (split by newlines)
                    $names = array_filter(
                        array_map('trim', explode("\n", $shelfNames)),
                        fn($name) => !empty($name)
                    );
                    
                    if (empty($names)) {
                        Notification::make()
                            ->title('No shelf names provided')
                            ->danger()
                            ->body('Please enter at least one shelf name.')
                            ->send();
                        return;
                    }
                    
                    $created = 0;
                    $skipped = 0;
                    
                    foreach ($names as $name) {
                        // Check if shelf with this name already exists for this location
                        $exists = Shelf::where('location_id', $locationId)
                            ->where('name', $name)
                            ->exists();
                        
                        if ($exists) {
                            $skipped++;
                            continue;
                        }
                        
                        // Create new shelf
                        Shelf::create([
                            'location_id' => $locationId,
                            'name' => $name,
                            'code' => null, // Can be filled in later
                            'description' => null,
                            'is_active' => true,
                        ]);
                        
                        $created++;
                    }
                    
                    if ($created > 0) {
                        Notification::make()
                            ->title('Shelves created successfully')
                            ->success()
                            ->body("Created {$created} shelf(s)." . ($skipped > 0 ? " {$skipped} shelf(s) skipped (already exist)." : ''))
                            ->send();
                    } else {
                        Notification::make()
                            ->title('No shelves created')
                            ->warning()
                            ->body("All shelf names already exist for this location.")
                            ->send();
                    }
                }),
        ];
    }
}
