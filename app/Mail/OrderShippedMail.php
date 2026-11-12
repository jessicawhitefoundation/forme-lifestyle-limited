<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderShippedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->order->loadMissing('items.variant');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: OrderMailSender::from(),
            replyTo: [OrderMailSender::storeContact()],
            subject: "Your FORMÉ Order #{$this->order->order_number} Has Shipped!",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.shipped',
        );
    }
}
