<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ShootModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncModelsFromJotForm extends Command
{
    protected $signature = 'models:sync-from-jotform {--form-id=253424538550053} {--api-key=}';
    
    protected $description = 'Sync models from JotForm form submissions';
    
    private $defaultFormId = '253424538550053';
    private $jotFormApiBase = 'https://api.jotform.com';

    public function handle()
    {
        $this->info('Starting sync of models from JotForm...');
        
        $formId = $this->option('form-id') ?: $this->defaultFormId;
        $apiKey = $this->option('api-key') ?: env('JOTFORM_API_KEY');
        
        if (!$apiKey) {
            $this->error('JotForm API key is required. Set JOTFORM_API_KEY in .env or use --api-key option');
            return 1;
        }
        
        try {
            // Fetch form submissions
            $this->info("Fetching submissions from form ID: {$formId}");
            $submissions = $this->fetchFormSubmissions($formId, $apiKey);
            
            if (empty($submissions)) {
                $this->warn('No submissions found');
                return 0;
            }
            
            $this->info("Found " . count($submissions) . " submissions");
            
            $created = 0;
            $updated = 0;
            $skipped = 0;
            
            // Process each submission
            foreach ($submissions as $submission) {
                try {
                    $result = $this->processSubmission($submission, $formId);
                    if ($result === 'created') {
                        $created++;
                    } elseif ($result === 'updated') {
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } catch (\Exception $e) {
                    $this->error("Error processing submission {$submission['id']}: " . $e->getMessage());
                    Log::error("JotForm sync error for submission {$submission['id']}: " . $e->getMessage());
                    $skipped++;
                }
            }
            
            $this->info("\nSync completed!");
            $this->info("Created: {$created}");
            $this->info("Updated: {$updated}");
            $this->info("Skipped: {$skipped}");
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Sync failed: " . $e->getMessage());
            Log::error("JotForm sync error: " . $e->getMessage());
            return 1;
        }
    }
    
    private function fetchFormSubmissions($formId, $apiKey)
    {
        $response = Http::withHeaders([
            'APIKEY' => $apiKey,
        ])->get("{$this->jotFormApiBase}/form/{$formId}/submissions", [
            'limit' => 1000,
            'orderby' => 'created_at',
        ]);
        
        if (!$response->successful()) {
            throw new \Exception("Failed to fetch submissions: " . $response->body());
        }
        
        $data = $response->json();
        return $data['content'] ?? [];
    }
    
    private function processSubmission($submission, $formId)
    {
        $answers = $submission['answers'] ?? [];
        
        // Map JotForm answers to our database fields
        $modelData = [
            'first_name' => $this->getAnswerValue($answers, 'first_name') ?? $this->getAnswerValue($answers, 'name', 'first'),
            'last_name' => $this->getAnswerValue($answers, 'last_name') ?? $this->getAnswerValue($answers, 'name', 'last'),
            'email' => $this->getAnswerValue($answers, 'email'),
            'phone_number' => $this->getAnswerValue($answers, 'phone_number') ?? $this->getAnswerValue($answers, 'phone'),
            'social_media' => $this->getAnswerValue($answers, 'social_media'),
            'coffee_order' => $this->getAnswerValue($answers, 'coffee_order'),
            'food_allergies' => $this->getAnswerValue($answers, 'food_allergies'),
            'height' => $this->getAnswerValue($answers, 'height'),
        ];
        
        // Build full name
        $nameParts = array_filter([$modelData['first_name'], $modelData['last_name']]);
        $modelData['name'] = !empty($nameParts) ? implode(' ', $nameParts) : ($modelData['email'] ?? 'Unknown');
        
        // Parse tops_size and bottoms_size (handle arrays)
        $topsSize = $this->getAnswerValue($answers, 'tops_size');
        if ($topsSize) {
            $modelData['tops_size'] = is_array($topsSize) ? $topsSize : explode(',', $topsSize);
        }
        
        $bottomsSize = $this->getAnswerValue($answers, 'bottoms_size');
        if ($bottomsSize) {
            $modelData['bottoms_size'] = is_array($bottomsSize) ? $bottomsSize : explode(',', $bottomsSize);
        }
        
        // Parse availability (handle arrays)
        $availability = $this->getAnswerValue($answers, 'availability');
        if ($availability) {
            $modelData['availability'] = is_array($availability) ? $availability : explode(',', $availability);
        }
        
        // Handle selfie upload
        $selfieAnswer = $this->getAnswerValue($answers, 'selfie', null, true);
        if ($selfieAnswer && isset($selfieAnswer['url'])) {
            $modelData['selfie_url'] = $selfieAnswer['url'];
        } elseif ($selfieAnswer && is_string($selfieAnswer)) {
            $modelData['selfie_url'] = $selfieAnswer;
        }
        
        // Parse submission date
        if (isset($submission['created_at'])) {
            try {
                $modelData['submission_date'] = Carbon::createFromTimestamp($submission['created_at']);
            } catch (\Exception $e) {
                $modelData['submission_date'] = Carbon::now();
            }
        } else {
            $modelData['submission_date'] = Carbon::now();
        }
        
        // Create unique identifier for this submission
        $submissionId = $submission['id'] ?? md5($modelData['email'] . $modelData['submission_date']->toIso8601String());
        
        // Check if model already exists
        $existingModel = ShootModel::where('email', $modelData['email'])
            ->orWhere(function($query) use ($modelData, $submissionId) {
                // Also check by submission ID if we store it
                $query->where('name', $modelData['name'])
                      ->where('submission_date', $modelData['submission_date']);
            })
            ->first();
        
        if ($existingModel) {
            // Update existing model
            foreach ($modelData as $key => $value) {
                if ($value !== null) {
                    $existingModel->$key = $value;
                }
            }
            $existingModel->save();
            $this->line("Updated: {$modelData['name']} ({$modelData['email']})");
            return 'updated';
        } else {
            // Create new model
            ShootModel::create($modelData);
            $this->info("Created: {$modelData['name']} ({$modelData['email']})");
            return 'created';
        }
    }
    
    private function getAnswerValue($answers, $fieldName, $subField = null, $returnFull = false)
    {
        // JotForm answers are keyed by question ID, but we need to search by name/text
        foreach ($answers as $key => $answer) {
            $name = $answer['name'] ?? '';
            $text = $answer['text'] ?? '';
            $answerValue = $answer['answer'] ?? null;
            
            // Check if this is the field we're looking for (case-insensitive)
            $nameMatch = stripos($name, $fieldName) !== false;
            $textMatch = stripos($text, $fieldName) !== false;
            $keyMatch = stripos($key, $fieldName) !== false;
            
            if ($nameMatch || $textMatch || $keyMatch) {
                if ($subField) {
                    // Handle sub-fields like "name" -> "first" or "last"
                    if (isset($answer[$subField])) {
                        return $answer[$subField];
                    }
                    // Check if answer is an object/array with sub-field
                    if (is_array($answerValue) && isset($answerValue[$subField])) {
                        return $answerValue[$subField];
                    }
                }
                
                if ($returnFull) {
                    return $answer;
                }
                
                // Return the answer value
                if (is_array($answerValue)) {
                    // If it's an array, return it as-is (for multi-select fields)
                    return $answerValue;
                }
                return $answerValue ?? $text ?? null;
            }
        }
        
        return null;
    }
}
