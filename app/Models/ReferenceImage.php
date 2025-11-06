<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferenceImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'gallery_images',
    ];

    protected $casts = [
        'gallery_images' => 'array',
    ];

    /**
     * Get the gallery_images as an array of objects with url and description.
     */
    public function getGalleryImagesAttribute($value)
    {
        if (is_array($value)) {
            return $value ?: [];
        }
        
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $result = [];
                foreach ($decoded as $item) {
                    if (is_string($item)) {
                        $result[] = ['url' => $item, 'description' => ''];
                    } elseif (is_array($item) && isset($item['url'])) {
                        $result[] = [
                            'url' => $item['url'] ?? '',
                            'description' => $item['description'] ?? ''
                        ];
                    }
                }
                return $result;
            }
        }
        
        return [];
    }

    /**
     * Set the gallery_images attribute - ensure it's stored as JSON array.
     */
    public function setGalleryImagesAttribute($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->attributes['gallery_images'] = json_encode($decoded);
            } else {
                $this->attributes['gallery_images'] = json_encode([]);
            }
        } elseif (is_array($value)) {
            $this->attributes['gallery_images'] = json_encode($value);
        } else {
            $this->attributes['gallery_images'] = json_encode([]);
        }
    }
}
