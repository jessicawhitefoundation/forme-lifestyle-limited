<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $paidOrders = Order::where('payment_status', 'paid')->where('status', '!=', 'cancelled');

        $revenue = (float) (clone $paidOrders)->sum('total_amount');
        $paidCount = (clone $paidOrders)->count();

        return [
            Stat::make('Total Revenue', '₦'.Number::format($revenue, 2))
                ->description("From {$paidCount} paid orders")
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Paid Orders', $paidCount)
                ->description('Unpaid checkouts are not counted')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),

            Stat::make('Customers', User::where('role', 'customer')->count())
                ->description('Registered accounts')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
        ];
    }
}
