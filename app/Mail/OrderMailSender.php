<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Address;

/**
 * Addresses shared by the order emails: they are sent from the orders mailbox
 * (config mail.orders_from), and customers' replies go to the store contact.
 */
final class OrderMailSender
{
    public static function from(): Address
    {
        return new Address(config('mail.orders_from.address'), config('mail.orders_from.name'));
    }

    public static function storeContact(): Address
    {
        return new Address(config('store.email'), config('mail.from.name'));
    }
}
