<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderTrackingController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Store/TrackOrder');
    }

    public function find(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order_number' => 'required|string|max:60',
            'email' => 'required|email|max:255',
        ]);

        $order = Order::where('order_number', strtoupper(trim($validated['order_number'])))
            ->where('customer_email', trim($validated['email']))
            ->first();

        if (! $order) {
            return back()->withErrors(['order_number' => 'We could not find an order with those details. Please check them and try again.']);
        }

        return redirect()->to($order->confirmationUrl());
    }
}
