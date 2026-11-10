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
        // Limits are per IP, but many Nigerian mobile customers can share one IP,
        // so they are set well above what a single shopper would ever reach.
        RateLimiter::for('storefront', function (Request $request) {
            return Limit::perMinute(300)->by($request->ip());
        });

        RateLimiter::for('cart', function (Request $request) {
            return Limit::perMinute(300)->by($request->ip());
        });

        // Login and registration attempts: per account and per IP.
        RateLimiter::for('auth', function (Request $request) {
            return [
                Limit::perMinute(30)->by($request->ip()),
                Limit::perMinute(6)->by(strtolower((string) $request->input('email')).'|'.$request->ip()),
            ];
        });

        RateLimiter::for('checkout', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        // Each uncached quote calls Routely with the store's API key.
        RateLimiter::for('shipping-rate', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
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
