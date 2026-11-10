<?php

namespace Tests\Feature\Store;

use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CatalogueTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(string $name, string $mainCategory = 'women'): Product
    {
        return Product::create([
            'category_id' => Category::where('slug', $mainCategory)->value('id'),
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'base_price' => 10000,
            'is_active' => true,
        ]);
    }

    private function assertCollectionHas(string $url, int $total): void
    {
        $this->get($url)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Store/Collection')->where('products.total', $total));
    }

    public function test_the_store_has_its_main_categories(): void
    {
        foreach (['women', 'men', 'kids', 'accessories', 'new-arrivals'] as $slug) {
            $this->get("/collections/{$slug}")->assertOk();
        }
    }

    public function test_a_product_appears_in_its_main_category_and_new_arrivals_without_a_duplicate(): void
    {
        $product = $this->makeProduct('Noor Set');
        $newArrivals = Category::where('slug', 'new-arrivals')->value('id');
        $product->categories()->attach($newArrivals);

        $this->assertCollectionHas('/collections/women', 1);
        $this->assertCollectionHas('/collections/new-arrivals', 1);
        $this->assertCollectionHas('/collections/men', 0);
        $this->assertCollectionHas('/shop', 1);
        $this->assertSame(1, Product::count());
    }

    public function test_leaving_new_arrivals_keeps_the_product_in_its_main_category(): void
    {
        $product = $this->makeProduct('Noor Set');
        $newArrivals = Category::where('slug', 'new-arrivals')->value('id');
        $product->categories()->attach($newArrivals);

        $product->categories()->detach($newArrivals);

        $this->assertCollectionHas('/collections/new-arrivals', 0);
        $this->assertCollectionHas('/collections/women', 1);
    }

    public function test_search_returns_only_matching_products(): void
    {
        $this->makeProduct('Sculpt Legging');
        $this->makeProduct('Tennis Skirt');

        $this->assertCollectionHas('/shop?q=sculpt', 1);
        $this->assertCollectionHas('/shop?q=nothing-like-this', 0);
    }

    public function test_search_treats_wildcards_as_plain_text(): void
    {
        $this->makeProduct('Sculpt Legging');

        $this->assertCollectionHas('/shop?q=%25', 0);
    }

    public function test_store_settings_survive_the_real_cache(): void
    {
        // The array cache used in tests hides this: real caches can't restore model objects.
        config(['cache.default' => 'database']);

        StoreSetting::current();
        $settings = StoreSetting::current();

        $this->assertInstanceOf(StoreSetting::class, $settings);
        $this->assertTrue($settings->free_shipping_enabled);

        $settings->update(['free_shipping_threshold' => 300000]);
        $this->assertSame(300000.0, StoreSetting::current()->free_shipping_threshold);
        $this->get('/')->assertOk();
    }

    public function test_the_new_store_pages_load(): void
    {
        foreach (['/faq', '/contact', '/privacy', '/terms', '/track-order', '/forgot-password'] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
