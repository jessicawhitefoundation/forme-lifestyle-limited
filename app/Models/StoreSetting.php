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
        'international_rates' => 'array',
    ];

    /**
     * Overseas countries customers can choose at checkout, keyed by code, each
     * with its config (config/store.php countries). Delivery is priced live by
     * Routely; `fee` is the optional backup price used when Routely can't give
     * one, the same for every country.
     *
     * @return array<string, array{code: string, name: string, currency: string, symbol: string, dial: string, region_label: string, region_required: bool, postcode_label: string, postcode_required: bool, coordinates: string, sample_postcode: string, sample_city: string, fee: ?float}>
     */
    public function internationalCountries(): array
    {
        $enabled = $this->enabledCountryCodes();
        $backup = is_numeric($this->international_backup_fee) && (float) $this->international_backup_fee > 0
            ? (float) $this->international_backup_fee
            : null;
        $countries = [];

        foreach (config('store.countries') as $code => $country) {
            if ($code === 'NG' || ! in_array($code, $enabled, true)) {
                continue;
            }

            $countries[$code] = $country + ['code' => $code, 'fee' => $backup];
        }

        return $countries;
    }

    /**
     * Codes of the overseas countries switched on. Stored in international_rates
     * as {"GB": {"enabled": true}, ...}.
     *
     * @return list<string>
     */
    public function enabledCountryCodes(): array
    {
        return array_keys(array_filter($this->international_rates ?? [], fn ($country) => ! empty($country['enabled'])));
    }

    /** Estimated weight of one item for overseas quotes (products have no weights yet). */
    public function internationalItemWeight(): float
    {
        $weight = (float) ($this->international_item_weight ?? 0);

        return $weight > 0 ? $weight : (float) config('store.international_item_weight_kg', 0.7);
    }

    /** The overseas country with this name or code, if customers can order to it. */
    public function internationalCountry(?string $nameOrCode): ?array
    {
        foreach ($this->internationalCountries() as $code => $country) {
            if ($nameOrCode === $code || $nameOrCode === $country['name']) {
                return $country;
            }
        }

        return null;
    }

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
