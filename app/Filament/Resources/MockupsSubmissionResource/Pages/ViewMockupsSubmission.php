<?php

namespace App\Filament\Resources\MockupsSubmissionResource\Pages;

use App\Filament\Resources\MockupsSubmissionResource;
use App\Models\MockupsSubmission;
use App\Models\Product;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class ViewMockupsSubmission extends ViewRecord
{
    use WithFileUploads;

    protected static string $resource = MockupsSubmissionResource::class;

    public static function handlePdfUpload($recordId)
    {
        try {
            $record = MockupsSubmission::findOrFail($recordId);
            $products = $record->products ?? [];
            
            if (empty($products)) {
                session()->flash('error', 'Please add products before uploading PDF.');
                return redirect()->to(\App\Filament\Resources\MockupsSubmissionResource::getUrl('view', ['record' => $record]));
            }

            $uploadedFile = request()->file('pdfFile');
            if (!$uploadedFile || !$uploadedFile->isValid()) {
                session()->flash('error', 'Invalid file uploaded.');
                return redirect()->to(\App\Filament\Resources\MockupsSubmissionResource::getUrl('view', ['record' => $record]));
            }

            // Get the uploaded file path
            $tempPath = $uploadedFile->getRealPath();
            
            if (!file_exists($tempPath)) {
                session()->flash('error', 'The uploaded file could not be found.');
                return redirect()->to(\App\Filament\Resources\MockupsSubmissionResource::getUrl('view', ['record' => $record]));
            }

            // Split PDF into individual pages
            try {
                // First, get page count
                $pdfReader = new Fpdi();
                $pageCount = $pdfReader->setSourceFile($tempPath);
                
                if ($pageCount === 0) {
                    session()->flash('error', 'The PDF file appears to be empty or invalid.');
                    return redirect()->to(\App\Filament\Resources\MockupsSubmissionResource::getUrl('view', ['record' => $record]));
                }
                
                \Log::info("PDF has {$pageCount} pages");
            } catch (\Exception $e) {
                \Log::error('Error reading PDF file: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
                session()->flash('error', 'Error reading PDF file: ' . $e->getMessage());
                return redirect()->to(\App\Filament\Resources\MockupsSubmissionResource::getUrl('view', ['record' => $record]));
            }

            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Deep copy products array to ensure proper updates
            $updatedProducts = json_decode(json_encode($products), true);
            if (!is_array($updatedProducts)) {
                $updatedProducts = [];
            }
            
            $productIndex = 0;
            $pageNumber = 1;

            // Process pages: Page 1 → Product 1 front, Page 2 → Product 1 back, Page 3 → Product 2 front, etc.
            for ($i = 1; $i <= $pageCount; $i++) {
                // Ensure product exists at this index
                if ($productIndex >= count($updatedProducts)) {
                    // Create new product entry if we run out
                    $updatedProducts[] = [
                        'product_name' => 'Product ' . (count($updatedProducts) + 1),
                        'style' => '',
                        'color' => '',
                        'front_pdf' => null,
                        'back_pdf' => null,
                    ];
                }

                // Ensure the product array has the required structure
                if (!isset($updatedProducts[$productIndex])) {
                    $updatedProducts[$productIndex] = [
                        'product_name' => 'Product ' . ($productIndex + 1),
                        'style' => '',
                        'color' => '',
                        'front_pdf' => null,
                        'back_pdf' => null,
                    ];
                }

                // Determine if this is a front or back page
                // Odd pages (1, 3, 5...) are front, Even pages (2, 4, 6...) are back
                $isFront = ($pageNumber % 2 === 1);
                
                // Extract single page with error handling
                try {
                    // Create a new FPDI instance for each page to avoid template issues
                    $pagePdf = new Fpdi();
                    $pagePdf->setSourceFile($tempPath);
                    $tplId = $pagePdf->importPage($i);
                    $size = $pagePdf->getTemplateSize($tplId);
                    
                    if (!$size || !isset($size['width']) || !isset($size['height'])) {
                        \Log::error("Page {$i} has invalid size");
                        $pageNumber++;
                        continue;
                    }
                    
                    $singlePagePdf = new Fpdi();
                    $singlePagePdf->setSourceFile($tempPath);
                    $singlePagePdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $singlePagePdf->useTemplate($tplId);
                    
                    // Save the page
                    $pageFilename = 'mockup-page-' . $record->id . '-' . $pageNumber . '-' . Str::random(10) . '.pdf';
                    $pagePath = $tempDir . '/' . $pageFilename;
                    $pageContent = $singlePagePdf->Output('', 'S');
                    
                    if (empty($pageContent)) {
                        \Log::error("Page {$i} produced empty content");
                        $pageNumber++;
                        continue;
                    }
                    
                    file_put_contents($pagePath, $pageContent);
                    
                    // Move to storage
                    $storagePath = 'mockups/products/' . ($isFront ? 'front' : 'back') . '/' . $pageFilename;
                    Storage::disk('public')->put($storagePath, file_get_contents($pagePath));
                    
                    // Update product array - ensure we're updating the correct product
                    $productName = $updatedProducts[$productIndex]['product_name'] ?? 'Product ' . ($productIndex + 1);
                    if ($isFront) {
                        $updatedProducts[$productIndex]['front_pdf'] = $storagePath;
                        \Log::info("Page {$pageNumber} (front) mapped to Product " . ($productIndex + 1) . " - {$productName}");
                    } else {
                        $updatedProducts[$productIndex]['back_pdf'] = $storagePath;
                        \Log::info("Page {$pageNumber} (back) mapped to Product " . ($productIndex + 1) . " - {$productName}");
                        $productIndex++; // Move to next product after back page
                    }
                    
                    // Clean up temp file
                    @unlink($pagePath);
                    
                } catch (\Exception $e) {
                    \Log::error("Error processing page {$i}: " . $e->getMessage() . " - " . $e->getTraceAsString());
                    // Continue to next page instead of failing completely
                }
                
                $pageNumber++;
            }

            // Log the updated products array before saving
            \Log::info('Updated products array:', $updatedProducts);

            // Update the record - use direct assignment to ensure proper casting
            $record->products = $updatedProducts;
            $record->save();

            session()->flash('success', "Processed {$pageCount} pages and updated " . count($updatedProducts) . " product(s).");
            return redirect()->to(\App\Filament\Resources\MockupsSubmissionResource::getUrl('view', ['record' => $record]));

        } catch (\Exception $e) {
            \Log::error('Mockups Submission - Error processing PDF upload: ' . $e->getMessage());
            session()->flash('error', 'An error occurred while processing the PDF: ' . $e->getMessage());
            return redirect()->to(\App\Filament\Resources\MockupsSubmissionResource::getUrl('view', ['record' => $record]));
        }
    }

    public static function updateProductStatus($recordId)
    {
        try {
            $record = MockupsSubmission::findOrFail($recordId);
            $products = $record->products ?? [];
            
            $productIndex = request()->input('product_index');
            $status = request()->input('status');
            
            if (!isset($products[$productIndex])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }
            
            // Validate status
            $validStatuses = ['Pending', 'Approved', 'Revisions Requested', 'Awaiting Response from Client', 'Removed'];
            if (!in_array($status, $validStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status'
                ], 400);
            }
            
            // Update the product status
            $products[$productIndex]['status'] = $status;
            
            // Save the updated products array
            $record->products = $products;
            $record->save();
            
            // Auto-update completion status based on product statuses
            $record->updateCompletionStatus();
            
            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error updating product status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating status: ' . $e->getMessage()
            ], 500);
        }
    }

    public static function updateProductNotes($recordId)
    {
        try {
            $record = MockupsSubmission::findOrFail($recordId);
            $products = $record->products ?? [];
            
            $productIndex = request()->input('product_index');
            $notes = request()->input('notes');
            
            if (!isset($products[$productIndex])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }
            
            // Update the product notes
            $products[$productIndex]['notes'] = $notes ?? '';
            
            // Save the updated products array
            $record->products = $products;
            $record->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Notes updated successfully'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error updating product notes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating notes: ' . $e->getMessage()
            ], 500);
        }
    }

    public static function saveProduct($recordId)
    {
        try {
            $record = MockupsSubmission::findOrFail($recordId);
            $products = $record->products ?? [];
            
            $productIndex = request()->input('product_index');
            $status = request()->input('status');
            $notes = request()->input('notes');
            $minimums = request()->input('minimums');
            $pricing = request()->input('pricing');
            
            if (!isset($products[$productIndex])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }
            
            // Validate status
            $validStatuses = ['Pending', 'Approved', 'Revisions Requested', 'Awaiting Response from Client', 'Removed'];
            if (!in_array($status, $validStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status'
                ], 400);
            }
            
            // Update status, notes, minimums, and pricing
            $products[$productIndex]['status'] = $status;
            $products[$productIndex]['notes'] = $notes ?? '';
            $products[$productIndex]['minimums'] = $minimums ?? '';
            $products[$productIndex]['pricing'] = $pricing ?? '';
            
            // Save the updated products array
            $record->products = $products;
            $record->save();
            
            // Auto-update completion status based on product statuses
            $record->updateCompletionStatus();
            
            return response()->json([
                'success' => true,
                'message' => 'Product saved successfully'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error saving product: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error saving product: ' . $e->getMessage()
            ], 500);
        }
    }

    public static function uploadProductImage($recordId)
    {
        try {
            $record = MockupsSubmission::findOrFail($recordId);
            $products = $record->products ?? [];
            
            $productIndex = request()->input('product_index');
            $imageType = request()->input('image_type'); // 'front' or 'back'
            $file = request()->file('file');
            
            if (!isset($products[$productIndex])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }
            
            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'No file uploaded'
                ], 400);
            }
            
            // Validate file type
            $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, $allowedExtensions)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid file type. Allowed: PDF, JPG, PNG, GIF, WEBP'
                ], 400);
            }
            
            // Store the file
            $directory = 'mockups/products/' . ($imageType === 'front' ? 'front' : 'back');
            $filename = 'product-' . $record->id . '-' . $productIndex . '-' . $imageType . '-' . time() . '.' . $extension;
            $path = $file->storeAs($directory, $filename, 'public');
            
            // Update the product array
            $fieldName = $imageType === 'front' ? 'front_pdf' : 'back_pdf';
            $products[$productIndex][$fieldName] = $path;
            
            // Save the updated products array
            $record->products = $products;
            $record->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully',
                'path' => $path
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error uploading product image: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error uploading image: ' . $e->getMessage()
            ], 500);
        }
    }

    public static function addComment($recordId)
    {
        try {
            $record = MockupsSubmission::findOrFail($recordId);
            $message = request()->input('message');
            
            if (empty($message)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message cannot be empty'
                ], 400);
            }
            
            $comment = \App\Models\MockupsSubmissionComment::create([
                'mockups_submission_id' => $record->id,
                'user_id' => auth()->id(),
                'message' => $message,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Comment added successfully',
                'comment' => $comment
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error adding comment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error adding comment: ' . $e->getMessage()
            ], 500);
        }
    }

    public static function sendToClient($recordId)
    {
        try {
            $record = MockupsSubmission::findOrFail($recordId);
            
            // Check if phone number is available
            if (empty($record->customer_phone)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer phone number is not available'
                ], 400);
            }

            $templateId = request()->input('template');
            $notes = request()->input('notes', '');
            
            if (empty($templateId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Template is required'
                ], 400);
            }

            // Initialize Twilio service
            try {
                $twilioService = new \App\Services\TwilioService();
            } catch (\Exception $e) {
                \Log::error('Twilio service initialization failed: ' . $e->getMessage());
                // Return success but log that SMS wasn't sent
                \Log::info('SMS not sent - Twilio not configured', [
                    'submission_id' => $record->id,
                    'customer_phone' => $record->customer_phone,
                    'customer_name' => $record->customer_name,
                    'tracking_number' => $record->tracking_number,
                    'template_id' => $templateId,
                    'notes' => $notes
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Submission logged successfully. Note: SMS service is not configured, so no SMS was sent.'
                ]);
            }

            // Validate phone number
            if (!$twilioService->isValidPhoneNumber($record->customer_phone)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid phone number format'
                ], 400);
            }

            // Get template from database
            $template = \App\Models\SmsTemplate::find($templateId);
            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => 'Template not found'
                ], 404);
            }
            
            // Generate submission link - use absolute URL for SMS
            $submissionLink = url(\App\Filament\Resources\MockupsSubmissionResource::getUrl('view', ['record' => $record]));
            
            // Build SMS message using template
            $message = $template->render([
                'customer_name' => $record->customer_name ?? 'Valued Customer',
                'tracking_number' => $record->tracking_number ?? '',
                'company_name' => $record->company_name ?? '',
                'notes' => $notes,
                'submission_link' => $submissionLink,
            ]);

            // Send SMS
            $twilioMessage = $twilioService->sendSMS($record->customer_phone, $message);

            \Log::info('Mockup submission sent to client via SMS', [
                'submission_id' => $record->id,
                'customer_phone' => $record->customer_phone,
                'customer_name' => $record->customer_name,
                'tracking_number' => $record->tracking_number,
                'template_id' => $templateId,
                'twilio_message_sid' => $twilioMessage->sid,
                'notes' => $notes
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Submission sent to client successfully via SMS',
                'twilio_message_sid' => $twilioMessage->sid
            ]);
            
        } catch (\Twilio\Exceptions\TwilioException $e) {
            \Log::error('Twilio error sending submission to client: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error sending SMS: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            \Log::error('Error sending submission to client: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error sending submission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active SMS templates
     */
    public static function getSmsTemplates()
    {
        try {
            $templates = \App\Models\SmsTemplate::orderBy('name')
                ->get(['id', 'name', 'description', 'content']);
            
            return response()->json([
                'success' => true,
                'templates' => $templates
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching SMS templates: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'templates' => []
            ], 500);
        }
    }

    public static function importData($recordId)
    {
        try {
            $record = MockupsSubmission::findOrFail($recordId);
            $data = request()->json()->all();
            
            // Update customer information if provided
            if (isset($data['customer_name'])) {
                $record->customer_name = $data['customer_name'];
            }
            if (isset($data['customer_email'])) {
                $record->customer_email = $data['customer_email'];
            }
            if (isset($data['customer_phone'])) {
                $record->customer_phone = $data['customer_phone'];
            }
            if (isset($data['company_name'])) {
                $record->company_name = $data['company_name'];
            }
            if (isset($data['website'])) {
                $record->website = $data['website'];
            }
            if (isset($data['instagram'])) {
                $record->instagram = $data['instagram'];
            }
            if (isset($data['notes'])) {
                $record->notes = $data['notes'];
            }
            
            // Handle products import
            if (isset($data['products']) && is_array($data['products'])) {
                $existingProducts = $record->products ?? [];
                $importedProducts = [];
                
                foreach ($data['products'] as $index => $productData) {
                    // Create product structure
                    $product = [
                        'product_name' => $productData['product_name'] ?? 'Product ' . ($index + 1),
                        'style' => $productData['style'] ?? '',
                        'color' => $productData['color'] ?? '',
                        'minimums' => $productData['minimums'] ?? '',
                        'pricing' => $productData['pricing'] ?? '',
                        'notes' => $productData['notes'] ?? '',
                        'status' => $productData['status'] ?? 'Pending',
                        'front_pdf' => $productData['front_pdf'] ?? null,
                        'back_pdf' => $productData['back_pdf'] ?? null,
                    ];
                    
                    $importedProducts[] = $product;
                }
                
                // If products array is provided, replace existing products
                // Otherwise, merge with existing
                if (isset($data['replace_products']) && $data['replace_products']) {
                    $record->products = $importedProducts;
                } else {
                    // Merge with existing products
                    $record->products = array_merge($existingProducts, $importedProducts);
                }
            }
            
            $record->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Data imported successfully',
                'products_count' => count($record->products ?? [])
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error importing submission data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error importing data: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            // Header actions removed - starting fresh
        ];
    }

    public $pdfFile;
    public $showUploadPdfModal = false;

    public function openUploadPdfModal(): void
    {
        $this->showUploadPdfModal = true;
    }

    public function closeUploadPdfModal(): void
    {
        $this->showUploadPdfModal = false;
        $this->pdfFile = null;
    }

    public function updateStatus(bool $isCompleted): void
    {
        $record = $this->getRecord();
        
        // If trying to close, check if all products are Approved or Removed
        if ($isCompleted && !$record->allProductsCompleted()) {
            Notification::make()
                ->title('Cannot Close Submission')
                ->body('All products must be marked as "Approved" or "Removed" before closing the submission.')
                ->warning()
                ->send();
            return;
        }
        
        $record->update([
            'is_completed' => $isCompleted,
            'completed_at' => $isCompleted ? now() : null,
        ]);

        Notification::make()
            ->title('Status Updated')
            ->body('Mockup status has been updated successfully.')
            ->success()
            ->send();

        $this->dispatch('status-updated');
    }

    public function processPdfUpload()
    {
        try {
            $record = $this->getRecord();
            $products = $record->products ?? [];
            
            if (empty($products)) {
                Notification::make()
                    ->title('No Products Found')
                    ->body('Please add products before uploading PDF.')
                    ->warning()
                    ->send();
                $this->pdfFile = null;
                return;
            }

            if (!$this->pdfFile) {
                Notification::make()
                    ->title('No File Selected')
                    ->body('Please select a PDF file to upload.')
                    ->warning()
                    ->send();
                return;
            }

            // Get the uploaded file path
            $tempPath = $this->pdfFile->getRealPath();
            
            if (!file_exists($tempPath)) {
                // Try Livewire temp path
                $tempPath = Storage::disk('public')->path('livewire-tmp/' . $this->pdfFile->getFilename());
                
                if (!file_exists($tempPath)) {
                    Notification::make()
                        ->title('File Not Found')
                        ->body('The uploaded file could not be found.')
                        ->danger()
                        ->send();
                    $this->pdfFile = null;
                    return;
                }
            }

            // Split PDF into individual pages
            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($tempPath);
            
            if ($pageCount === 0) {
                Notification::make()
                    ->title('Invalid PDF')
                    ->body('The PDF file appears to be empty or invalid.')
                    ->danger()
                    ->send();
                return;
            }

            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $updatedProducts = $products;
            $productIndex = 0;
            $pageNumber = 1;

            // Process pages: Page 1 → Product 1 front, Page 2 → Product 1 back, Page 3 → Product 2 front, etc.
            for ($i = 1; $i <= $pageCount; $i++) {
                if ($productIndex >= count($updatedProducts)) {
                    // Create new product entry if we run out
                    $updatedProducts[] = [
                        'product_name' => 'Product ' . (count($updatedProducts) + 1),
                        'style' => '',
                        'color' => '',
                        'front_pdf' => null,
                        'back_pdf' => null,
                    ];
                }

                // Determine if this is a front or back page
                // Odd pages (1, 3, 5...) are front, Even pages (2, 4, 6...) are back
                $isFront = ($pageNumber % 2 === 1);
                
                // Extract single page
                $tplId = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($tplId);
                
                $singlePagePdf = new Fpdi();
                $singlePagePdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $singlePagePdf->useTemplate($tplId);
                
                // Save the page
                $pageFilename = 'mockup-page-' . $record->id . '-' . $pageNumber . '-' . Str::random(10) . '.pdf';
                $pagePath = $tempDir . '/' . $pageFilename;
                file_put_contents($pagePath, $singlePagePdf->Output('', 'S'));
                
                // Move to storage
                $storagePath = 'mockups/products/' . ($isFront ? 'front' : 'back') . '/' . $pageFilename;
                Storage::disk('public')->put($storagePath, file_get_contents($pagePath));
                
                // Update product array
                if ($isFront) {
                    $updatedProducts[$productIndex]['front_pdf'] = $storagePath;
                } else {
                    $updatedProducts[$productIndex]['back_pdf'] = $storagePath;
                    $productIndex++; // Move to next product after back page
                }
                
                // Clean up temp file
                @unlink($pagePath);
                
                $pageNumber++;
            }

            // Update the record
            $record->update([
                'products' => $updatedProducts,
            ]);

            // Clean up uploaded temp file
            @unlink($tempPath);
            if ($this->pdfFile instanceof TemporaryUploadedFile) {
                $this->pdfFile->delete();
            }
            $this->pdfFile = null;

            $this->closeUploadPdfModal();

            Notification::make()
                ->title('PDF Uploaded Successfully')
                ->body("Processed {$pageCount} pages and updated " . count($updatedProducts) . " product(s).")
                ->success()
                ->send();

            $this->dispatch('$refresh');

        } catch (\Exception $e) {
            \Log::error('Mockups Submission - Error processing PDF upload: ' . $e->getMessage());
            
            Notification::make()
                ->title('Error Processing PDF')
                ->body('An error occurred while processing the PDF: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected static string $view = 'filament.resources.mockups-submission-resource.pages.view-mockups-submission';

    protected function getViewData(): array
    {
        // Eager load comments with user relationship
        $this->record->load(['comments.user']);
        return [];
    }

    protected function downloadProductsCad()
    {
        $record = $this->getRecord();
        $products = $record->getMappedProducts();

        if ($products->isEmpty()) {
            Notification::make()
                ->title('No Products Found')
                ->body('No matching products with CAD files found for this submission.')
                ->warning()
                ->send();
            return null;
        }

        try {
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $mergedPdf = new Fpdi();
            $totalPagesAdded = 0;
            $processedCount = 0;

            foreach ($products as $product) {
                $cadPath = $this->getCadFilePath($product, $tempDir);
                if ($cadPath && file_exists($cadPath)) {
                    try {
                        // Check if file is an image
                        $mimeType = mime_content_type($cadPath);
                        if (str_starts_with($mimeType, 'image/')) {
                            $cadPath = $this->convertImageToPdf($cadPath, $product, $tempDir);
                            if (!$cadPath) {
                                continue;
                            }
                        }

                        $pageCount = $mergedPdf->setSourceFile($cadPath);
                        $totalPagesAdded += $pageCount;

                        for ($i = 1; $i <= $pageCount; $i++) {
                            $tplId = $mergedPdf->importPage($i);
                            $size = $mergedPdf->getTemplateSize($tplId);
                            $mergedPdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                            $mergedPdf->useTemplate($tplId);
                        }

                        $processedCount++;

                        // Clean up downloaded CAD file if it's in temp directory
                        if (str_starts_with($cadPath, $tempDir)) {
                            @unlink($cadPath);
                        }
                    } catch (\Exception $e) {
                        \Log::error('Mockups Submission - Error processing product ' . $product->id . ': ' . $e->getMessage());
                        continue;
                    }
                }
            }

            if ($processedCount === 0) {
                Notification::make()
                    ->title('No CAD Files Found')
                    ->body('No valid CAD files could be processed for the products in this submission.')
                    ->warning()
                    ->send();
                return null;
            }

            $filename = 'mockup-submission-' . $record->tracking_number . '-' . date('Y-m-d-His') . '.pdf';

            Notification::make()
                ->title('Download Started')
                ->body("Processing {$processedCount} products with {$totalPagesAdded} total pages.")
                ->success()
                ->send();

            return response()->streamDownload(function () use ($mergedPdf) {
                echo $mergedPdf->Output('', 'S');
            }, $filename, ['Content-Type' => 'application/pdf']);

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Could not generate PDF: ' . $e->getMessage())
                ->danger()
                ->send();
            return null;
        }
    }

    private function getCadFilePath($product, $tempDir): ?string
    {
        try {
            $mediaFiles = $product->getMedia('cad_download');
            if ($mediaFiles->isNotEmpty()) {
                $path = $mediaFiles->first()->getPath();
                if (file_exists($path)) {
                    return $path;
                }
            }

            $cadUrl = $product->cad_download;
            if (!$cadUrl) {
                return null;
            }

            if (filter_var($cadUrl, FILTER_VALIDATE_URL)) {
                $response = Http::timeout(30)->get($cadUrl);
                if ($response->successful()) {
                    $extension = pathinfo(parse_url($cadUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'pdf';
                    $tempPath = $tempDir . '/cad-' . Str::random(10) . '.' . $extension;
                    file_put_contents($tempPath, $response->body());
                    return $tempPath;
                }
            } else {
                $paths = [
                    storage_path('app/public/' . $cadUrl),
                    public_path($cadUrl),
                    str_starts_with($cadUrl, '/') ? $cadUrl : null,
                ];

                foreach ($paths as $path) {
                    if ($path && file_exists($path)) {
                        return $path;
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error('Mockups Submission - Error getting CAD path for product ' . $product->id . ': ' . $e->getMessage());
        }

        return null;
    }

    private function convertImageToPdf(string $imagePath, Product $product, string $tempDir): ?string
    {
        try {
            $pdf = Pdf::loadView('filament.widgets.image-to-pdf', [
                'imagePath' => $imagePath,
                'productName' => $product->name,
            ])
                ->setPaper('letter', 'portrait')
                ->setOption('isRemoteEnabled', true);

            $pdfPath = $tempDir . '/image-' . $product->id . '-' . Str::random(10) . '.pdf';
            file_put_contents($pdfPath, $pdf->output());

            return $pdfPath;
        } catch (\Exception $e) {
            \Log::error('Mockups Submission - Error converting image to PDF for product ' . $product->id . ': ' . $e->getMessage());
            return null;
        }
    }
}
