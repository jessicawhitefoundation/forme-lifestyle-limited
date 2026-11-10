<?php

use App\Http\Controllers\Auth\CustomerAuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\StoreController;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

// ─── Store Front ──────────────────────────────────────────────────────────────
Route::middleware('throttle:storefront')->group(function () {
    Route::get('/', [StoreController::class, 'home'])->name('home');
    Route::get('/shop', [StoreController::class, 'collection'])->name('shop');
    Route::get('/collections/{slug}', [StoreController::class, 'collection'])->name('collection');
    Route::get('/products/{slug}', [StoreController::class, 'product'])->name('product');
    Route::get('/policy', [StoreController::class, 'policy'])->name('policy');
    Route::get('/about', [StoreController::class, 'about'])->name('about');
    Route::get('/connect', [StoreController::class, 'connect'])->name('connect');
    Route::get('/size-guide', [StoreController::class, 'sizeGuide'])->name('size-guide');
    Route::get('/faq', [StoreController::class, 'info'])->defaults('page', 'faq')->name('faq');
    Route::get('/contact', [StoreController::class, 'info'])->defaults('page', 'contact')->name('contact');
    Route::get('/privacy', [StoreController::class, 'info'])->defaults('page', 'privacy')->name('privacy');
    Route::get('/terms', [StoreController::class, 'info'])->defaults('page', 'terms')->name('terms');
    Route::get('/track-order', [OrderTrackingController::class, 'show'])->name('order.track');
    Route::post('/track-order', [OrderTrackingController::class, 'find'])->middleware('throttle:10,1')->name('order.track.find');
});

// ─── Cart ─────────────────────────────────────────────────────────────────────
Route::middleware('throttle:cart')->group(function () {
    Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
    Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/cart/{cartItem}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{cartItem}', [CartController::class, 'destroy'])->name('cart.destroy');
    Route::get('/cart/count', [CartController::class, 'count'])->name('cart.count');
});

// ─── Checkout & Paystack (open to guests; an account is optional) ─────────────
Route::middleware('throttle:cart')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::get('/checkout/callback', [CheckoutController::class, 'callback'])->name('checkout.callback');
    Route::get('/order/{orderNumber}/confirmation', [CheckoutController::class, 'confirmation'])->name('order.confirmation');
});
Route::post('/checkout/shipping-rate', [CheckoutController::class, 'calculateShippingRate'])
    ->middleware('throttle:shipping-rate')
    ->name('checkout.shipping-rate');
Route::post('/checkout/initiate', [CheckoutController::class, 'initiate'])
    ->middleware('throttle:checkout')
    ->name('checkout.initiate');

// ─── Paystack Webhook (CSRF-exempt in bootstrap/app.php; verified by signature) ─
Route::post('/webhooks/paystack', [PaystackWebhookController::class, 'handle'])
    ->middleware('throttle:120,1')
    ->name('webhook.paystack');

// ─── Customer Auth (only form submissions are limited, not viewing the pages) ──
Route::middleware('guest')->group(function () {
    Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [CustomerAuthController::class, 'register'])->middleware('throttle:auth');
    Route::get('/login', [CustomerAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [CustomerAuthController::class, 'login'])->middleware('throttle:auth');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('logout');
    Route::get('/account', [CustomerAuthController::class, 'account'])->name('account');
    Route::get('/account/orders', [CustomerAuthController::class, 'orders'])->name('account.orders');
    Route::patch('/account/profile', [CustomerAuthController::class, 'updateProfile'])->name('account.update');
    Route::delete('/account/saved-card', [CustomerAuthController::class, 'removeSavedCard'])->name('account.saved-card.destroy');
});

// ─── Storage Fallback (Serves uploads if symlink is missing in production) ────
Route::get('/storage/{path}', function (string $path) {
    $publicDir = realpath(storage_path('app/public'));
    $fullPath = realpath(storage_path('app/public/'.$path));

    if (! $fullPath || ! $publicDir || ! str_starts_with($fullPath, $publicDir.DIRECTORY_SEPARATOR) || ! is_file($fullPath)) {
        abort(404);
    }

    return response()->file($fullPath);
})->where('path', '.*')
    ->withoutMiddleware([
        StartSession::class,
        ShareErrorsFromSession::class,
        HandleInertiaRequests::class,
        HandleAppearance::class,
    ]);

require __DIR__.'/settings.php';
