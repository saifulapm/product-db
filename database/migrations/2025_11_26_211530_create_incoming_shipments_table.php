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
        Schema::create('incoming_shipments', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number')->nullable();
            $table->string('carrier')->nullable();
            $table->string('supplier')->nullable();
            $table->date('expected_date')->nullable();
            $table->date('received_date')->nullable();
            $table->string('status')->default('pending'); // pending, in_transit, received, delayed, cancelled
            $table->json('items')->nullable(); // Array of items in shipment
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index('status');
            $table->index('expected_date');
            $table->index('tracking_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incoming_shipments');
    }
};
