<?php

namespace App\Filament\Resources\FranchiseeResource\Widgets;

use Filament\Widgets\Widget;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Model;

class FranchiseeLogosWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.resources.franchisee-resource.widgets.franchisee-logos-widget';

    protected int | string | array $columnSpan = 'full';

    /**
     * Store all form state inside a single array to keep Livewire happy.
     */
    public array $formData = [];

    public function mount(): void
    {
        $record = $this->getRecord();
        
        if ($record) {
            $logos = $record->logos ?? [];
            $formData = [];
            
            for ($i = 1; $i <= 10; $i++) {
                $formData["logo_{$i}"] = $logos[$i - 1] ?? null;
            }
            
            $this->form->fill($formData);
            $this->formData = $formData;
        }
    }

    protected function getFormStatePath(): string
    {
        return 'formData';
    }

    protected function getRecord(): ?Model
    {
        // Get record ID from route parameter (for edit pages)
        $recordId = request()->route('record');
        
        if ($recordId) {
            return \App\Models\Franchisee::find($recordId);
        }
        
        return null;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Franchisee Logos')
                    ->description('Upload up to 10 logo files for this franchisee')
                    ->schema([
                        FileUpload::make('logo_1')
                            ->label('Logo 1')
                            ->image()
                            ->directory('franchisees/logos')
                            ->disk('public')
                            ->maxSize(5120) // 5MB
                            ->imagePreviewHeight('100')
                            ->downloadable()
                            ->openable()
                            ->previewable(),
                        FileUpload::make('logo_2')
                            ->label('Logo 2')
                            ->image()
                            ->directory('franchisees/logos')
                            ->disk('public')
                            ->maxSize(5120)
                            ->imagePreviewHeight('100')
                            ->downloadable()
                            ->openable()
                            ->previewable(),
                        FileUpload::make('logo_3')
                            ->label('Logo 3')
                            ->image()
                            ->directory('franchisees/logos')
                            ->disk('public')
                            ->maxSize(5120)
                            ->imagePreviewHeight('100')
                            ->downloadable()
                            ->openable()
                            ->previewable(),
                        FileUpload::make('logo_4')
                            ->label('Logo 4')
                            ->image()
                            ->directory('franchisees/logos')
                            ->disk('public')
                            ->maxSize(5120)
                            ->imagePreviewHeight('100')
                            ->downloadable()
                            ->openable()
                            ->previewable(),
                        FileUpload::make('logo_5')
                            ->label('Logo 5')
                            ->image()
                            ->directory('franchisees/logos')
                            ->disk('public')
                            ->maxSize(5120)
                            ->imagePreviewHeight('100')
                            ->downloadable()
                            ->openable()
                            ->previewable(),
                        FileUpload::make('logo_6')
                            ->label('Logo 6')
                            ->image()
                            ->directory('franchisees/logos')
                            ->disk('public')
                            ->maxSize(5120)
                            ->imagePreviewHeight('100')
                            ->downloadable()
                            ->openable()
                            ->previewable(),
                        FileUpload::make('logo_7')
                            ->label('Logo 7')
                            ->image()
                            ->directory('franchisees/logos')
                            ->disk('public')
                            ->maxSize(5120)
                            ->imagePreviewHeight('100')
                            ->downloadable()
                            ->openable()
                            ->previewable(),
                        FileUpload::make('logo_8')
                            ->label('Logo 8')
                            ->image()
                            ->directory('franchisees/logos')
                            ->disk('public')
                            ->maxSize(5120)
                            ->imagePreviewHeight('100')
                            ->downloadable()
                            ->openable()
                            ->previewable(),
                        FileUpload::make('logo_9')
                            ->label('Logo 9')
                            ->image()
                            ->directory('franchisees/logos')
                            ->disk('public')
                            ->maxSize(5120)
                            ->imagePreviewHeight('100')
                            ->downloadable()
                            ->openable()
                            ->previewable(),
                        FileUpload::make('logo_10')
                            ->label('Logo 10')
                            ->image()
                            ->directory('franchisees/logos')
                            ->disk('public')
                            ->maxSize(5120)
                            ->imagePreviewHeight('100')
                            ->downloadable()
                            ->openable()
                            ->previewable(),
                    ])
                    ->columns(2),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $record = $this->getRecord();
        
        if ($record) {
            $logos = [];
            for ($i = 1; $i <= 10; $i++) {
                $logoKey = "logo_{$i}";
                if (!empty($data[$logoKey])) {
                    $logoValue = is_array($data[$logoKey]) ? (isset($data[$logoKey][0]) ? $data[$logoKey][0] : null) : $data[$logoKey];
                    if ($logoValue) {
                        $logos[] = $logoValue;
                    }
                }
            }
            
            $record->update(['logos' => $logos]);
            
            \Filament\Notifications\Notification::make()
                ->title('Logos saved successfully')
                ->success()
                ->send();
        } else {
            \Filament\Notifications\Notification::make()
                ->title('Please save the franchisee first')
                ->warning()
                ->send();
        }
    }
}

