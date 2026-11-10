<?php

namespace App\Console\Commands;

use App\Models\Cart;
use App\Models\Order;
use Illuminate\Console\Command;

class StoreCleanup extends Command
{
    protected $signature = 'store:cleanup';

    protected $description = 'Cancel abandoned unpaid orders and delete old guest carts';

    public function handle(): int
    {
        // Cancelled, not deleted: a late payment on an old payment page can still find its order.
        $cancelled = Order::where('payment_status', 'unpaid')
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subDays(2))
            ->update(['status' => 'cancelled']);

        $carts = Cart::whereNull('user_id')
            ->where('updated_at', '<', now()->subDays(30))
            ->delete();

        $this->info("Cancelled {$cancelled} abandoned orders and deleted {$carts} old guest carts.");

        return self::SUCCESS;
    }
}
