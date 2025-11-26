<?php

namespace App\Filament\Resources\IncomingShipmentResource\Pages;

use App\Filament\Resources\IncomingShipmentResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewIncomingShipment extends ViewRecord
{
    protected static string $resource = IncomingShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create_order')
                ->label('Create Order from Shipment')
                ->icon('heroicon-o-shopping-bag')
                ->color('success')
                ->url(fn () => \App\Filament\Resources\OrderResource::getUrl('create', [
                    'incoming_shipment_id' => $this->record->id,
                ]))
                ->visible(fn () => $this->record->status === 'received'),
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Shipment Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('tracking_number')
                            ->label('Tracking Number'),
                        Infolists\Components\TextEntry::make('carrier')
                            ->label('Carrier'),
                        Infolists\Components\TextEntry::make('supplier')
                            ->label('Supplier'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'in_transit' => 'info',
                                'received' => 'success',
                                'delayed' => 'danger',
                                'cancelled' => 'gray',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'pending' => 'Pending',
                                'in_transit' => 'In Transit',
                                'received' => 'Received',
                                'delayed' => 'Delayed',
                                'cancelled' => 'Cancelled',
                                default => $state,
                            }),
                    ])
                    ->columns(2),
                
                Infolists\Components\Section::make('Dates')
                    ->schema([
                        Infolists\Components\TextEntry::make('expected_date')
                            ->label('Expected Date')
                            ->date('M d, Y'),
                        Infolists\Components\TextEntry::make('received_date')
                            ->label('Received Date')
                            ->date('M d, Y'),
                    ])
                    ->columns(2),
                
                Infolists\Components\Section::make('Packing List Items - Available Stock')
                    ->description('Shows original quantities and remaining available stock after orders have been picked.')
                    ->schema([
                        Infolists\Components\TextEntry::make('items')
                            ->label('')
                            ->formatStateUsing(function ($state, $record) {
                                if (empty($state) || !is_array($state)) {
                                    return new \Illuminate\Support\HtmlString('<p class="text-gray-500">No items in this shipment.</p>');
                                }
                                
                                // Get available quantities by carton
                                $availableQuantities = $record->getAvailableQuantitiesByCarton();
                                
                                $html = '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <thead class="bg-gray-50 dark:bg-gray-900">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">CTN#</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">Style</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">Color</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">Packing Way</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">Original Qty</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">Allocated</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Available</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">';
                                
                                $totalOriginal = 0;
                                $totalAllocated = 0;
                                $totalAvailable = 0;
                                
                                foreach ($availableQuantities as $item) {
                                    $carton = $item['carton_number'] ?: '—';
                                    $style = $item['style'] ?: '—';
                                    $color = $item['color'] ?: '—';
                                    $packingWay = $item['packing_way'] ?: '—';
                                    $originalQty = $item['original_quantity'];
                                    $allocatedQty = $item['allocated_quantity'];
                                    $availableQty = $item['available_quantity'];
                                    
                                    $totalOriginal += $originalQty;
                                    $totalAllocated += $allocatedQty;
                                    $totalAvailable += $availableQty;
                                    
                                    // Color code: red if empty, yellow if low, green if available
                                    $availableClass = $availableQty === 0 
                                        ? 'text-red-600 dark:text-red-400 font-semibold' 
                                        : ($availableQty < $originalQty * 0.5 
                                            ? 'text-yellow-600 dark:text-yellow-400' 
                                            : 'text-green-600 dark:text-green-400');
                                    
                                    $html .= '<tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700 font-medium">' . htmlspecialchars($carton) . '</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700">' . htmlspecialchars($style) . '</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700">' . htmlspecialchars($color) . '</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700">' . htmlspecialchars($packingWay) . '</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right border-r border-gray-200 dark:border-gray-700">' . number_format($originalQty) . '</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right border-r border-gray-200 dark:border-gray-700">' . number_format($allocatedQty) . '</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium ' . $availableClass . '">' . number_format($availableQty) . '</td>
                                    </tr>';
                                }
                                
                                $html .= '</tbody>
                                    <tfoot class="bg-gray-50 dark:bg-gray-900 font-semibold">
                                        <tr>
                                            <td colspan="4" class="px-4 py-3 text-right text-sm text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700">Totals:</td>
                                            <td class="px-4 py-3 text-right text-sm text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700">' . number_format($totalOriginal) . '</td>
                                            <td class="px-4 py-3 text-right text-sm text-gray-500 dark:text-gray-400 border-r border-gray-200 dark:border-gray-700">' . number_format($totalAllocated) . '</td>
                                            <td class="px-4 py-3 text-right text-sm text-green-600 dark:text-green-400">' . number_format($totalAvailable) . '</td>
                                        </tr>
                                    </tfoot>
                                </table></div>';
                                
                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->columnSpanFull(),
                    ]),
                
                Infolists\Components\Section::make('Notes')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('')
                            ->default('—')
                            ->placeholder('No notes'),
                    ])
                    ->visible(fn ($record) => !empty($record->notes)),
            ]);
    }
}
