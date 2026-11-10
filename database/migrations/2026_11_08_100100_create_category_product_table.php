<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A product keeps one main category (products.category_id) and can also
     * appear in others through this table, e.g. Women + New Arrivals.
     */
    public function up(): void
    {
        Schema::create('category_product', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['category_id', 'product_id']);
        });

        $storeCategories = [
            'women' => ['name' => 'Women', 'sort_order' => 1],
            'men' => ['name' => 'Men', 'sort_order' => 2],
            'kids' => ['name' => 'Kids', 'sort_order' => 3],
            'accessories' => ['name' => 'Accessories', 'sort_order' => 4],
            'new-arrivals' => ['name' => 'New Arrivals', 'sort_order' => 5],
        ];

        foreach ($storeCategories as $slug => $values) {
            DB::table('categories')->updateOrInsert(
                ['slug' => $slug],
                $values + ['is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            );
        }

        $ids = DB::table('categories')->whereIn('slug', array_keys($storeCategories))->pluck('id', 'slug');
        $oldSlugs = DB::table('categories')->pluck('slug', 'id');

        foreach (DB::table('products')->get(['id', 'category_id', 'gender']) as $product) {
            $oldSlug = $oldSlugs[$product->category_id] ?? null;

            $main = $oldSlug === 'accessories' ? 'accessories' : match ($product->gender) {
                'male' => 'men',
                'kids' => 'kids',
                default => 'women',
            };

            DB::table('products')->where('id', $product->id)->update(['category_id' => $ids[$main]]);

            if ($oldSlug === 'new-arrivals') {
                DB::table('category_product')->insertOrIgnore(['category_id' => $ids['new-arrivals'], 'product_id' => $product->id]);
            }

            if ($product->gender === 'unisex' && $main === 'women') {
                DB::table('category_product')->insertOrIgnore(['category_id' => $ids['men'], 'product_id' => $product->id]);
            }
        }

        DB::table('categories')->whereNotIn('slug', array_keys($storeCategories))->update(['is_active' => false]);
    }

    public function down(): void
    {
        Schema::dropIfExists('category_product');
    }
};
