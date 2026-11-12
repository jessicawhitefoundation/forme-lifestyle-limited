<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewOrderAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->order->loadMissing('items.variant');
    }

    public function envelope(): Envelope
    {
        $totalFormatted = number_format((float) $this->order->total_amount, 2);

        // Hitting Reply on the alert writes straight to the customer.
        $replyTo = filter_var($this->order->customer_email, FILTER_VALIDATE_EMAIL)
            ? [new Address($this->order->customer_email, $this->order->customer_name)]
            : [];

        return new Envelope(
            from: OrderMailSender::from(),
            replyTo: $replyTo,
            subject: "🚨 [New Order] #{$this->order->order_number} · ₦{$totalFormatted} from {$this->order->customer_name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.admin-notification',
        );
    }
}
