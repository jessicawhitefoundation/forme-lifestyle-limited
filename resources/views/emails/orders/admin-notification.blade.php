@extends('emails.layout', ['title' => 'New Order Received #' . $order->order_number])

@section('content')
    <div style="text-align: center; margin-bottom: 28px;">
        <span class="tag" style="background-color: #E8F5E9; color: #2E7D32;">New Paid Order</span>
        <h2 style="font-family: 'Playfair Display', Georgia, serif; font-size: 24px; color: #1a1a1a; margin: 14px 0 6px 0; font-weight: 500;">
            ₦{{ number_format((float) $order->total_amount, 2) }} Received
        </h2>
        <p style="color: #666; font-size: 14px; margin: 0;">
            A customer has just completed payment for Order <strong>#{{ $order->order_number }}</strong>.
        </p>
    </div>

    <!-- Customer Information -->
    <table width="100%" cellpadding="12" cellspacing="0" style="background-color: #FAF8F5; border-radius: 4px; margin-bottom: 24px; font-size: 13px;">
        <tr>
            <td width="50%" style="border-bottom: 1px solid #EFEAE6;">
                <strong style="color: #8c827a; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Customer Name</strong><br>
                <span style="font-weight: 600; color: #1a1a1a;">{{ $order->customer_name }}</span>
            </td>
            <td width="50%" style="border-bottom: 1px solid #EFEAE6;">
                <strong style="color: #8c827a; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Customer Contact</strong><br>
                <a href="mailto:{{ $order->customer_email }}" style="color: #6F1D2F;">{{ $order->customer_email }}</a><br>
                <span style="color: #555;">{{ $order->customer_phone ?? 'No phone' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <strong style="color: #8c827a; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Payment Reference</strong><br>
                <span style="color: #1a1a1a;">{{ $order->payment_reference ?? 'N/A' }}</span>
            </td>
            <td>
                <strong style="color: #8c827a; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Order Date</strong><br>
                <span style="color: #1a1a1a;">{{ $order->created_at->format('M d, Y · h:i A') }}</span>
            </td>
        </tr>
    </table>

    <!-- Shipping Destination -->
    <div style="background-color: #FAF8F5; border-left: 3px solid #6F1D2F; padding: 14px 18px; border-radius: 2px; margin-bottom: 24px;">
        <strong style="color: #6F1D2F; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Shipping Destination</strong>
        <p style="margin: 6px 0 0 0; font-size: 13px; color: #333; line-height: 1.5;">
            {{ $order->fullAddress() }}
            @if($order->isInternational())
                <br><strong style="color: #B45309;">Overseas order: ship with {{ $order->shipping_carrier ?: config('store.international_carrier') }}, then use “Mark as shipped” in the admin.</strong>
            @endif
            @if($order->notes)
                <br><em>Customer Notes: {{ $order->notes }}</em>
            @endif
        </p>
    </div>

    <!-- Items to Pack -->
    <h3 style="font-family: 'Playfair Display', Georgia, serif; font-size: 18px; color: #1a1a1a; margin: 0 0 14px 0; font-weight: 500; border-bottom: 1px solid #F0ECE8; padding-bottom: 8px;">
        Items to Pack ({{ $order->items->sum('quantity') }})
    </h3>

    <table width="100%" cellpadding="8" cellspacing="0" style="font-size: 13px; margin-bottom: 24px;">
        <thead>
            <tr style="border-bottom: 1px solid #EFEAE6; color: #8c827a; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">
                <th align="left" style="padding-bottom: 8px;">Product</th>
                <th align="center" style="padding-bottom: 8px;">Qty</th>
                <th align="right" style="padding-bottom: 8px;">Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr style="border-bottom: 1px solid #F7F4F0;">
                    <td style="padding: 10px 0;">
                        <strong style="color: #1a1a1a; font-size: 13px;">{{ $item->product_name }}</strong>
                        @if($item->variant_description)
                            <div style="color: #666; font-size: 12px; margin-top: 2px;">{{ $item->variant_description }}</div>
                        @endif
                    </td>
                    <td align="center" style="padding: 10px 0; color: #1a1a1a; font-weight: 600;">{{ $item->quantity }}</td>
                    <td align="right" style="padding: 10px 0; color: #1a1a1a;">
                        ₦{{ number_format((float) $item->total_price, 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Admin CTA -->
    <div style="text-align: center; margin: 32px 0 16px 0;">
        <a href="{{ url('/admin/orders/' . $order->id) }}" class="button">
            View &amp; Dispatch in Admin
        </a>
    </div>
@endsection
