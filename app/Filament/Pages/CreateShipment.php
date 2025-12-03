<?php

namespace App\Filament\Pages;

use App\Models\Garment;
use App\Models\Supply;
use App\Models\User;
use App\Notifications\SupplyReorderNotification;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class CreateShipment extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Create A Shipment';

    protected static ?string $navigationGroup = 'Shipping';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.create-shipment';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('shipments.create');
    }

    public ?array $data = [];
    public ?array $recommendedSupply = null;

    public function mount(): void
    {
        $this->form->fill([
            'selectedGarmentId' => null,
            'quantity' => 1,
            'order_number' => null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('selectedGarmentId')
                    ->label('Select Garment')
                    ->options(Garment::query()->pluck('name', 'id'))
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn () => $this->calculateBestFit()),
                Forms\Components\TextInput::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->calculateBestFit()),
                Forms\Components\TextInput::make('order_number')
                    ->label('Order Number')
                    ->required()
                    ->maxLength(255),
            ])
            ->statePath('data');
    }

    public function calculateBestFit(): void
    {
        $selectedGarmentId = $this->data['selectedGarmentId'] ?? null;
        $quantity = (int)($this->data['quantity'] ?? 1);

        if (!$selectedGarmentId || !$quantity) {
            $this->recommendedSupply = null;
            return;
        }

        $garment = Garment::find($selectedGarmentId);
        if (!$garment) {
            $this->recommendedSupply = null;
            return;
        }

        // Get garment cubic dimensions
        $garmentDimensions = $garment->cubic_dimensions ?? [];
        $garmentLength = (float)($garmentDimensions['length'] ?? 0);
        $garmentWidth = (float)($garmentDimensions['width'] ?? 0);
        $garmentHeight = (float)($garmentDimensions['height'] ?? 0);

        if ($garmentLength <= 0 || $garmentWidth <= 0 || $garmentHeight <= 0) {
            $this->recommendedSupply = null;
            Notification::make()
                ->title('No Cubic Dimensions')
                ->body('This garment does not have cubic dimensions set. Please add cubic dimensions to the garment first.')
                ->warning()
                ->send();
            return;
        }

        // Calculate total volume needed (garment volume * quantity)
        $garmentVolume = $garmentLength * $garmentWidth * $garmentHeight;
        $totalVolumeNeeded = $garmentVolume * $quantity;

        // Get all supplies with cubic measurements
        $supplies = Supply::whereNotNull('cubic_measurements')
            ->get();

        $bestFit = null;
        $bestFitWaste = null;

        foreach ($supplies as $supply) {
            $supplyDimensions = $supply->cubic_measurements ?? [];
            $supplyLength = (float)($supplyDimensions['length'] ?? 0);
            $supplyWidth = (float)($supplyDimensions['width'] ?? 0);
            $supplyHeight = (float)($supplyDimensions['height'] ?? 0);

            if ($supplyLength <= 0 || $supplyWidth <= 0 || $supplyHeight <= 0) {
                continue;
            }

            // Check if garment fits in supply (considering rotation)
            $fits = $this->checkIfFits($garmentLength, $garmentWidth, $garmentHeight, $supplyLength, $supplyWidth, $supplyHeight);

            if ($fits) {
                $supplyVolume = $supplyLength * $supplyWidth * $supplyHeight;
                $waste = $supplyVolume - $totalVolumeNeeded;

                // Find the supply with the least waste that still fits
                if ($bestFit === null || ($waste >= 0 && ($bestFitWaste === null || $waste < $bestFitWaste))) {
                    $bestFit = $supply;
                    $bestFitWaste = $waste;
                }
            }
        }

        if ($bestFit) {
            $bestFitDimensions = $bestFit->cubic_measurements ?? [];
            $this->recommendedSupply = [
                'id' => $bestFit->id,
                'name' => $bestFit->name,
                'type' => $bestFit->type,
                'quantity' => $bestFit->quantity ?? 0,
                'length' => $bestFitDimensions['length'] ?? 0,
                'width' => $bestFitDimensions['width'] ?? 0,
                'height' => $bestFitDimensions['height'] ?? 0,
                'waste_percentage' => $bestFitWaste > 0 ? round(($bestFitWaste / ($bestFitDimensions['length'] * $bestFitDimensions['width'] * $bestFitDimensions['height'])) * 100, 2) : 0,
            ];
        } else {
            $this->recommendedSupply = null;
            Notification::make()
                ->title('No Suitable Supply Found')
                ->body('No supply is large enough to fit this garment. Please check the garment\'s cubic dimensions or add larger supplies.')
                ->warning()
                ->send();
        }
    }

    protected function checkIfFits(float $garmentL, float $garmentW, float $garmentH, float $supplyL, float $supplyW, float $supplyH): bool
    {
        // Try all 6 possible orientations
        $orientations = [
            [$garmentL, $garmentW, $garmentH],
            [$garmentL, $garmentH, $garmentW],
            [$garmentW, $garmentL, $garmentH],
            [$garmentW, $garmentH, $garmentL],
            [$garmentH, $garmentL, $garmentW],
            [$garmentH, $garmentW, $garmentL],
        ];

        foreach ($orientations as [$l, $w, $h]) {
            if ($l <= $supplyL && $w <= $supplyW && $h <= $supplyH) {
                return true;
            }
        }

        return false;
    }

    public function commitShipment()
    {
        $orderNumber = $this->data['order_number'] ?? null;
        $supplyId = $this->recommendedSupply['id'] ?? null;

        if (!$orderNumber) {
            Notification::make()
                ->title('Order Number Required')
                ->body('Please enter an order number before committing the shipment.')
                ->warning()
                ->send();
            return null;
        }

        if (!$supplyId) {
            Notification::make()
                ->title('No Supply Selected')
                ->body('Please select a garment to get a recommended supply.')
                ->warning()
                ->send();
            return null;
        }

        $supply = Supply::find($supplyId);
        if (!$supply) {
            Notification::make()
                ->title('Supply Not Found')
                ->body('The selected supply could not be found.')
                ->danger()
                ->send();
            return null;
        }

        if ($supply->quantity <= 0) {
            Notification::make()
                ->title('Insufficient Inventory')
                ->body('This supply is out of stock. Please select a different supply.')
                ->warning()
                ->send();
            return null;
        }

        // Deduct 1 from quantity
        $supply->quantity = max(0, $supply->quantity - 1);

        // Add to shipment tracking
        $tracking = $supply->shipment_tracking ?? [];
        $tracking[] = [
            'order_number' => $orderNumber,
            'used_at' => now()->toDateTimeString(),
            'garment_id' => $this->data['selectedGarmentId'] ?? null,
            'quantity' => $this->data['quantity'] ?? 1,
        ];

        $supply->shipment_tracking = $tracking;
        $supply->save();

        // Refresh the supply to get updated quantity
        $supply->refresh();

        // Check if quantity has reached or fallen below reorder point
        if ($supply->reorder_point !== null && 
            $supply->quantity <= $supply->reorder_point && 
            $supply->quantity >= 0) {
            
            // Send notification to all super-admin users
            $adminUsers = User::whereHas('roles', function ($query) {
                $query->where('slug', 'super-admin')
                      ->orWhere('name', 'like', '%admin%')
                      ->orWhere('name', 'like', '%super%');
            })->get();
            
            // If no admin users found, send to all users (fallback)
            if ($adminUsers->isEmpty()) {
                $adminUsers = User::all();
            }
            
            // Send notification to each admin user
            foreach ($adminUsers as $user) {
                $user->notify(new SupplyReorderNotification($supply));
            }
        }

        // Update recommended supply quantity
        if ($this->recommendedSupply) {
            $this->recommendedSupply['quantity'] = $supply->quantity;
        }

        Notification::make()
            ->title('Shipment Committed')
            ->body("Shipment for order {$orderNumber} has been committed. Inventory updated.")
            ->success()
            ->send();

        // Redirect to shipments page
        return $this->redirect(\App\Filament\Pages\Shipments::getUrl(), navigate: true);
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->statePath('data')
            ),
        ];
    }
}

