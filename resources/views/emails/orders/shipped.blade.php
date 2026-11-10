@extends('emails.layout', ['title' => 'Your Order Has Shipped #' . $order->order_number])

@section('content')
    <div style="text-align: center; margin-bottom: 28px;">
        <span class="tag" style="background-color: #E0F2FE; color: #0369A1;">Dispatched</span>
        <h2 style="font-family: 'Playfair Display', Georgia, serif; font-size: 24px; color: #1a1a1a; margin: 14px 0 6px 0; font-weight: 500;">
            Your Order is on its Way!
        </h2>
        <p style="color: #666; font-size: 14px; margin: 0;">
            Great news, {{ $order->customer_name }}! Order <strong>#{{ $order->order_number }}</strong> has been handed over to our courier partner.
        </p>
    </div>

    <!-- Tracking Card -->
    <div style="background-color: #FAF8F5; border: 1.5px solid #6F1D2F; border-radius: 4px; padding: 22px 24px; margin-bottom: 28px; text-align: center;">
        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: #8c827a; margin-bottom: 6px;">
            Routely Tracking Number
        </div>
        <div style="font-family: monospace; font-size: 20px; font-weight: 700; color: #6F1D2F; letter-spacing: 2px; margin-bottom: 8px;">
            {{ $order->routely_tracking_number ?? 'In Transit' }}
        </div>
        <p style="margin: 0; font-size: 13px; color: #555;">
            Carrier: <strong>{{ $order->routely_carrier ?? 'Routely Logistics Partner' }}</strong>
        </p>
    </div>

    <!-- Delivery Address -->
    <div style="background-color: #FAF8F5; border-left: 3px solid #6F1D2F; padding: 14px 18px; border-radius: 2px; margin-bottom: 24px;">
        <strong style="color: #6F1D2F; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Delivering To:</strong>
        <p style="margin: 6px 0 0 0; font-size: 13px; color: #333; line-height: 1.5;">
            {{ $order->shipping_address }}, {{ $order->shipping_city }}, {{ $order->shipping_state }}, {{ $order->shipping_country }}
        </p>
    </div>

    <!-- Items in Package -->
    <h3 style="font-family: 'Playfair Display', Georgia, serif; font-size: 18px; color: #1a1a1a; margin: 0 0 14px 0; font-weight: 500; border-bottom: 1px solid #F0ECE8; padding-bottom: 8px;">
        Items in Package
    </h3>

    <table width="100%" cellpadding="8" cellspacing="0" style="font-size: 13px; margin-bottom: 24px;">
        @foreach($order->items as $item)
            <tr style="border-bottom: 1px solid #F7F4F0;">
                <td style="padding: 10px 0;">
                    <strong style="color: #1a1a1a;">{{ $item->product_name }}</strong>
                    @if($item->variant_description)
                        <div style="color: #777; font-size: 12px; margin-top: 2px;">{{ $item->variant_description }}</div>
                    @endif
                </td>
                <td align="right" style="padding: 10px 0; color: #555;">Qty: {{ $item->quantity }}</td>
            </tr>
        @endforeach
    </table>

    <!-- CTA Button -->
    <div style="text-align: center; margin: 32px 0 16px 0;">
        <a href="{{ $order->confirmationUrl() }}" class="button">
            Track Delivery
        </a>
    </div>
@endsection
