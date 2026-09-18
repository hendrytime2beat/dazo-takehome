<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\Orders\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    public function index(Request $request): Response
    {
        $status = (string) $request->query('status', '');

        $orders = Order::query()
            ->withCount('items')
            ->when(in_array($status, [Order::STATUS_PAID, Order::STATUS_PENDING, Order::STATUS_CANCELLED], true),
                fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'filters' => ['status' => $status],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Orders/Create', [
            'products' => Product::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'shipping_address' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in([Order::STATUS_PENDING, Order::STATUS_PAID])],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $order = $this->orders->createOrder($data, $data['items']);

            return redirect()
                ->route('orders.index')
                ->with('success', "Order {$order->order_number} berhasil dibuat.");
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', 'Order gagal dibuat. Periksa kembali data dan stok produk.');
        }
    }
}