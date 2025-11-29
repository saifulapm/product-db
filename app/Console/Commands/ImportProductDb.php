<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;

class ImportProductDb extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:product-db';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import products from product-db SQLite database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sqliteFile = database_path('product-db-latest.sqlite');
        
        if (!file_exists($sqliteFile)) {
            $this->error("SQLite file not found: {$sqliteFile}");
            return 1;
        }
        
        try {
            $sqlite = new PDO("sqlite:{$sqliteFile}");
            $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $this->info("Connected to SQLite database");
            
            // Get all products
            $stmt = $sqlite->query("SELECT * FROM products");
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->info("Found " . count($products) . " products to import");
            
            $imported = 0;
            $updated = 0;
            $skipped = 0;
            
            $bar = $this->output->createProgressBar(count($products));
            $bar->start();
            
            DB::beginTransaction();
            
            foreach ($products as $product) {
                try {
                    $data = $this->mapProductData($product);
                    
                    // Check if product already exists
                    $existing = DB::table('products')->where('sku', $data['sku'])->first();
                    
                    if ($existing) {
                        // Update existing product
                        DB::table('products')
                            ->where('sku', $data['sku'])
                            ->update($data);
                        $updated++;
                    } else {
                        // Insert new product
                        DB::table('products')->insert($data);
                        $imported++;
                    }
                    
                    $bar->advance();
                } catch (\Exception $e) {
                    $skipped++;
                    // Continue with next product on error
                    $bar->advance();
                    continue;
                }
            }
            
            DB::commit();
            $bar->finish();
            
            $this->newLine(2);
            $this->info("=== IMPORT SUMMARY ===");
            $this->info("Imported: {$imported}");
            $this->info("Updated: {$updated}");
            $this->info("Skipped: {$skipped}");
            $this->info("Total: " . count($products));
            
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
    
    private function mapProductData($product)
    {
        return [
            'name' => $product['name'] ?? '',
            'sku' => $product['sku'] ?? '',
            'status' => 'supplier_product',
            'product_type' => $product['product_type'] ?? null,
            'supplier' => $product['supplier'] ?? 'ASC',
            'website_url' => $product['website_url'] ?? null,
            'fabric' => $product['fabric'] ?? null,
            'care_instructions' => $product['care_instructions'] ?? null,
            'lead_times' => $product['lead_times'] ?? null,
            'available_sizes' => $product['available_sizes'] ?? null,
            'customization_methods' => $product['customization_methods'] ?? null,
            'model_size' => $product['model_size'] ?? null,
            'minimums' => $product['minimums'] ?? null,
            'starting_from_price' => $product['starting_from_price'] ? (string)$product['starting_from_price'] : null,
            'cost' => $product['cost'] ?? 0,
            'price' => $product['price'] ?? 0,
            'parent_product' => $product['parent_product'] ?? null,
            'hs_code' => $product['hs_code'] ?? null,
            'how_it_fits' => $product['specifications'] ?? null,
            'media' => $product['images'] ?? null,
            'created_at' => $product['created_at'] ?? now(),
            'updated_at' => $product['updated_at'] ?? now(),
        ];
    }
}
