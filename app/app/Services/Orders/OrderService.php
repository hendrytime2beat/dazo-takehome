<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * Create an order with one or more product items inside a single database
     * transaction. Product prices are taken from the backend (DB), never from
     * the client. Product rows are locked (SELECT ... FOR UPDATE) while stock
     * is validated and decremented so concurrent orders cannot oversell.
     *
     * @param  array<string, mixed>             $data  order-level payload (customer, shipping, …)
     * @param  array<int, array<string, mixed>> $items [['product_id' => int, 'quantity' => int], …]
     *
     * @throws ValidationException when a product is missing or stock is insufficient
     */
    public function createOrder(array $data, array $items): Order
    {
        return DB::transaction(function () use ($data, $items) {
            $products = Product::query()
                ->whereIn('id', array_column($items, 'product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $orderItems = [];
            $subtotal = 0.0;

            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $quantity = (int) $item['quantity'];

                $product = $products->get($productId);

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => "Produk ID {$productId} tidak ditemukan.",
                    ]);
                }

                if ($quantity < 1) {
                    throw ValidationException::withMessages([
                        'items' => "Kuantitas harus minimal 1 untuk {$product->name}.",
                    ]);
                }

                if ($product->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => "Stok tidak mencukupi untuk {$product->name} (stok: {$product->stock}).",
                    ]);
                }

                $lineTotal = (float) $product->price * $quantity;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                ];

                $subtotal += $lineTotal;
            }

            $shippingCost = (float) ($data['shipping_cost'] ?? 0);
            $totalAmount = $subtotal + $shippingCost;

            /** @var Order $order */
            $order = Order::query()->create([
                'order_number' => $this->generateOrderNumber(),
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'status' => $data['status'] ?? Order::STATUS_PENDING,
                'shipping_cost' => $shippingCost,
                'total_amount' => $totalAmount,
                'paid_at' => ($data['status'] ?? null) === Order::STATUS_PAID ? now() : null,
            ]);

            $order->items()->createMany($orderItems);

            foreach ($orderItems as $orderItem) {
                $products[$orderItem['product_id']]->decrement('stock', $orderItem['quantity']);
            }

            return $order;
        });
    }

    private function generateOrderNumber(): string
    {
        // Order numbers only need to be unique and human friendly for this
        // test; a real system may prefer UUIDs or a running-sequence number.
        do {
            $number = 'ORD-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }
}