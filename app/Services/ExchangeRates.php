<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Today's exchange rates from the naira, for showing overseas customers a
 * rough total in their own currency. Only an estimate: customers are always
 * charged in naira. Fetched from the free ExchangeRate-API open endpoint (no
 * key) and kept for 12 hours; if it's unreachable, no estimate is shown.
 */
class ExchangeRates
{
    private const URL = 'https://open.er-api.com/v6/latest/NGN';

    private const CACHE_KEY = 'exchange_rates_ngn';

    /** How many of `$currency` one naira buys, or null when unknown. */
    public function fromNaira(string $currency): ?float
    {
        $rate = $this->rates()[strtoupper($currency)] ?? null;

        return is_numeric($rate) && (float) $rate > 0 ? (float) $rate : null;
    }

    /** `$naira` converted to `$currency`, rounded to 2 decimals, or null when unknown. */
    public function convert(float $naira, string $currency): ?float
    {
        $rate = $this->fromNaira($currency);

        return $rate ? round($naira * $rate, 2) : null;
    }

    /** @return array<string, float> */
    private function rates(): array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached)) {
            return $cached;
        }

        $rates = [];

        try {
            $response = Http::timeout(5)->get(self::URL);

            if ($response->successful() && $response->json('result') === 'success') {
                $rates = (array) $response->json('rates', []);
            }
        } catch (\Throwable $e) {
            Log::warning('Exchange rates could not be fetched: '.$e->getMessage());
        }

        // Keep a good answer for 12 hours; after a failure, try again in 30 minutes.
        Cache::put(self::CACHE_KEY, $rates, $rates ? now()->addHours(12) : now()->addMinutes(30));

        return $rates;
    }
}
