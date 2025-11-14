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
        Schema::table('franchisees', function (Blueprint $table) {
            // Drop old columns
            $table->dropColumn(['email', 'phone', 'address', 'city', 'state', 'zip_code']);
            
            // Rename 'name' to 'franchisee_name'
            $table->renameColumn('name', 'franchisee_name');
            
            // Add new columns
            $table->string('company')->after('id');
            $table->string('location')->after('company');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('franchisees', function (Blueprint $table) {
            // Revert column rename
            $table->renameColumn('franchisee_name', 'name');
            
            // Drop new columns
            $table->dropColumn(['company', 'location']);
            
            // Re-add old columns
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
        });
    }
};
