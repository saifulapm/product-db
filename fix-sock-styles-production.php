<?php

/**
 * Production Fix Script for Sock Styles 500 Error
 * 
 * This script checks and fixes the sock_styles table structure
 * Run this on production: php fix-sock-styles-production.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "Checking sock_styles table structure...\n\n";

// Check if table exists
if (!Schema::hasTable('sock_styles')) {
    echo "ERROR: sock_styles table does not exist!\n";
    echo "Please run migrations: php artisan migrate\n";
    exit(1);
}

// Check for required columns
$columns = Schema::getColumnListing('sock_styles');
$requiredColumns = ['id', 'name', 'packaging_style', 'is_active', 'created_at', 'updated_at'];
$missingColumns = array_diff($requiredColumns, $columns);

if (!empty($missingColumns)) {
    echo "WARNING: Missing columns: " . implode(', ', $missingColumns) . "\n\n";
    
    if (in_array('packaging_style', $missingColumns)) {
        echo "Adding packaging_style column...\n";
        try {
            Schema::table('sock_styles', function ($table) {
                $table->string('packaging_style')->nullable()->after('name');
            });
            echo "✓ packaging_style column added successfully\n\n";
        } catch (\Exception $e) {
            echo "ERROR adding column: " . $e->getMessage() . "\n";
            echo "Please run: php artisan migrate\n";
            exit(1);
        }
    }
    
    // Check for other missing columns
    if (in_array('is_active', $missingColumns)) {
        echo "Adding is_active column...\n";
        try {
            Schema::table('sock_styles', function ($table) {
                $table->boolean('is_active')->default(true)->after('packaging_style');
            });
            echo "✓ is_active column added successfully\n\n";
        } catch (\Exception $e) {
            echo "ERROR adding column: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
} else {
    echo "✓ All required columns exist\n\n";
}

// Verify table structure
echo "Current table structure:\n";
$columns = Schema::getColumnListing('sock_styles');
foreach ($columns as $column) {
    $type = DB::select("SHOW COLUMNS FROM sock_styles WHERE Field = ?", [$column]);
    if (!empty($type)) {
        echo "  - {$column}: {$type[0]->Type}\n";
    }
}

echo "\n✓ Table structure verified\n";
echo "\nIf issues persist, check:\n";
echo "1. Run migrations: php artisan migrate\n";
echo "2. Clear cache: php artisan cache:clear && php artisan config:clear\n";
echo "3. Check Laravel logs: storage/logs/laravel.log\n";

