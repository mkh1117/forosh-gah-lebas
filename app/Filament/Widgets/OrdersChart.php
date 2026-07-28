<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;


class OrdersChart extends ChartWidget
{
    // کلمه static از پشت این متغیرها حذف شد
    protected ?string $heading = 'روند فروش و تعداد سفارشات ۷ روز اخیر';

    protected ?string $pollingInterval = '30s';

    protected function getData(): array
{

    $startDate = Carbon::now()->subDays(6)->startOfDay();
    $endDate = Carbon::now()->endOfDay();


    $orders = Order::whereBetween('created_at', [$startDate, $endDate])->get();

    $dates = [];
    $ordersCountData = [];
    $salesData = [];


    for ($i = 6; $i >= 0; $i--) {
        $date = Carbon::now()->subDays($i);
        $dateFormatted = $date->format('Y-m-d');



        $dayOrders = $orders->filter(function ($order) use ($dateFormatted) {
            return Carbon::parse($order->created_at)->format('Y-m-d') === $dateFormatted;
        });


        $dailyOrdersCount = $dayOrders->count();
        $dailySales = $dayOrders->where('status', '!=', 'cancelled')->sum('total_price');

        $dates[] = $date->format('m/d');
        $ordersCountData[] = $dailyOrdersCount;
        $salesData[] = $dailySales;
    }

    return [
        'datasets' => [
            [
                'label' => 'تعداد سفارشات',
                'data' => $ordersCountData,
                'borderColor' => '#3b82f6',
                'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                'fill' => true,
            ],
            [
                'label' => 'مبلغ فروش (تومان)',
                'data' => $salesData,
                'borderColor' => '#10b981',
                'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                'fill' => true,
            ],
        ],
        'labels' => $dates,
    ];
}

    protected function getType(): string
    {
        return 'line';
    }
}
