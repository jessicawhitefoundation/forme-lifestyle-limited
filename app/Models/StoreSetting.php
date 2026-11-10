<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class StoreSetting extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'announcement_is_active' => 'boolean',
        'free_shipping_enabled' => 'boolean',
        'free_shipping_threshold' => 'float',
        'vat_enabled' => 'boolean',
        'vat_rate' => 'float',
        'vat_inclusive' => 'boolean',
    ];

    private const CACHE_KEY = 'store_settings';

    /**
     * The store's single settings row, cached because every page uses it. The
     * cache holds plain attributes: Laravel won't restore model objects from it.
     */
    public static function current(): self
    {
        $attributes = Cache::remember(self::CACHE_KEY, 86400, fn () => self::firstOrCreate(['id' => 1], [
            'announcement_text' => 'Free delivery within Lagos on orders of ₦200,000 or more',
            'announcement_link' => '/shop',
            'announcement_is_active' => true,
        ])->refresh()->getAttributes());

        return (new self)->newFromBuilder($attributes);
    }

    protected static function booted(): void
    {
        static::saved(function () {
            cache()->forget(self::CACHE_KEY);
        });

        static::deleted(function () {
            cache()->forget(self::CACHE_KEY);
        });
    }
}
