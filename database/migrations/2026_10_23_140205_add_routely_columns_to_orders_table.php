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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('routely_shipment_id')->nullable()->after('notes');
            $table->string('routely_tracking_number')->nullable()->after('routely_shipment_id');
            $table->string('routely_status')->nullable()->after('routely_tracking_number');
            $table->decimal('routely_fee', 10, 2)->nullable()->after('routely_status');
            $table->json('routely_response')->nullable()->after('routely_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'routely_shipment_id',
                'routely_tracking_number',
                'routely_status',
                'routely_fee',
                'routely_response',
            ]);
        });
    }
};
