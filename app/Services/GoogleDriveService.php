<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GoogleDriveService
{
    private $client;
    private $driveService;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setApplicationName('Thread Colors Importer');
        
        // Use API key authentication
        $apiKey = env('GOOGLE_API_KEY');
        if (!$apiKey) {
            throw new \Exception('GOOGLE_API_KEY not found in environment variables');
        }
        
        $this->client->setDeveloperKey($apiKey);
        $this->driveService = new Drive($this->client);
    }

    public function getFolderContents($folderId)
    {
        try {
            $response = $this->driveService->files->listFiles([
                'q' => "'{$folderId}' in parents and trashed=false",
                'fields' => 'files(id,name,mimeType,size,webContentLink,thumbnailLink)'
            ]);

            return $response->getFiles();
        } catch (\Exception $e) {
            Log::error('Google Drive API Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getImageForThreadColor($folderId, $threadColorCode)
    {
        try {
            $files = $this->getFolderContents($folderId);
            
            // Look for files that match the thread color code
            foreach ($files as $file) {
                $filename = pathinfo($file->getName(), PATHINFO_FILENAME);
                $extension = strtolower(pathinfo($file->getName(), PATHINFO_EXTENSION));
                
                // Check if filename matches thread color code and is an image
                if (($filename === $threadColorCode || $filename === (string)$threadColorCode) 
                    && in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                    
                    return [
                        'id' => $file->getId(),
                        'name' => $file->getName(),
                        'url' => $file->getWebContentLink(),
                        'thumbnail' => $file->getThumbnailLink()
                    ];
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Error getting image for thread color {$threadColorCode}: " . $e->getMessage());
            return null;
        }
    }

    public function downloadAndStoreImage($folderId, $threadColorCode)
    {
        try {
            $imageInfo = $this->getImageForThreadColor($folderId, $threadColorCode);
            
            if (!$imageInfo) {
                return null;
            }

            // Download the image using the file ID
            $imageContent = $this->driveService->files->get($imageInfo['id'], [
                'alt' => 'media'
            ])->getBody()->getContents();

            if (!$imageContent) {
                return null;
            }

            // Determine file extension from filename
            $extension = strtolower(pathinfo($imageInfo['name'], PATHINFO_EXTENSION));
            
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

    public function testConnection($folderId)
    {
        try {
            $files = $this->getFolderContents($folderId);
            $imageFiles = array_filter($files, function($file) {
                $extension = strtolower(pathinfo($file->getName(), PATHINFO_EXTENSION));
                return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
            });

            return [
                'success' => true,
                'totalFiles' => count($files),
                'imageFiles' => count($imageFiles),
                'sampleFiles' => array_map(function($file) {
                    return [
                        'name' => $file->getName(),
                        'size' => $file->getSize(),
                        'type' => $file->getMimeType()
                    ];
                }, array_slice($imageFiles, 0, 10)) // Show first 10 image files
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function extractFolderIdFromUrl($url)
    {
        // Extract folder ID from Google Drive URL
        // Examples:
        // https://drive.google.com/drive/folders/1RDevqNIwqVJixqs4VwJGb3GsakeP5kgl?usp=drive_link
        // https://drive.google.com/drive/folders/1RDevqNIwqVJixqs4VwJGb3GsakeP5kgl
        
        if (preg_match('/drive\.google\.com\/drive\/folders\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
}










