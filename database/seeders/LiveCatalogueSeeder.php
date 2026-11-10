<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\HeroSlide;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Loads the real FORMÉ catalogue: the 27 products from the old forme.com.ng
 * database (prices, colours, stock, categories) and the Debut Collection
 * photos, already resized for the web (data/photos, listed in live-photos.json).
 *
 * A product is switched on only if it has photos and something to buy;
 * the rest stay hidden for review in Admin > Products.
 *
 * Safe to run more than once: existing products (same slug) are not changed,
 * except that photos are added to one that has none yet. Run it on its own,
 * never through DatabaseSeeder:
 *   php artisan db:seed --class=LiveCatalogueSeeder --force
 */
class LiveCatalogueSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(__DIR__.'/data/live-catalogue.json'), true, flags: JSON_THROW_ON_ERROR);
        $photos = json_decode(file_get_contents(__DIR__.'/data/live-photos.json'), true, flags: JSON_THROW_ON_ERROR);

        $categories = Category::whereIn('slug', ['women', 'men', 'kids', 'accessories', 'new-arrivals'])->pluck('id', 'slug');

        if ($categories->count() !== 5) {
            throw new RuntimeException('Run "php artisan migrate --force" first: the store categories are missing.');
        }

        $created = $photographed = $active = 0;

        DB::transaction(function () use ($data, $photos, $categories, &$created, &$photographed, &$active) {
            foreach ($data['products'] as $row) {
                $productPhotos = $photos[$row['slug']] ?? [];
                $product = Product::where('slug', $row['slug'])->first();

                if (! $product) {
                    // Live only with photos and something in stock to sell.
                    $inStock = array_sum(array_map(fn ($v) => max(0, (int) $v['stock_quantity']), $row['variants'])) > 0;
                    $product = $this->createProduct($row, $categories, active: $productPhotos !== [] && $inStock);
                    $created++;
                    $active += (int) $product->is_active;
                }

                if ($productPhotos !== [] && ! $product->images()->exists()) {
                    $this->attachPhotos($product, $productPhotos);
                    $photographed++;
                }
            }
        });

        $slides = $this->addHeroSlides();
        $categoryPhotos = $this->addCategoryPhotos();

        $this->command?->info("Created {$created} products ({$active} switched on), added photos to {$photographed}, {$slides} homepage banners and {$categoryPhotos} category photos.");
    }

    /**
     * Photos for the homepage "Shop by Category" cards (data/category-photos.json).
     * Only fills categories with no photo yet, so ones set in the admin are kept.
     */
    private function addCategoryPhotos(): int
    {
        $photos = json_decode(file_get_contents(__DIR__.'/data/category-photos.json'), true, flags: JSON_THROW_ON_ERROR);
        $added = 0;

        foreach ($photos as $slug => $photo) {
            $category = Category::where('slug', $slug)->whereNull('image_path')->first();
            if (! $category) {
                continue;
            }

            $path = 'categories/'.str_replace('/', '-', $photo);
            Storage::disk('public')->put($path, file_get_contents(__DIR__.'/data/photos/'.$photo));
            $category->forceFill(['image_path' => $path])->save();
            $added++;
        }

        return $added;
    }

    /**
     * Homepage banners chosen from the Debut Collection (data/hero-slides.json).
     * Skipped if any banner already exists, so ones set in the admin are kept.
     */
    private function addHeroSlides(): int
    {
        if (HeroSlide::exists()) {
            return 0;
        }

        $slides = json_decode(file_get_contents(__DIR__.'/data/hero-slides.json'), true, flags: JSON_THROW_ON_ERROR);

        foreach ($slides as $i => $slide) {
            $path = 'hero-slides/'.str_replace('/', '-', $slide['photo']);
            Storage::disk('public')->put($path, file_get_contents(__DIR__.'/data/photos/'.$slide['photo']));

            HeroSlide::create([
                'type' => 'image',
                'src' => $path,
                'label' => $slide['label'],
                'sub' => $slide['sub'],
                'sort_order' => $i,
                'is_active' => true,
            ]);
        }

        return count($slides);
    }

    private function createProduct(array $row, $categories, bool $active): Product
    {
        // Same mapping as the category_product migration used for the old data.
        $main = $row['old_category'] === 'accessories' ? 'accessories' : match ($row['gender']) {
            'male' => 'men',
            'kids' => 'kids',
            default => 'women',
        };

        $product = Product::create([
            'category_id' => $categories[$main],
            'gender' => $row['gender'],
            'name' => $row['name'],
            'slug' => $row['slug'],
            'description' => $row['description'],
            'short_description' => $row['short_description'],
            'base_price' => $row['base_price'],
            'compare_price' => $row['compare_price'],
            'is_featured' => $row['is_featured'],
            'is_active' => $active,
            'sort_order' => 0,
            'material' => $row['material'],
            'care_instructions' => $row['care_instructions'],
        ]);

        // Jessica: the whole launch collection is new, so every product starts in
        // New Arrivals. Untick them in Admin > Products as newer pieces arrive.
        $extra = [$categories['new-arrivals']];
        if ($row['gender'] === 'unisex' && $main === 'women') {
            $extra[] = $categories['men'];
        }
        $product->categories()->syncWithoutDetaching($extra);

        foreach ($row['variants'] as $variant) {
            $product->variants()->create(['color' => $variant['color'] !== null ? trim($variant['color']) : null] + $variant);
        }

        return $product;
    }

    private function attachPhotos(Product $product, array $photos): void
    {
        foreach ($photos as $i => $photo) {
            $path = 'products/'.str_replace('/', '-', $photo['file']);
            Storage::disk('public')->put($path, file_get_contents(__DIR__.'/data/photos/'.$photo['file']));

            $color = $photo['color'] !== null ? trim($photo['color']) : null;

            $product->images()->create([
                'image_path' => $path,
                'alt_text' => trim($product->name.($color ? " in {$color}" : '')),
                'color' => $color,
                'is_primary' => $photo['is_primary'],
                'sort_order' => $i,
            ]);
        }
    }
}
