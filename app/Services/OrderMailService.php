<?php

namespace App\Services;

use App\Mail\NewOrderAdminMail;
use App\Mail\NewOrderCustomerMail;
use App\Mail\OrderShippedMail;
use App\Mail\WelcomeCustomerMail;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderMailService
{
    /**
     * Check if email credentials are configured before attempting dispatch.
     */
    private static function isMailConfigured(): bool
    {
        $mailer = config('mail.default', 'smtp');

        if ($mailer === 'smtp') {
            $username = config('mail.mailers.smtp.username') ?: env('MAIL_USERNAME');
            $password = config('mail.mailers.smtp.password') ?: env('MAIL_PASSWORD');

            if (empty($username) || empty($password)) {
                Log::info('Email sending deferred: SMTP credentials (MAIL_USERNAME/MAIL_PASSWORD) not yet configured in .env.');

                return false;
            }
        }

        return true;
    }

    /**
     * Send order confirmation to customer and notification to store owner/admin.
     * Uses dispatch()->afterResponse() so the customer's checkout completes instantly (<100ms).
     */
    public static function sendOrderPaidNotifications(Order $order): void
    {
        if (! static::isMailConfigured()) {
            return;
        }

        dispatch(function () use ($order) {
            try {
                $order->loadMissing('items.variant');

                // 1. Send customer confirmation
                if (! empty($order->customer_email)) {
                    Mail::to($order->customer_email)->send(new NewOrderCustomerMail($order));
                    Log::info("Order confirmation email sent to {$order->customer_email} for Order #{$order->order_number}");
                }

                // 2. Send owner/admin notification
                $adminEmail = config('mail.admin_address') ?: env('ADMIN_NOTIFICATION_EMAIL', 'hello@forme.ng');
                if (! empty($adminEmail)) {
                    Mail::to($adminEmail)->send(new NewOrderAdminMail($order));
                    Log::info("Admin new order notification sent to {$adminEmail} for Order #{$order->order_number}");
                }
            } catch (\Throwable $e) {
                Log::error("Failed to send order paid emails for Order #{$order->order_number}: {$e->getMessage()}", [
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        })->afterResponse();
    }

    /**
     * Send dispatch & tracking notification when order is shipped.
     */
    public static function sendOrderShippedNotification(Order $order): void
    {
        if (! static::isMailConfigured()) {
            return;
        }

        dispatch(function () use ($order) {
            try {
                $order->loadMissing('items.variant');

                if (! empty($order->customer_email)) {
                    Mail::to($order->customer_email)->send(new OrderShippedMail($order));
                    Log::info("Shipping tracking email sent to {$order->customer_email} for Order #{$order->order_number}");
                }
            } catch (\Throwable $e) {
                Log::error("Failed to send shipment email for Order #{$order->order_number}: {$e->getMessage()}");
            }
        })->afterResponse();
    }

    /**
     * Send welcome email to newly registered customer.
     */
    public static function sendWelcomeNotification(User $user): void
    {
        if (! static::isMailConfigured()) {
            return;
        }

        dispatch(function () use ($user) {
            try {
                if (! empty($user->email)) {
                    Mail::to($user->email)->send(new WelcomeCustomerMail($user));
                    Log::info("Welcome email sent to {$user->email}");
                }
            } catch (\Throwable $e) {
                Log::error("Failed to send welcome email to {$user->email}: {$e->getMessage()}");
            }
        })->afterResponse();
    }
}
