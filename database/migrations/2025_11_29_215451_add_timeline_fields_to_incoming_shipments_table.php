<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incoming_shipments', function (Blueprint $table) {
            $table->timestamp('tracking_added_at')->nullable()->after('tracking_number');
            $table->timestamp('first_received_at')->nullable()->after('received_date');
            $table->json('receive_history')->nullable()->after('first_received_at');
        });
    }

    public function down(): void
    {
        Schema::table('incoming_shipments', function (Blueprint $table) {
            $table->dropColumn(['tracking_added_at', 'first_received_at', 'receive_history']);
        });
    }
};
