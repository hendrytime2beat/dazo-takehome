<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\Analytics\AnalyticsClient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class DashboardController extends Controller
{
    private const PERIODS = [
        '7d' => 7,
        '30d' => 30,
        '90d' => 90,
        'all' => null,
    ];

    public function __construct(private readonly AnalyticsClient $analytics) {}

    public function index(Request $request): Response
    {
        $period = $request->query('period', '30d');

        if (! array_key_exists($period, self::PERIODS)) {
            $period = '30d';
        }

        $from = self::PERIODS[$period] === null
            ? null
            : Carbon::today()->subDays(self::PERIODS[$period]);

        $ordersQuery = Order::query()
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->select(['id', 'created_at', 'status', 'total_amount', 'shipping_cost']);

        $orders = $ordersQuery->get();

        $items = $orders->isEmpty()
            ? collect()
            : \App\Models\OrderItem::query()
                ->whereIn('order_id', $orders->pluck('id'))
                ->select(['product_name', 'quantity', 'line_total'])
                ->get();

        $payload = [
            'orders' => $orders->map(fn (Order $order) => [
                'created_at' => $order->created_at->toISOString(),
                'status' => $order->status,
                'total_amount' => (float) $order->total_amount,
                'shipping_cost' => (float) $order->shipping_cost,
            ])->values()->all(),
            'order_items' => $items->map(fn ($item) => [
                'product_name' => $item->product_name,
                'quantity' => (int) $item->quantity,
                'line_total' => (float) $item->line_total,
            ])->values()->all(),
        ];

        $analyticsOk = true;

        try {
            $result = $this->analytics->compute($payload);
        } catch (Throwable $e) {
            $analyticsOk = false;
            $result = $this->fallbackCompute($payload);
        }

        $recentOrders = Order::query()
            ->withCount('items')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'status' => $order->status,
                'total_amount' => $order->total_amount,
                'created_at' => $order->created_at->diffForHumans(),
                'items_count' => $order->items_count,
            ]);

        $range = $orders->isNotEmpty()
            ? [$orders->min('created_at'), $orders->max('created_at')]
            : [Carbon::today(), Carbon::today()];

        $lowStock = Product::query()
            ->where('is_active', true)
            ->where('stock', '<=', 5)
            ->orderBy('stock')
            ->limit(5)
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'stock' => $product->stock,
            ]);

        return Inertia::render('Dashboard/Index', [
            'period' => $period,
            'from' => $range[0]->format('Y-m-d'),
            'to' => $range[1]->format('Y-m-d'),
            'analytics_ok' => $analyticsOk,
            'metrics' => $result['summary'] ?? [],
            'sales_series' => $result['sales_series'] ?? [],
            'payment_split' => $result['payment_split'] ?? ['paid' => 0, 'pending' => 0],
            'top_products' => $result['top_products'] ?? [],
            'recent_orders' => $recentOrders,
            'low_stock_products' => $lowStock,
            'product_count' => Product::where('is_active', true)->count(),
        ]);
    }

    /**
     * Local mirror of the Golang aggregation. Only used when the analytics
     * service is unreachable so the dashboard degrades gracefully instead of
     * erroring out. The UI exposes that numbers are stale/local.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function fallbackCompute(array $payload): array
    {
        $orders = $payload['orders'];
        $items = $payload['order_items'];

        $paid = collect($orders)->where('status', 'paid');
        $pending = collect($orders)->where('status', 'pending');

        $totalOrders = count($orders);
        $totalRevenue = collect($orders)->sum('total_amount');
        $shipping = collect($orders)->sum('shipping_cost');
        $paidCount = $paid->count();
        $pendingCount = $pending->count();

        $series = collect($orders)
            ->groupBy(fn ($o) => Carbon::parse($o['created_at'])->utc()->format('Y-m-d'))
            ->map(fn ($rows, $day) => [
                'date' => $day,
                'revenue' => $rows->sum('total_amount'),
                'orders' => $rows->count(),
            ])
            ->sortBy('date')
            ->values()
            ->all();

        $top = collect($items)
            ->groupBy('product_name')
            ->map(fn ($rows) => [
                'name' => $rows->first()['product_name'],
                'quantity' => $rows->sum('quantity'),
                'revenue' => $rows->sum('line_total'),
            ])
            ->sortByDesc('quantity')
            ->take(5)
            ->values()
            ->all();

        return [
            'summary' => [
                'total_orders' => $totalOrders,
                'total_revenue' => round($totalRevenue, 2),
                'total_shipping_cost' => round($shipping, 2),
                'paid_orders' => $paidCount,
                'unpaid_orders' => $pendingCount,
                'paid_percentage' => $totalOrders ? round($paidCount / $totalOrders * 100, 2) : 0,
                'unpaid_percentage' => $totalOrders ? round($pendingCount / $totalOrders * 100, 2) : 0,
                'average_order_value' => $totalOrders ? round($totalRevenue / $totalOrders, 2) : 0,
                'total_items_sold' => collect($items)->sum('quantity'),
                'paid_total' => round($paid->sum('total_amount'), 2),
                'unpaid_total' => round($pending->sum('total_amount'), 2),
                'net_revenue' => round($totalRevenue - $shipping, 2),
                'profit' => round(collect($items)->sum('line_total') * 0.4, 2),
            ],
            'sales_series' => $series,
            'payment_split' => [
                'paid' => round($paid->sum('total_amount'), 2),
                'pending' => round($pending->sum('total_amount'), 2),
            ],
            'top_products' => $top,
        ];
    }
}