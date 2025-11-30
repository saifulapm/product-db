<?php

namespace App\Filament\Resources\GarmentResource\Pages;

use App\Filament\Resources\GarmentResource;
use App\Filament\Resources\GarmentResource\Widgets\CubicDimensionsWidget;
use App\Filament\Resources\GarmentResource\Widgets\GarmentMeasurementsWidget;
use App\Filament\Resources\GarmentResource\Widgets\VariantEntryWidget;
use App\Filament\Resources\GarmentResource\Widgets\VariantsSummaryWidget;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateGarment extends CreateRecord
{
    protected static string $resource = GarmentResource::class;

    protected $listeners = [
        'sync-variants-to-form' => 'syncVariantsToForm',
        'sync-measurements-to-form' => 'syncMeasurementsToForm',
        'sync-cubic-dimensions-to-form' => 'syncCubicDimensionsToForm',
        'save-garment-form' => 'handleSave',
    ];

    public function syncVariantsToForm($variants): void
    {
        // Convert variants from widget to form format
        $formData = $this->form->getState();
        $formData['variants'] = $variants;
        $this->form->fill($formData);
    }

    public function syncMeasurementsToForm($measurements): void
    {
        // Convert measurements from widget to form format
        $formData = $this->form->getState();
        $formData['measurements'] = $measurements;
        $this->form->fill($formData);
    }

    public function syncCubicDimensionsToForm($cubicDimensions): void
    {
        // Convert cubic dimensions from widget to form format
        $formData = $this->form->getState();
        $formData['cubic_dimensions'] = $cubicDimensions;
        $this->form->fill($formData);
    }

    public function handleSave(): void
    {
        // Ensure form is filled with latest data from the sync
        $this->form->fill($this->form->getState());
        
        // Call the parent save method which handles validation and persistence
        $this->save();
    }

    protected function afterCreate(): void
    {
        // Redirect to view page after creating
        $this->redirect(GarmentResource::getUrl('view', ['record' => $this->record]));
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [
            GarmentMeasurementsWidget::class,
            VariantsSummaryWidget::class,
            VariantEntryWidget::class,
            CubicDimensionsWidget::class,
        ];
    }

    protected function getFormActions(): array
    {
        return []; // Hide default form actions - buttons are in the widget at bottom
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure variants are included in save data
        // The variants are synced from the widget via syncVariantsToForm
        if (!isset($data['variants'])) {
            $data['variants'] = [];
        }
        
        // Filter out empty variants
        if (is_array($data['variants'])) {
            $data['variants'] = array_filter($data['variants'], function($variant) {
                return !empty($variant['name']) || !empty($variant['sku']) || !empty($variant['inventory']);
            });
            // Re-index array
            $data['variants'] = array_values($data['variants']);
        }

        // Ensure measurements are included in save data
        if (!isset($data['measurements'])) {
            $data['measurements'] = [];
        }
        
        // Filter out empty measurements (fabric panels)
        if (is_array($data['measurements'])) {
            $data['measurements'] = array_filter($data['measurements'], function($panel) {
                return !empty($panel['fabric_panel_name']) || !empty($panel['image_url']);
            });
            // Re-index array
            $data['measurements'] = array_values($data['measurements']);
        }

        // Ensure cubic_dimensions are included in save data
        if (!isset($data['cubic_dimensions'])) {
            $data['cubic_dimensions'] = null;
        }
        
        // Filter out empty cubic dimensions
        if (is_array($data['cubic_dimensions'])) {
            $hasValues = !empty($data['cubic_dimensions']['length']) || 
                        !empty($data['cubic_dimensions']['width']) || 
                        !empty($data['cubic_dimensions']['height']);
            if (!$hasValues) {
                $data['cubic_dimensions'] = null;
            }
        }
        
        return $data;
    }
}
