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
            // Each country is offered once switched on with a fee: {"GB": {"enabled": bool, "fee": naira, "rate": naira per £}, "US": {...}, "CA": {...}}
            $table->json('international_rates')->nullable();
            $table->string('international_delivery_time')->default('3–5 business days');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_postcode', 20)->nullable()->after('shipping_state');
            // Couriers booked by hand (DHL for overseas orders); Routely keeps its own columns.
            $table->string('shipping_carrier')->nullable();
            $table->string('tracking_number')->nullable();
        });

        Cache::forget('store_settings');
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn(['international_rates', 'international_delivery_time']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_postcode', 'shipping_carrier', 'tracking_number']);
        });

        Cache::forget('store_settings');
    }
};
