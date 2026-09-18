<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder lengkap: produk, admin user, order beserta item untuk menguji
 * dashboard & seluruh analytics tanpa input manual.
 */
class AnalyticsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ProductSeeder::class);

        $this->seedAdminUser();
        $this->seedOrders();

        // Simulasikan sebagian produk yang stoknya menipis agar panel
        // "Stok Menipis" di dashboard punya data untuk ditampilkan.
        Product::query()
            ->whereIn('id', Product::query()->where('is_active', true)->inRandomOrder()->limit(3)->pluck('id'))
            ->each(function (Product $product) {
                $product->update(['stock' => random_int(0, 4)]);
            });
    }

    private function seedAdminUser(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@dazo.test'],
            ['name' => 'Admin DAZO', 'password' => 'password'],
        );
    }

    private function seedOrders(): void
    {
        // Idempotent: hapus data order lama supaya seeder aman dijalankan ulang.
        // OrderItem terhapus otomatis lewat foreign key ON DELETE CASCADE.
        Order::query()->delete();

        $products = Product::query()->where('is_active', true)->get();
        $customers = [
            ['name' => 'Andi Pratama', 'email' => 'andi@example.com', 'phone' => '0812-3456-7801', 'city' => 'Jakarta'],
            ['name' => 'Budi Santoso', 'email' => 'budi@example.com', 'phone' => '0812-3456-7802', 'city' => 'Bandung'],
            ['name' => 'Citra Dewi', 'email' => 'citra@example.com', 'phone' => '0812-3456-7803', 'city' => 'Surabaya'],
            ['name' => 'Deni Firmansyah', 'email' => 'deni@example.com', 'phone' => '0812-3456-7804', 'city' => 'Yogyakarta'],
            ['name' => 'Eka Lestari', 'email' => 'eka@example.com', 'phone' => '0812-3456-7805', 'city' => 'Semarang'],
            ['name' => 'Fajar Nugroho', 'email' => 'fajar@example.com', 'phone' => '0812-3456-7806', 'city' => 'Malang'],
            ['name' => 'Gita Ramadhani', 'email' => 'gita@example.com', 'phone' => '0812-3456-7807', 'city' => 'Medan'],
            ['name' => 'Hendra Wijaya', 'email' => 'hendra@example.com', 'phone' => '0812-3456-7808', 'city' => 'Makassar'],
            ['name' => 'Intan Kusuma', 'email' => 'intan@example.com', 'phone' => '0812-3456-7809', 'city' => 'Denpasar'],
            ['name' => 'Joko Prasetyo', 'email' => 'joko@example.com', 'phone' => '0812-3456-7810', 'city' => 'Palembang'],
        ];

        $paymentMethods = ['Transfer Bank', 'Kartu Kredit', 'E-Wallet', 'Cash on Delivery'];
        $days = 90;
        $productIds = $products->modelKeys();

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $recencyBoost = ($days - $i) / $days; // newer days sell more
            $orderCount = random_int(1, 4) + (int) floor($recencyBoost * 3);

            for ($j = 0; $j < $orderCount; $j++) {
                $this->createOrder($date, $productIds, $customers, $paymentMethods);
            }
        }
    }

    /**
     * @param  list<int>  $productIds
     */
    private function createOrder($date, array $productIds, array $customers, array $paymentMethods): void
    {
        $customer = $customers[array_rand($customers)];
        $createdAt = $date->copy()->addMinutes(random_int(0, 1400));

        $orderItems = [];
        $subtotal = 0;
        $usedProductIds = [];

        for ($k = random_int(1, 4); $k > 0; $k--) {
            $product = $this->pickProductWithStock($productIds, $usedProductIds);

            if ($product === null) {
                break; // semua produk habis stoknya
            }

            $usedProductIds[] = $product->id;
            $stockNow = (int) Product::query()->where('id', $product->id)->value('stock');

            if ($stockNow <= 0) {
                continue;
            }

            $quantity = random_int(1, min(3, $stockNow));
            $lineTotal = (float) $product->price * $quantity;

            $orderItems[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'unit_price' => $product->price,
                'quantity' => $quantity,
                'line_total' => $lineTotal,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];

            $subtotal += $lineTotal;
        }

        if ($orderItems === []) {
            return;
        }

        $isPaid = mt_rand(1, 100) <= 72; // 72% paid, 28% pending
        $shippingCost = in_array($customer['city'], ['Jakarta', 'Bandung', 'Surabaya', 'Medan']) ? 25000 : 45000;

        $order = Order::query()->create([
            'order_number' => 'ORD-'.$date->format('Ymd').'-'.strtoupper(substr(uniqid('', true), -6)),
            'customer_name' => $customer['name'],
            'customer_email' => $customer['email'],
            'customer_phone' => $customer['phone'],
            'shipping_address' => 'Jl. Contoh No. '.random_int(1, 200).', '.$customer['city'],
            'payment_method' => $paymentMethods[array_rand($paymentMethods)],
            'status' => $isPaid ? Order::STATUS_PAID : Order::STATUS_PENDING,
            'shipping_cost' => $shippingCost,
            'total_amount' => round($subtotal + $shippingCost, 2),
            'paid_at' => $isPaid ? $date->copy()->addMinutes(random_int(5, 300)) : null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        foreach ($orderItems as &$item) {
            $item['order_id'] = $order->id;
        }

        OrderItem::query()->insert($orderItems);

        $quantitiesByProduct = [];
        foreach ($orderItems as $item) {
            $quantitiesByProduct[$item['product_id']] = ($quantitiesByProduct[$item['product_id']] ?? 0) + $item['quantity'];
        }

        foreach ($quantitiesByProduct as $productId => $totalQty) {
            Product::query()
                ->where('id', $productId)
                ->where('stock', '>=', $totalQty)
                ->decrement('stock', $totalQty);
        }
    }

    /**
     * @param  list<int>  $productIds
     * @param  list<int>  $usedProductIds
     */
    private function pickProductWithStock(array $productIds, array $usedProductIds): ?Product
    {
        $candidates = Product::query()
            ->whereIn('id', $productIds)
            ->whereNotIn('id', $usedProductIds)
            ->where('stock', '>', 0)
            ->inRandomOrder()
            ->first();

        if ($candidates !== null) {
            return $candidates;
        }

        // Semua produk yang belum dipilih habis; gunakan yang masih ada stok.
        return Product::query()
            ->whereIn('id', $productIds)
            ->where('stock', '>', 0)
            ->inRandomOrder()
            ->first();
    }
}