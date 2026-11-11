<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Models\StoreSetting;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // Paystack can't send a CSRF token; the webhook checks Paystack's signature instead.
        // The store's crash reporter uses sendBeacon, which can't send one either; it only writes to the log.
        $middleware->preventRequestForgery(except: ['webhooks/paystack', 'client-error']);

        $middleware->web(append: [
            SecurityHeadersMiddleware::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->respond(function ($response, Throwable $exception, Request $request) {
            $status = $response->getStatusCode();

            // In production or when debug mode is disabled, render the branded Inertia error page
            if (! config('app.debug') && in_array($status, [500, 503, 404, 403])) {
                // A mistyped address matches no route, so HandleInertiaRequests never shares
                // the store details the layout needs (contact, settings). Pass them here, or
                // the page crashes in the browser instead of showing "not found".
                return Inertia::render('Error', [
                    'status' => $status,
                    'name' => config('app.name'),
                    'auth' => ['user' => $request->hasSession() ? $request->user() : null],
                    'store_setting' => rescue(fn () => StoreSetting::current(), null, report: false),
                    'store_contact' => [
                        'email' => config('store.email'),
                        'whatsapp_display' => config('store.whatsapp_display'),
                        'whatsapp_number' => config('store.whatsapp_number'),
                    ],
                ])
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            // Friendly recovery for session expiration (CSRF token timeout)
            if ($status === 419) {
                return back()->with([
                    'message' => 'Your session timed out. Please try again.',
                ]);
            }

            return $response;
        });
    })->create();
