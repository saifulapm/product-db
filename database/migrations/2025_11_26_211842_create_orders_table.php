<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique(); // e.g., BDR1399
            $table->string('client_name')->nullable();
            $table->foreignId('incoming_shipment_id')->nullable()->constrained('incoming_shipments')->nullOnDelete();
            $table->json('items')->nullable(); // Array of items needed for this order
            $table->string('status')->default('pending'); // pending, picking, picked, shipped, completed
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index('order_number');
            $table->index('status');
            $table->index('incoming_shipment_id');
        });
        
        // Create order_items table to track allocations from shipments
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('incoming_shipment_id')->constrained('incoming_shipments')->cascadeOnDelete();
            $table->integer('shipment_item_index')->nullable(); // Index of item in shipment's items array
            $table->string('style')->nullable();
            $table->string('color')->nullable();
            $table->string('packing_way')->nullable();
            $table->integer('quantity_required')->default(0);
            $table->integer('quantity_allocated')->default(0);
            $table->string('warehouse_location')->nullable();
            $table->timestamps();
            
            $table->index('order_id');
            $table->index('incoming_shipment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
