<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->boolean('free_shipping_enabled')->default(true);
            $table->decimal('free_shipping_threshold', 12, 2)->default(200000);
            $table->boolean('vat_enabled')->default(false);
            $table->decimal('vat_rate', 5, 2)->default(7.5);
            $table->boolean('vat_inclusive')->default(true);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->boolean('vat_inclusive')->default(true);
            $table->boolean('free_shipping')->default(false);
            $table->boolean('save_card')->default(false);
            $table->unsignedBigInteger('cart_id')->nullable()->index();
        });

        // The old banner promised free shipping over ₦50,000, which the store never applied.
        DB::table('store_settings')
            ->where('announcement_text', 'like', '%50,000%')
            ->update(['announcement_text' => 'Free delivery within Lagos on orders of ₦200,000 or more']);

        // The cached copy predates these columns.
        Cache::forget('store_setting_global');
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn(['free_shipping_enabled', 'free_shipping_threshold', 'vat_enabled', 'vat_rate', 'vat_inclusive']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['cart_id']);
            $table->dropColumn(['vat_amount', 'vat_inclusive', 'free_shipping', 'save_card', 'cart_id']);
        });

        Cache::forget('store_setting_global');
    }
};
