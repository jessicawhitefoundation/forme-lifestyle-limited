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
            // One backup delivery fee for every overseas country, used only when
            // Routely can't give a price.
            $table->decimal('international_backup_fee', 12, 2)->nullable();
        });

        Cache::forget('store_settings');
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn('international_backup_fee');
        });

        Cache::forget('store_settings');
    }
};
