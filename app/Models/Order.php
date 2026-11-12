<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'user_id', 'cart_id', 'status', 'payment_status', 'payment_reference',
        'subtotal', 'shipping_fee', 'vat_amount', 'vat_inclusive', 'free_shipping', 'total_amount',
        'customer_name', 'customer_email', 'customer_phone',
        'shipping_address', 'shipping_city', 'shipping_state', 'shipping_postcode', 'shipping_country',
        'shipping_carrier', 'tracking_number', 'stock_held_until',
        'notes', 'save_card',
        'routely_shipment_id', 'routely_tracking_number', 'routely_status', 'routely_fee', 'routely_response',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'vat_inclusive' => 'boolean',
        'free_shipping' => 'boolean',
        'total_amount' => 'decimal:2',
        'routely_fee' => 'decimal:2',
        'routely_response' => 'array',
        'save_card' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public static function generateOrderNumber(): string
    {
        return 'FORME-'.strtoupper(uniqid());
    }

    /**
     * A fresh Paystack reference for each payment attempt. Paystack rejects a
     * repeated reference, so retries must not reuse one.
     */
    public function newPaymentReference(): string
    {
        return $this->order_number.'.'.Str::lower(Str::random(8));
    }

    public static function findByPaymentReference(?string $reference): ?self
    {
        if (! $reference) {
            return null;
        }

        return self::where('order_number', Str::before($reference, '.'))->first();
    }

    /**
     * A link anyone holding it can open, so guests can reach their order from email.
     */
    public function confirmationUrl(): string
    {
        return URL::signedRoute('order.confirmation', ['orderNumber' => $this->order_number]);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /** Going outside Nigeria, so shipped by hand (DHL) rather than Routely. */
    public function isInternational(): bool
    {
        return filled($this->shipping_country) && $this->shipping_country !== 'Nigeria';
    }

    /** The courier's public tracking page, when the courier is one we know. */
    public function trackingUrl(): ?string
    {
        if (! $this->tracking_number) {
            return null;
        }

        $carrier = strtolower((string) $this->shipping_carrier);

        // A DHL waybill. Routely bookings carry Routely's own tracking number,
        // which DHL's site wouldn't recognise, so no link for those.
        return str_contains($carrier, 'dhl') && ! str_contains($carrier, 'routely')
            ? 'https://www.dhl.com/global-en/home/tracking/tracking-express.html?submit=1&tracking-id='.rawurlencode($this->tracking_number)
            : null;
    }

    /** "12 Baker St, London, NW1 6XE, United Kingdom" — the parts that are filled in. */
    public function fullAddress(): string
    {
        return collect([$this->shipping_address, $this->shipping_city, $this->shipping_state, $this->shipping_postcode, $this->shipping_country])
            ->filter(fn ($part) => filled($part))
            ->implode(', ');
    }
}
