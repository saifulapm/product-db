<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GoogleDriveImageService
{
    private $apiKey;

    public function __construct()
    {
        $this->apiKey = env('GOOGLE_API_KEY');
        if (!$this->apiKey) {
            throw new \Exception('GOOGLE_API_KEY not found in environment variables');
        }
    }

    public function getImageUrlForThreadColor($threadColorCode)
    {
        try {
            // Use Google Drive public folder access
            $folderId = '1RDevqNIwqVJixqs4VwJGb3GsakeP5kgl';
            
            // Try to get the image URL using the public folder structure
            $imageUrl = $this->constructImageUrl($folderId, $threadColorCode);
            
            // Test if the image exists
            if ($this->testImageUrl($imageUrl)) {
                return $imageUrl;
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Error getting image for thread color {$threadColorCode}: " . $e->getMessage());
            return null;
        }
    }

    public function downloadAndStoreImage($threadColorCode)
    {
        try {
            $imageUrl = $this->getImageUrlForThreadColor($threadColorCode);
            
            if (!$imageUrl) {
                return null;
            }

            // Download the image
            $imageContent = file_get_contents($imageUrl);
            if (!$imageContent) {
                return null;
            }

            // Determine file extension
            $extension = 'png'; // default based on your folder
            
            // Store the image locally
            $filename = $threadColorCode . '.' . $extension;
            $path = 'thread-colors/' . $filename;
            
            Storage::disk('public')->put($path, $imageContent);
            
            return $path;
        } catch (\Exception $e) {
            Log::error("Error downloading image for thread color {$threadColorCode}: " . $e->getMessage());
            return null;
        }
    }

    private function constructImageUrl($folderId, $threadColorCode)
    {
        // Construct Google Drive direct download URL
        // Format: https://drive.google.com/uc?export=download&id=FILE_ID
        // But we need to find the file ID first
        
        // Alternative approach: Use the folder's public sharing
        // Since your folder is shared, we can try to access files directly
        $baseUrl = "https://drive.google.com/file/d/";
        $fileId = $this->findFileIdInFolder($folderId, $threadColorCode);
        
        if ($fileId) {
            return "https://drive.google.com/uc?export=download&id=" . $fileId;
        }
        
        return null;
    }

    private function findFileIdInFolder($folderId, $threadColorCode)
    {
        try {
            // Use Google Drive API v3 to list files in the folder
            $response = Http::get("https://www.googleapis.com/drive/v3/files", [
                'q' => "'{$folderId}' in parents and name contains '{$threadColorCode}' and trashed=false",
                'key' => $this->apiKey,
                'fields' => 'files(id,name)'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['files'])) {
                    return $data['files'][0]['id'];
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Error finding file ID: " . $e->getMessage());
            return null;
        }
    }

    private function testImageUrl($url)
    {
        try {
            $headers = get_headers($url, 1);
            return strpos($headers[0], '200') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function testConnection()
    {
        try {
            $folderId = '1RDevqNIwqVJixqs4VwJGb3GsakeP5kgl';
            
            $response = Http::get("https://www.googleapis.com/drive/v3/files", [
                'q' => "'{$folderId}' in parents and trashed=false",
                'key' => $this->apiKey,
                'fields' => 'files(id,name,mimeType)'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $files = $data['files'] ?? [];
                $imageFiles = array_filter($files, function($file) {
                    return strpos($file['mimeType'], 'image/') === 0;
                });

                return [
                    'success' => true,
                    'totalFiles' => count($files),
                    'imageFiles' => count($imageFiles),
                    'sampleFiles' => array_map(function($file) {
                        return [
                            'name' => $file['name'],
                            'id' => $file['id']
                        ];
                    }, array_slice($imageFiles, 0, 10))
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response->body()
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}










