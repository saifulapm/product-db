<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PackingListResource\Pages;
use App\Models\IncomingShipment;
use App\Models\PackingListRecord;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PackingListResource extends Resource
{
    protected static ?string $model = PackingListRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Packing Lists';
    protected static ?string $modelLabel = 'Packing List';
    protected static ?string $pluralModelLabel = 'Packing Lists';
    protected static ?string $navigationGroup = 'Sock Pre Orders';
    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null) // Disable default record URL since we use custom route parameters
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->url(fn ($record) => PackingListResource::getUrl('view', [
                        'shipmentId' => $record->shipment_id,
                        'pickListIndex' => $record->index,
                    ]))
                    ->openUrlInNewTab(false),
                Tables\Columns\TextColumn::make('shipment_name')
                    ->label('Shipment')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => IncomingShipmentResource::getUrl('edit', ['record' => $record->shipment_id]))
                    ->color('gray'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'fully_picked' => 'success',
                        'partially_picked' => 'warning',
                        'not_picked' => 'gray',
                        'picked' => 'success', // Backward compatibility
                        'completed' => 'success', // Backward compatibility
                        'in_progress' => 'warning', // Backward compatibility
                        'pending' => 'gray', // Backward compatibility
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match($state) {
                        'fully_picked' => 'Fully Picked',
                        'partially_picked' => 'Partially Picked',
                        'not_picked' => 'Not Picked',
                        'picked' => 'Fully Picked', // Backward compatibility
                        'completed' => 'Fully Picked', // Backward compatibility
                        'in_progress' => 'Partially Picked', // Backward compatibility
                        'pending' => 'Not Picked', // Backward compatibility
                        default => 'Not Picked',
                    }),
                Tables\Columns\TextColumn::make('item_count')
                    ->label('Items')
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) => number_format($state) . ' pcs'),
                Tables\Columns\TextColumn::make('progress_percent')
                    ->label('Progress')
                    ->formatStateUsing(fn ($state, $record) => $state . '%')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('shipment_id')
                    ->label('Shipment')
                    ->options(function () {
                        return \App\Models\IncomingShipment::orderBy('name')->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'not_picked' => 'Not Picked',
                        'partially_picked' => 'Partially Picked',
                        'fully_picked' => 'Fully Picked',
                        'pending' => 'Not Picked', // Backward compatibility
                        'in_progress' => 'Partially Picked', // Backward compatibility
                        'completed' => 'Fully Picked', // Backward compatibility
                        'picked' => 'Fully Picked', // Backward compatibility
                    ]),
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Delete Selected')
                        ->requiresConfirmation()
                        ->modalHeading('Delete Packing Lists')
                        ->modalDescription('Are you sure you want to delete the selected packing lists? This action cannot be undone.')
                        ->action(function ($records) {
                            // Parse composite IDs and delete pick lists
                            $deletedCount = 0;
                            $shipmentsToUpdate = [];
                            
                            foreach ($records as $record) {
                                $id = $record->id ?? $record->getKey();
                                if (is_string($id) && strpos($id, '_') !== false) {
                                    [$shipmentId, $pickListIndex] = explode('_', $id, 2);
                                    $shipmentId = (int) $shipmentId;
                                    $pickListIndex = (int) $pickListIndex;
                                    
                                    if (!isset($shipmentsToUpdate[$shipmentId])) {
                                        $shipment = IncomingShipment::find($shipmentId);
                                        if ($shipment) {
                                            $shipmentsToUpdate[$shipmentId] = $shipment;
                                        }
                                    }
                                    
                                    if (isset($shipmentsToUpdate[$shipmentId])) {
                                        $shipment = $shipmentsToUpdate[$shipmentId];
                                        $pickLists = $shipment->pick_lists ?? [];
                                        
                                        if (is_array($pickLists) && isset($pickLists[$pickListIndex])) {
                                            unset($pickLists[$pickListIndex]);
                                            $pickLists = array_values($pickLists); // Re-index array
                                            $shipment->pick_lists = $pickLists;
                                            $deletedCount++;
                                        }
                                    }
                                }
                            }
                            
                            // Save all updated shipments
                            foreach ($shipmentsToUpdate as $shipment) {
                                $shipment->save();
                            }
                            
                            if ($deletedCount > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Packing lists deleted')
                                    ->body($deletedCount . ' packing list(s) deleted successfully.')
                                    ->success()
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->defaultSort('uploaded_at', 'desc');
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
            'index' => Pages\ListPackingLists::route('/'),
            'view' => Pages\ViewPackingList::route('/{shipmentId}/{pickListIndex}'),
        ];
    }
}
