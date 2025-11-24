<?php

namespace App\Filament\Resources\MockupsSubmissionResource\Pages;

use App\Filament\Resources\MockupsSubmissionResource;
use App\Models\MockupsSubmission;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListMockupsSubmissions extends ListRecords
{
    protected static string $resource = MockupsSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Action::make('import_from_paste')
                ->label('Import from Paste')
                ->icon('heroicon-o-clipboard-document')
                ->color('success')
                ->form([
                    Textarea::make('pasted_data')
                        ->label('Paste Submission Data')
                        ->placeholder('Paste the submission data here...')
                        ->rows(15)
                        ->required()
                        ->helperText('Paste the submission data including customer info and products list.')
                ])
                ->action(function (array $data) {
                    $pastedData = $data['pasted_data'];
                    $submission = $this->parseAndCreateSubmission($pastedData);
                    
                    if ($submission) {
                        Notification::make()
                            ->title('Submission Imported')
                            ->body('Submission has been successfully imported.')
                            ->success()
                            ->send();
                        
                        return redirect()->to(MockupsSubmissionResource::getUrl('view', ['record' => $submission]));
                    } else {
                        Notification::make()
                            ->title('Import Failed')
                            ->body('Failed to parse the submission data. Please check the format.')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    protected function parseAndCreateSubmission(string $pastedData): ?MockupsSubmission
    {
        try {
            $lines = explode("\n", trim($pastedData));
            
            $data = [
                'customer_name' => '',
                'customer_email' => '',
                'customer_phone' => '',
                'website' => '',
                'company_name' => '',
                'instagram' => '',
                'notes' => '',
                'products' => [],
            ];
            
            $inProductsSection = false;
            $inNoteSection = false;
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                // Check if we're entering the products section
                if (stripos($line, 'Products') !== false && stripos($line, ':') === false) {
                    $inProductsSection = true;
                    $inNoteSection = false;
                    continue;
                }
                
                // Check if we're in the note section
                if (stripos($line, 'Note:') !== false || stripos($line, 'Notes:') !== false) {
                    $inNoteSection = true;
                    $inProductsSection = false;
                    // Check if there's content after "Note:"
                    $parts = explode(':', $line, 2);
                    if (isset($parts[1]) && !empty(trim($parts[1]))) {
                        $data['notes'] = trim($parts[1]);
                    }
                    continue;
                }
                
                if ($inProductsSection) {
                    // Parse product lines - anything that doesn't have a colon is a product
                    if (stripos($line, ':') === false && !empty($line)) {
                        $productName = trim($line);
                        if (!empty($productName)) {
                            // Try to extract color from product name (e.g., "The Beanie - Army")
                            $parts = explode(' - ', $productName);
                            $name = trim($parts[0]);
                            $color = isset($parts[1]) ? trim($parts[1]) : '';
                            
                            $data['products'][] = [
                                'product_name' => $name,
                                'style' => '',
                                'color' => $color,
                                'status' => 'Pending',
                                'notes' => '',
                                'minimums' => '',
                                'pricing' => '',
                                'front_pdf' => null,
                                'back_pdf' => null,
                            ];
                        }
                    }
                } elseif ($inNoteSection) {
                    // Collect note content
                    if (empty($data['notes'])) {
                        $data['notes'] = $line;
                    } else {
                        $data['notes'] .= "\n" . $line;
                    }
                } else {
                    // Parse field-value pairs
                    if (preg_match('/^(.+?):\s*(.+)$/i', $line, $matches)) {
                        $field = trim($matches[1]);
                        $value = trim($matches[2]);
                        
                        switch (strtolower($field)) {
                            case 'name':
                                $data['customer_name'] = $value;
                                break;
                            case 'email':
                                $data['customer_email'] = $value;
                                break;
                            case 'phone':
                                $data['customer_phone'] = preg_replace('/[^0-9]/', '', $value);
                                break;
                            case 'website':
                                $data['website'] = $value;
                                break;
                            case 'company name':
                                $data['company_name'] = $value;
                                break;
                            case 'instagram':
                                $data['instagram'] = $value;
                                break;
                        }
                    }
                }
            }
            
            // Generate tracking number
            $lastSubmission = MockupsSubmission::orderBy('tracking_number', 'desc')->first();
            $trackingNumber = $lastSubmission ? $lastSubmission->tracking_number + 1 : 1;
            
            // Create the submission
            $submission = MockupsSubmission::create([
                'tracking_number' => $trackingNumber,
                'title' => 'Submission #' . $trackingNumber . ($data['company_name'] ? ' - ' . $data['company_name'] : ''),
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'],
                'website' => $data['website'],
                'company_name' => $data['company_name'],
                'instagram' => $data['instagram'],
                'notes' => $data['notes'] ?: $currentNote,
                'products' => $data['products'],
                'created_by' => auth()->id(),
                'is_completed' => false,
            ]);
            
            return $submission;
            
        } catch (\Exception $e) {
            \Log::error('Error parsing submission data: ' . $e->getMessage());
            return null;
        }
    }
}
