<?php

namespace App\Filament\Resources\MockupsSubmissionResource\Pages;

use App\Filament\Resources\MockupsSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\Storage;

class EditMockupsSubmission extends EditRecord
{
    protected static string $resource = MockupsSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Check if combined PDF was uploaded
        if (!empty($data['combined_mockups_pdf'])) {
            // Handle both string (single file) and array (multiple files) formats
            $combinedPdfPath = is_array($data['combined_mockups_pdf']) 
                ? ($data['combined_mockups_pdf'][0] ?? null)
                : $data['combined_mockups_pdf'];
            
            if ($combinedPdfPath) {
                // Get the full path
                $fullPath = Storage::disk('public')->path($combinedPdfPath);
                
                if (file_exists($fullPath)) {
                    try {
                        $products = $data['products'] ?? [];
                        
                        if (!empty($products)) {
                            // Split PDF and assign pages to products in pairs (front/back)
                            $splitFiles = $this->splitPdfByPages($fullPath);
                            
                            if ($splitFiles && count($splitFiles) > 0) {
                                $productIndex = 0;
                                
                                // Assign pages in pairs: page 1 = front, page 2 = back, page 3 = front, etc.
                                for ($i = 0; $i < count($splitFiles); $i += 2) {
                                    if (isset($products[$productIndex])) {
                                        // Front PDF (odd pages: 1, 3, 5, etc.)
                                        $frontFilePath = $splitFiles[$i];
                                        $frontFilename = 'product-' . ($productIndex + 1) . '-front-' . uniqid() . '.pdf';
                                        $frontRelativePath = 'mockups/products/front/' . $frontFilename;
                                        Storage::disk('public')->put($frontRelativePath, file_get_contents($frontFilePath));
                                        $products[$productIndex]['front_pdf'] = $frontRelativePath;
                                        @unlink($frontFilePath);
                                        
                                        // Back PDF (even pages: 2, 4, 6, etc.) - if exists
                                        if (isset($splitFiles[$i + 1])) {
                                            $backFilePath = $splitFiles[$i + 1];
                                            $backFilename = 'product-' . ($productIndex + 1) . '-back-' . uniqid() . '.pdf';
                                            $backRelativePath = 'mockups/products/back/' . $backFilename;
                                            Storage::disk('public')->put($backRelativePath, file_get_contents($backFilePath));
                                            $products[$productIndex]['back_pdf'] = $backRelativePath;
                                            @unlink($backFilePath);
                                        }
                                        
                                        $productIndex++;
                                    }
                                }
                                
                                $data['products'] = $products;
                                
                                Notification::make()
                                    ->title('PDF Split Successfully')
                                    ->body('The combined PDF has been split and assigned to products (front/back pairs).')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('PDF Split Failed')
                                    ->body('Could not split the PDF. Please check the file and try again.')
                                    ->warning()
                                    ->send();
                            }
                        }
                        
                        // Clean up the combined PDF file (we don't need to store it)
                        Storage::disk('public')->delete($combinedPdfPath);
                        
                    } catch (\Exception $e) {
                        \Log::error('Error splitting PDF: ' . $e->getMessage());
                        Notification::make()
                            ->title('Error Splitting PDF')
                            ->body('Could not split PDF: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }
            }
            
            // Remove combined PDF from data (we don't need to store it)
            unset($data['combined_mockups_pdf']);
        }
        
        return $data;
    }

    /**
     * Split a PDF file into individual pages
     */
    protected function splitPdfByPages(string $pdfPath): ?array
    {
        try {
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($pdfPath);
            
            $splitFiles = [];
            
            for ($pageNum = 1; $pageNum <= $pageCount; $pageNum++) {
                $newPdf = new Fpdi();
                $newPdf->setSourceFile($pdfPath);
                
                $templateId = $newPdf->importPage($pageNum);
                $size = $newPdf->getTemplateSize($templateId);
                
                $newPdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $newPdf->useTemplate($templateId);
                
                $outputPath = $tempDir . '/page-' . $pageNum . '-' . uniqid() . '.pdf';
                $newPdf->Output('F', $outputPath);
                
                $splitFiles[] = $outputPath;
            }
            
            return $splitFiles;
            
        } catch (\Exception $e) {
            \Log::error('Error splitting PDF: ' . $e->getMessage());
            return null;
        }
    }
}
