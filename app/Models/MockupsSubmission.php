<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MockupsSubmission extends Model
{
    protected $fillable = [
        'tracking_number',
        'submission_date',
        'title',
        'customer_name',
        'customer_email',
        'customer_phone',
        'website',
        'company_name',
        'instagram',
        'notes',
        'products',
        'graphics',
        'pdfs',
        'assigned_to',
        'created_by',
        'is_completed',
        'completed_at',
    ];

    protected $casts = [
        'submission_date' => 'date',
        'products' => 'array',
        'graphics' => 'array',
        'pdfs' => 'array',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user assigned to this submission.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who created this submission.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the comments for this submission.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(MockupsSubmissionComment::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get mapped Product models from the products JSON array.
     * Matches product names from mockup submission to actual Product records.
     */
    public function getMappedProducts()
    {
        $products = $this->products ?? [];
        $mappedProducts = collect();

        foreach ($products as $mockupProduct) {
            $productName = $mockupProduct['product_name'] ?? null;
            if (!$productName) {
                continue;
            }

            // Try to find exact match first
            $product = \App\Models\Product::where('name', $productName)
                ->where(function ($query) {
                    $query->whereNotNull('cad_download')
                        ->orWhereHas('media', function ($q) {
                            $q->where('collection_name', 'cad_download');
                        });
                })
                ->first();

            // If no exact match, try partial match
            if (!$product) {
                $product = \App\Models\Product::where('name', 'like', '%' . $productName . '%')
                    ->where(function ($query) {
                        $query->whereNotNull('cad_download')
                            ->orWhereHas('media', function ($q) {
                                $q->where('collection_name', 'cad_download');
                            });
                    })
                    ->first();
            }

            if ($product) {
                $mappedProducts->push($product);
            }
        }

        return $mappedProducts;
    }

    /**
     * Get product status counts
     */
    public function getProductStatusCounts(): array
    {
        $products = $this->products ?? [];
        $counts = [
            'Pending' => 0,
            'Approved' => 0,
            'Revisions Requested' => 0,
            'Awaiting Response from Client' => 0,
            'Removed' => 0,
        ];

        foreach ($products as $product) {
            $status = $product['status'] ?? 'Pending';
            if (isset($counts[$status])) {
                $counts[$status]++;
            } else {
                $counts['Pending']++;
            }
        }

        return $counts;
    }

    /**
     * Check if all products are either Approved or Removed
     */
    public function allProductsCompleted(): bool
    {
        $products = $this->products ?? [];
        
        if (empty($products)) {
            return false; // No products means not completed
        }

        foreach ($products as $product) {
            $status = $product['status'] ?? 'Pending';
            if ($status !== 'Approved' && $status !== 'Removed') {
                return false;
            }
        }

        return true;
    }

    /**
     * Update completion status based on product statuses
     */
    public function updateCompletionStatus(): void
    {
        $shouldBeCompleted = $this->allProductsCompleted();
        
        if ($shouldBeCompleted && !$this->is_completed) {
            $this->is_completed = true;
            $this->completed_at = now();
            $this->save();
        } elseif (!$shouldBeCompleted && $this->is_completed) {
            // If products are not all completed but status is closed, reopen it
            $this->is_completed = false;
            $this->completed_at = null;
            $this->save();
        }
    }
}
