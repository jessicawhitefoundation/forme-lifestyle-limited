@extends('emails.layout', ['title' => 'Order Confirmation #' . $order->order_number])

@section('content')
    <div style="text-align: center; margin-bottom: 28px;">
        <span class="tag">Payment Confirmed</span>
        <h2 style="font-family: 'Playfair Display', Georgia, serif; font-size: 24px; color: #1a1a1a; margin: 14px 0 6px 0; font-weight: 500;">
            Thank you for your order, {{ $order->customer_name }}!
        </h2>
        <p style="color: #666; font-size: 14px; margin: 0;">
            We have received your payment and are getting your pieces ready for dispatch.
        </p>
    </div>

    <!-- Order Metadata Box -->
    <table width="100%" cellpadding="12" cellspacing="0" style="background-color: #FAF8F5; border-radius: 4px; margin-bottom: 28px; font-size: 13px;">
        <tr>
            <td width="50%" style="border-bottom: 1px solid #EFEAE6;">
                <strong style="color: #8c827a; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Order Number</strong><br>
                <span style="font-weight: 600; color: #1a1a1a;">{{ $order->order_number }}</span>
            </td>
            <td width="50%" style="border-bottom: 1px solid #EFEAE6;">
                <strong style="color: #8c827a; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Date</strong><br>
                <span style="color: #1a1a1a;">{{ $order->created_at->format('M d, Y · h:i A') }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <strong style="color: #8c827a; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Payment Method</strong><br>
                <span style="color: #1a1a1a;">Paystack ({{ $order->payment_reference ?? 'Verified' }})</span>
            </td>
            <td>
                <strong style="color: #8c827a; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Delivery Status</strong><br>
                <span style="color: #6F1D2F; font-weight: 600;">Processing</span>
            </td>
        </tr>
    </table>

    <!-- Line Items Table -->
    <h3 style="font-family: 'Playfair Display', Georgia, serif; font-size: 18px; color: #1a1a1a; margin: 0 0 16px 0; font-weight: 500; border-bottom: 1px solid #F0ECE8; padding-bottom: 8px;">
        Order Summary
    </h3>

    <table width="100%" cellpadding="8" cellspacing="0" style="font-size: 13px; margin-bottom: 20px;">
        <thead>
            <tr style="border-bottom: 1px solid #EFEAE6; color: #8c827a; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">
                <th align="left" style="padding-bottom: 8px;">Item</th>
                <th align="center" style="padding-bottom: 8px;">Qty</th>
                <th align="right" style="padding-bottom: 8px;">Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr style="border-bottom: 1px solid #F7F4F0;">
                    <td style="padding: 12px 0;">
                        <strong style="color: #1a1a1a; font-size: 14px;">{{ $item->product_name }}</strong>
                        @if($item->variant_description)
                            <div style="color: #777; font-size: 12px; margin-top: 2px;">{{ $item->variant_description }}</div>
                        @endif
                    </td>
                    <td align="center" style="padding: 12px 0; color: #555;">{{ $item->quantity }}</td>
                    <td align="right" style="padding: 12px 0; font-weight: 500; color: #1a1a1a;">
                        ₦{{ number_format((float) $item->total_price, 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals Calculation -->
    <table width="100%" cellpadding="6" cellspacing="0" style="font-size: 13px; margin-bottom: 28px;">
        <tr>
            <td align="right" style="color: #777;">Subtotal:</td>
            <td align="right" width="120" style="color: #1a1a1a;">₦{{ number_format((float) $order->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td align="right" style="color: #777;">Delivery:</td>
            <td align="right" width="120" style="color: #1a1a1a;">{{ $order->free_shipping ? 'Free' : '₦'.number_format((float) $order->shipping_fee, 2) }}</td>
        </tr>
        @if((float) $order->vat_amount > 0)
            <tr>
                <td align="right" style="color: #777;">VAT{{ $order->vat_inclusive ? ' (included)' : '' }}:</td>
                <td align="right" width="120" style="color: #1a1a1a;">₦{{ number_format((float) $order->vat_amount, 2) }}</td>
            </tr>
        @endif
        <tr style="font-size: 15px;">
            <td align="right" style="font-weight: 600; color: #1a1a1a; padding-top: 10px; border-top: 1px solid #EFEAE6;">Total Paid:</td>
            <td align="right" width="120" style="font-weight: 700; color: #6F1D2F; padding-top: 10px; border-top: 1px solid #EFEAE6;">
                ₦{{ number_format((float) $order->total_amount, 2) }}
            </td>
        </tr>
    </table>

    <!-- Shipping Destination Details -->
    <div style="background-color: #FAF8F5; border-left: 3px solid #6F1D2F; padding: 16px 20px; border-radius: 2px; margin-bottom: 28px;">
        <h4 style="margin: 0 0 8px 0; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #6F1D2F;">
            Delivery Destination
        </h4>
        <p style="margin: 0; font-size: 13px; color: #333; line-height: 1.6;">
            <strong>Recipient:</strong> {{ $order->customer_name }} ({{ $order->customer_phone ?? 'No phone' }})<br>
            <strong>Address:</strong> {{ $order->shipping_address }}, {{ $order->shipping_city }}, {{ $order->shipping_state }}, {{ $order->shipping_country }}
            @if($order->notes)
                <br><strong>Notes:</strong> <em>{{ $order->notes }}</em>
            @endif
        </p>
    </div>

    <!-- CTA Button -->
    <div style="text-align: center; margin: 32px 0 16px 0;">
        <a href="{{ $order->confirmationUrl() }}" class="button">
            View Order Status
        </a>
    </div>
@endsection
