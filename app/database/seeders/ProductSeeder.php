<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['sku' => 'DAZO-ELEC-001', 'name' => 'Wireless Mouse MX-3', 'category' => 'Elektronik', 'price' => 289000, 'stock' => 500],
            ['sku' => 'DAZO-ELEC-002', 'name' => 'Mechanical Keyboard K87', 'category' => 'Elektronik', 'price' => 875000, 'stock' => 500],
            ['sku' => 'DAZO-ELEC-003', 'name' => 'USB-C Hub 7-in-1', 'category' => 'Elektronik', 'price' => 425000, 'stock' => 500],
            ['sku' => 'DAZO-ELEC-004', 'name' => 'Webcam Full HD 1080p', 'category' => 'Elektronik', 'price' => 510000, 'stock' => 500],
            ['sku' => 'DAZO-GEAR-001', 'name' => 'Laptop Stand Aluminium', 'category' => 'Aksesoris', 'price' => 265000, 'stock' => 500],
            ['sku' => 'DAZO-GEAR-002', 'name' => 'Lapangan Karet Anti-Slip', 'category' => 'Aksesoris', 'price' => 145000, 'stock' => 500],
            ['sku' => 'DAZO-GEAR-003', 'name' => 'Headphone Bluetooth Pro', 'category' => 'Audio', 'price' => 735000, 'stock' => 500],
            ['sku' => 'DAZO-AUDIO-001', 'name' => 'Speaker Bluetooth Mini', 'category' => 'Audio', 'price' => 395000, 'stock' => 500],
            ['sku' => 'DAZO-MOB-001', 'name' => 'Power Bank 20000mAh', 'category' => 'Mobilitas', 'price' => 459000, 'stock' => 500],
            ['sku' => 'DAZO-MOB-002', 'name' => 'Tas Ransel Laptop 15.6"', 'category' => 'Mobilitas', 'price' => 550000, 'stock' => 500],
            ['sku' => 'DAZO-MOB-003', 'name' => 'Charger GaN 65W', 'category' => 'Elektronik', 'price' => 329000, 'stock' => 500],
            ['sku' => 'DAZO-HOME-001', 'name' => 'Lampu Meja LED Dimmable', 'category' => 'Rumah', 'price' => 218000, 'stock' => 500],
        ];

        foreach ($products as $product) {
            Product::query()->updateOrCreate(
                ['sku' => $product['sku']],
                array_merge($product, [
                    'description' => "Produk {$product['name']} untuk kebutuhan harian dan produktivitas.",
                    'is_active' => true,
                ]),
            );
        }
    }
}