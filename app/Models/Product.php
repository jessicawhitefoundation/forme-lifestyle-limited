<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'gender', 'name', 'slug', 'description', 'short_description',
        'base_price', 'compare_price', 'is_featured', 'is_active', 'sort_order',
        'material', 'care_instructions',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * @return HasManyThrough<OrderItem, ProductVariant, $this>
     */
    public function orderItems(): HasManyThrough
    {
        return $this->hasManyThrough(OrderItem::class, ProductVariant::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (Product $product) {
            /** @var ProductImage $image */
            foreach ($product->images as $image) {
                if ($image->image_path) {
                    Storage::disk('public')->delete($image->image_path);
                }
            }
        });
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): ?ProductImage
    {
        /** @var ProductImage|null $img */
        $img = $this->images()->where('is_primary', true)->first()
            ?? $this->images()->first();

        return $img;
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        $img = $this->primaryImage();

        return $img ? asset('storage/'.$img->image_path) : null;
    }

    /**
     * @return Collection<string, string>
     */
    public function colors(): Collection
    {
        return $this->variants()
            ->whereNotNull('color')
            ->distinct('color')
            ->pluck('color', 'color_hex');
    }

    /**
     * @return Collection<int, string>
     */
    public function sizes(): Collection
    {
        return $this->variants()
            ->whereNotNull('size')
            ->distinct()
            ->pluck('size');
    }
}
