<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->isProduction() || (app()->has('request') && request()->header('x-forwarded-proto') === 'https')) {
            URL::forceScheme('https');
        }

        $this->configureDefaults();
        $this->configureRateLimiting();
    }

    protected function configureRateLimiting(): void
    {
        // Homepage & Store Front protection: max 60 requests per minute per IP
        RateLimiter::for('storefront', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Auth protection (login/register): max 6 attempts per minute per IP
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(6)->by($request->ip());
        });

        // Checkout initiation: max 10 attempts per minute per IP
        RateLimiter::for('checkout', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
