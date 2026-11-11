<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Receives crash reports from the store's error screen ("Something paused our
 * display"), so errors that only happen in a visitor's browser reach the log.
 */
class ClientErrorController extends Controller
{
    public function store(Request $request): Response
    {
        $field = fn (string $key, int $limit) => Str::limit((string) $request->input($key, ''), $limit, '…');

        Log::error('Browser error on the store: '.$field('message', 300), [
            'url' => $field('url', 300),
            'stack' => $field('stack', 2000),
            'component_stack' => $field('componentStack', 1500),
            'user_agent' => Str::limit((string) $request->userAgent(), 300, '…'),
            'user_id' => $request->user()?->id,
        ]);

        return response()->noContent();
    }
}
