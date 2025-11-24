<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsTemplate extends Model
{
    protected $fillable = [
        'name',
        'content',
        'description',
    ];

    /**
     * Replace template variables with actual values
     */
    public function render(array $variables = []): string
    {
        $content = $this->content;
        
        // Default variables
        $defaults = [
            'customer_name' => $variables['customer_name'] ?? 'Valued Customer',
            'tracking_number' => $variables['tracking_number'] ?? '',
            'company_name' => $variables['company_name'] ?? '',
            'notes' => $variables['notes'] ?? '',
            'submission_link' => $variables['submission_link'] ?? '',
        ];
        
        // Handle conditional blocks like {{#variable}}...{{/variable}}
        $conditionalVariables = ['company_name', 'notes', 'tracking_number', 'submission_link'];
        
        foreach ($conditionalVariables as $var) {
            $pattern = '/\{\{#' . preg_quote($var, '/') . '\}\}(.*?)\{\{\/' . preg_quote($var, '/') . '\}\}/s';
            
            if (empty($defaults[$var])) {
                // Remove the entire block if variable is empty
                $content = preg_replace($pattern, '', $content);
            } else {
                // Remove the conditional tags but keep the content
                $content = preg_replace('/\{\{#' . preg_quote($var, '/') . '\}\}/', '', $content);
                $content = preg_replace('/\{\{\/' . preg_quote($var, '/') . '\}\}/', '', $content);
            }
        }
        
        // Replace variables in format {{variable_name}}
        foreach ($defaults as $key => $value) {
            // Only replace if value is not empty, or if it's customer_name (always show)
            if ($key === 'customer_name' || !empty($value)) {
                $content = str_replace('{{' . $key . '}}', $value, $content);
            } else {
                // Remove standalone variable if empty (except customer_name)
                $content = preg_replace('/\{\{' . preg_quote($key, '/') . '\}\}/', '', $content);
            }
        }
        
        return trim($content);
    }
}
