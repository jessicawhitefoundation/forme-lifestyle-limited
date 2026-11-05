@extends('emails.layout', ['title' => 'Welcome to FORMÉ'])

@section('content')
    <div style="text-align: center; margin-bottom: 28px;">
        <span class="tag">Welcome to FORMÉ</span>
        <h2 style="font-family: 'Playfair Display', Georgia, serif; font-size: 26px; color: #1a1a1a; margin: 14px 0 8px 0; font-weight: 500;">
            Welcome, {{ $user->name }}
        </h2>
        <p style="color: #666; font-size: 15px; margin: 0; line-height: 1.6;">
            We're delighted to welcome you to our community. FORMÉ was created to bring you elevated, functional activewear at attainable prices.
        </p>
    </div>

    <div style="background-color: #FAF8F5; border-radius: 4px; padding: 24px 28px; margin-bottom: 28px;">
        <h3 style="font-family: 'Playfair Display', Georgia, serif; font-size: 17px; color: #6F1D2F; margin: 0 0 12px 0; font-weight: 600;">
            Your Active Wardrobe Starts Here
        </h3>
        <p style="color: #444; font-size: 13px; line-height: 1.7; margin: 0 0 16px 0;">
            With your new FORMÉ account, you enjoy:
        </p>
        <ul style="color: #444; font-size: 13px; line-height: 1.8; margin: 0; padding-left: 18px;">
            <li><strong>Express Checkout:</strong> Seamless order placement with saved delivery details.</li>
            <li><strong>Real-time Tracking:</strong> Follow your Routely interstate and local dispatches step-by-step.</li>
            <li><strong>Order History:</strong> Access all your past orders, receipts and tracking IDs in one place.</li>
        </ul>
    </div>

    <!-- CTA Button -->
    <div style="text-align: center; margin: 32px 0 20px 0;">
        <a href="{{ url('/collections/new-arrivals') }}" class="button">
            Shop Debut Collection
        </a>
    </div>

    <p style="text-align: center; font-size: 12px; color: #8c827a; margin: 0;">
        Move. Perform. Live. In your own form.
    </p>
@endsection
