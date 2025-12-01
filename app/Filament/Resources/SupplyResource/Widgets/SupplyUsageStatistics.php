<?php

namespace App\Filament\Resources\SupplyResource\Widgets;

use App\Models\Supply;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Livewire\Attributes\Reactive;

class SupplyUsageStatistics extends Widget
{
    protected static string $view = 'filament.resources.supply-resource.widgets.supply-usage-statistics';

    protected int | string | array $columnSpan = 'full';

    #[Reactive]
    public ?Supply $record = null;

    protected function getRecord(): ?Supply
    {
        if ($this->record instanceof Supply) {
            return $this->record;
        }

        $recordId = request()->route('record');
        
        if ($recordId) {
            return Supply::find($recordId);
        }
        
        return null;
    }

    public function getUsageStatistics(): array
    {
        $record = $this->getRecord();
        
        if (!$record instanceof Supply) {
            return [
                'total_used' => 0,
                'average_per_month' => 0,
                'months_tracked' => 0,
            ];
        }

        $tracking = $record->shipment_tracking ?? [];
        
        if (empty($tracking) || !is_array($tracking)) {
            return [
                'total_used' => 0,
                'average_per_month' => 0,
                'months_tracked' => 0,
            ];
        }

        // Calculate total used (each shipment uses 1 supply)
        $totalUsed = count($tracking);

        // Group shipments by month
        $monthlyUsage = [];
        $firstShipmentDate = null;
        $lastShipmentDate = null;

        foreach ($tracking as $shipment) {
            if (empty($shipment['used_at'])) {
                continue;
            }

            try {
                $date = Carbon::parse($shipment['used_at']);
                $monthKey = $date->format('Y-m');
                
                if (!isset($monthlyUsage[$monthKey])) {
                    $monthlyUsage[$monthKey] = 0;
                }
                
                $monthlyUsage[$monthKey]++;
                
                // Track first and last shipment dates
                if ($firstShipmentDate === null || $date->lt($firstShipmentDate)) {
                    $firstShipmentDate = $date;
                }
                if ($lastShipmentDate === null || $date->gt($lastShipmentDate)) {
                    $lastShipmentDate = $date;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Calculate average per month
        $averagePerMonth = 0;
        $monthsTracked = 0;

        if ($firstShipmentDate && $lastShipmentDate) {
            // Calculate number of months between first and last shipment
            $monthsTracked = $firstShipmentDate->diffInMonths($lastShipmentDate) + 1;
            
            // If less than 1 month, use 1 month as minimum
            if ($monthsTracked < 1) {
                $monthsTracked = 1;
            }
            
            $averagePerMonth = $monthsTracked > 0 ? round($totalUsed / $monthsTracked, 2) : 0;
        } elseif (count($monthlyUsage) > 0) {
            // If we have monthly data but no date range, use number of months with data
            $monthsTracked = count($monthlyUsage);
            $averagePerMonth = round($totalUsed / $monthsTracked, 2);
        }

        return [
            'total_used' => $totalUsed,
            'average_per_month' => $averagePerMonth,
            'months_tracked' => $monthsTracked,
            'monthly_breakdown' => $monthlyUsage,
            'first_shipment' => $firstShipmentDate ? $firstShipmentDate->format('M d, Y') : null,
            'last_shipment' => $lastShipmentDate ? $lastShipmentDate->format('M d, Y') : null,
        ];
    }
}

