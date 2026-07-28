<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Post;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrdersStatsOverview extends BaseWidget
{
    // کلمه static از اینجا حذف شد
    protected ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        // محاسبه مجموع فروش کل سفارشات غیرلغوشده
        $totalSales = Order::where('status', '!=', 'cancelled')->sum('total_price');

        // تعداد کل سفارشات
        $totalOrdersCount = Order::count();

        // تعداد سفارشات در انتظار پرداخت یا پردازش
        $pendingOrdersCount = Order::whereIn('status', ['pending', 'processing'])->count();

        // تعداد کل محصولات
        $totalProductsCount = Post::count();

        return [
            Stat::make('مجموع درآمد کل', number_format($totalSales) . ' تومان')
                ->description('حاصل از سفارشات موفق')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('تعداد کل سفارشات', number_format($totalOrdersCount))
                ->description('همه سفارشات ثبت‌شده')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),

            Stat::make('سفارشات نیازمند بررسی', number_format($pendingOrdersCount))
                ->description('در انتظار پرداخت یا پردازش')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('تعداد کل محصولات', number_format($totalProductsCount))
                ->description('محصولات فعال در فروشگاه')
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('info'),
        ];
    }
}
