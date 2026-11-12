<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            // Kilograms per item used to estimate overseas parcels for Routely's quote.
            $table->decimal('international_item_weight', 5, 2)->nullable();
        });

        Cache::forget('store_settings');
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn('international_item_weight');
        });

        Cache::forget('store_settings');
    }
};
