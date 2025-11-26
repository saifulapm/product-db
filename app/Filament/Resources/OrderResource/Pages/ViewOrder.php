<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Order Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('order_number')
                            ->label('Order Number')
                            ->size('lg')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('client_name')
                            ->label('Client Name'),
                        Infolists\Components\TextEntry::make('incomingShipment.tracking_number')
                            ->label('Source Shipment')
                            ->url(fn ($record) => $record->incomingShipment 
                                ? \App\Filament\Resources\IncomingShipmentResource::getUrl('view', ['record' => $record->incomingShipment])
                                : null
                            )
                            ->color('primary'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'picking' => 'info',
                                'picked' => 'success',
                                'shipped' => 'primary',
                                'completed' => 'gray',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'pending' => 'Pending',
                                'picking' => 'Picking',
                                'picked' => 'Picked',
                                'shipped' => 'Shipped',
                                'completed' => 'Completed',
                                default => $state,
                            }),
                    ])
                    ->columns(2),
                
                Infolists\Components\Section::make('Pick List Items')
                    ->description('Shows which cartons to pick from. When a carton is empty, the system will indicate the next available carton.')
                    ->schema([
                        Infolists\Components\TextEntry::make('items')
                            ->label('')
                            ->formatStateUsing(function ($state, $record) {
                                if (empty($state) || !is_array($state)) {
                                    return new \Illuminate\Support\HtmlString('<p class="text-gray-500">No items in this order.</p>');
                                }
                                
                                $shipment = $record->incomingShipment;
                                $orderItems = $record->orderItems()->get()->groupBy(function($item) {
                                    return strtolower(trim($item->style ?? '')) . '|' . 
                                           strtolower(trim($item->color ?? '')) . '|' . 
                                           strtolower(trim($item->packing_way ?? ''));
                                });
                                
                                $html = '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <thead class="bg-gray-50 dark:bg-gray-900">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">Item #</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">Description</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">Warehouse Location</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700"># Required</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pick From Carton(s)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">';
                                
                                $totalQty = 0;
                                foreach ($state as $index => $item) {
                                    $description = $item['description'] ?? '—';
                                    $warehouseLocation = $item['warehouse_location'] ?? '';
                                    $quantity = $item['quantity_required'] ?? 0;
                                    $totalQty += $quantity;
                                    
                                    // Parse description to find matching cartons
                                    $parsed = \App\Models\Order::parseOrderDescription($description);
                                    $cartonInfo = '';
                                    
                                    if ($shipment && !empty($parsed['style'])) {
                                        $availableCartons = $shipment->getAvailableCartonsForItem(
                                            $parsed['style'],
                                            $parsed['color'],
                                            $parsed['packing_way'],
                                            $quantity
                                        );
                                        
                                        if (!empty($availableCartons)) {
                                            $cartonNumbers = array_map(function($c) {
                                                return 'CTN#' . ($c['carton_number'] ?: '?');
                                            }, $availableCartons);
                                            $cartonInfo = '<span class="text-green-600 dark:text-green-400 font-semibold">' . implode(', ', $cartonNumbers) . '</span>';
                                        } else {
                                            // Check if there are other cartons with this item (even if allocated)
                                            $allCartons = [];
                                            foreach ($shipment->items ?? [] as $shipItem) {
                                                if (strtolower(trim($shipItem['style'] ?? '')) === strtolower(trim($parsed['style'])) &&
                                                    strtolower(trim($shipItem['color'] ?? '')) === strtolower(trim($parsed['color'])) &&
                                                    strtolower(trim($shipItem['packing_way'] ?? '')) === strtolower(trim($parsed['packing_way']))) {
                                                    $allCartons[] = $shipItem['carton_number'] ?? '?';
                                                }
                                            }
                                            
                                            if (!empty($allCartons)) {
                                                $cartonInfo = '<span class="text-red-600 dark:text-red-400">All cartons empty. Check: CTN#' . implode(', CTN#', array_unique($allCartons)) . '</span>';
                                            } else {
                                                $cartonInfo = '<span class="text-gray-500">Not found in shipment</span>';
                                            }
                                        }
                                    } else {
                                        $cartonInfo = '<span class="text-gray-400">—</span>';
                                    }
                                    
                                    $html .= '<tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700">' . ($index + 1) . '</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700">' . htmlspecialchars($description) . '</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700">' . htmlspecialchars($warehouseLocation ?: '—') . '</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right font-medium border-r border-gray-200 dark:border-gray-700">' . number_format($quantity) . '</td>
                                        <td class="px-4 py-3 text-sm">' . $cartonInfo . '</td>
                                    </tr>';
                                }
                                
                                $html .= '</tbody>
                                    <tfoot class="bg-gray-50 dark:bg-gray-900 font-semibold">
                                        <tr>
                                            <td colspan="3" class="px-4 py-3 text-right text-sm text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700">Total Items Required:</td>
                                            <td class="px-4 py-3 text-right text-sm text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700">' . number_format($totalQty) . '</td>
                                            <td class="px-4 py-3"></td>
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
