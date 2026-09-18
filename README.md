# DAZO — Fullstack Take-Home Test

Mini dashboard penjualan hasil take‑home test **DAZO**. Dua layer terpisah:

- **`app/`** — Monolith Laravel 12 + Inertia.js (Vue 3 + Tailwind CSS) sebagai web app.
- **`analytics-service/`** — Layanan analitik terpisah berbasis **Golang** (stdlib HTTP), diakses Laravel lewat HTTP API.

## Arsitektur

```
Browser (Vue 3 + ApexCharts)
   │ Inertia
   ▼
Laravel (routes / controllers / OrderService)
   │ HTTP POST /api/analytics   │ DB (MySQL: products, orders, order_items, users)
   ▼                            ▼
Golang analytics-service     MariaDB / MySQL
```

- **Buat pesanan** berjalan transaksional di Laravel (`OrderService`): row‑lock per produk (`lockForUpdate`), validasi stok server‑side, harga diambil dari DB (bukan dikirim client), stok di‑decrement hanya saat keseluruhan order *commit*.
- **Dashboard** meminta agregasi (KPI, revenue per hari, produk terlaris, stok menipis) ke Golang service via `POST /api/analytics`. Jika layanan Golang mati/ekses timeout, Laravel **fallback hitung lokal** dari DB sehingga halaman tetap render (lihatan `analytics_ok` di props).
- Datetime order disimpan **UTC**, dikelompokkan per `DATE(created_at)` sesuai zona server.

## Prasyarat

- PHP **8.2+** (proyek ini dikembangkan di 8.2.30)
- Composer 2.x, Node 20+
- MySQL 8 / MariaDB (PostgreSQL juga didukung, urutan migrasi sudah aman untuk MySQL)
- Go 1.2x (untuk analytics service)

## Setup

```bash
# 1. Buat database
mysql -u root -e "CREATE DATABASE IF NOT EXISTS dazo_takehome CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Aplikasi Laravel
cd app
cp .env.example .env
php artisan key:generate

# 3. Seeder — Wajib menjalankan keduanya sesuai kebutuhan
#    a. Product Seeder → hanya data produk (untuk menguji flow order dari kondisi awal):
php artisan db:seed --class=ProductSeeder
#    b. Complete / Analytics Seeder → produk + order + order item + user (dashboard langsung terisi):
php artisan migrate:fresh --seed --seeder=AnalyticsSeeder
#    - 12 produk (3 di antaranya sengaja stok rendah 0–4 utk menampilkan panel “Stok Menipis”)
#    - ~300 order tersebar 90 hari terakhir (paid/unpaid, ecoupon/applied, free shipping)
#    - user admin: admin@dazo.test / password
#    AnalyticsSeeder idempotent — aman dijalankan berulang (order lama dibersihkan dulu).

# 4. Frontend
npm install
npm run build

# 5. Layanan Golang (terminal 1)  → port 9090
cd ../analytics-service
go build -o bin/analytics-service ./cmd/server
./bin/analytics-service
# cek: curl http://127.0.0.1:9090/healthz → {"status":"ok"}

# 6. Server Laravel (terminal 2)  → port 8000
cd ../app
php artisan serve --host=127.0.0.1 --port=8000

# 7. Buka http://127.0.0.1:8000  — login: admin@dazo.test / password
```

## Halaman & fitur

| Halaman | Fitur |
|---|---|
| `/login` | Layout 2 kolom (form kiri, panel ilustrasi CS kanan), auth Laravel native (session, CSRF); login via email **atau** nomor WhatsApp, show/hide password, captcha demo, tombol Google (placeholder), halaman `/forgot-password` & `/register` |
| `/` (Dashboard) | Admin SaaS: sidebar 120px (collapse + drawer mobile), 4 kartu KPI (Total Pesanan, Pendapatan Kotor/Bersih, Laba Kotor), grafik penjualan 2 seri (pesanan + pendapatan) dengan pemilih periode tanggal, panel statistik (total pembayaran, % sudah/belum bayar, biaya kirim), 3 kartu Monitoring, tombol chat melayang (demo), loading skeleton & empty state |
| `/orders` | Daftar order (pagination), status badge |
| `/orders/create` | Form multi‑item; qty melebihi stok ditolak (error dibawa ke client) |
| `/products` | Katalog produk + stok real‑time |

## Keputusan teknis & trade‑off

1. **Analitik di service terpisah (Go)** — sesuai spesifikasi: Laravel tidak menghitung agregasi sendiri; ia mengirim seluruh dataset order (batas 1.000 order terbaru) ke Golang dan menyajikan hasilnya. Trade‑off: pengiriman dataset penuh tidak menskalakan jika order ratusan ribu; untuk skala take‑home & mekanisme “service terpisah” ini sudah cukup. Sudah disediakan `limit` pada payload + fallback lokal bila Go down.
2. **Row‑lock + transaction** untuk pembuatan order — mencegah overselling saat 2 request berbarengan. Trade‑off: menahan baris stok selama transaksi, namun transaksi singkat sehingga tidak masalah di skala ini. Stock decrement hanya terjadi setelah seluruh validasi lolos.
3. **Harga & stok diambil server‑side**, item diterima hanya `product_id + quantity` — mencegah tamper harga via request.
4. **Fallback lokal dashboard** — Go service mati tidak membuat aplikasi kosong; halaman dirubah dengan `analytics_ok=false` sehingga UI bisa menampilkan banner degradasi.
5. **Migrasi diurutkan untuk MySQL** (`products` → `orders` → `order_items`) karena FK MySQL butuh tabel induk lebih dulu. Tidak ada cascade soft‑delete di tabel pivot: FK `ON DELETE CASCADE` dihapus → migrasi memakai FK map eksplisit, bukan `constrained()`+`cascadeOnDelete()`.
6. **UI tanpa referensi visual** — PDF soal tidak bisa dibuka dari lingkungan kerja (gambar tidak dapat dibaca), sehingga tata letak dashboard dibangun dari inisiatif sendiri (sidebar + header + kartu KPI + grafik). Struktur data & alur sesuai soal. Bisa disesuaikan jika desain final diberikan.
7. **Halaman login** mengikuti spesifikasi UI yang diberikan: lebar form ±400 px di kolom kiri (putih), panel kanan `#F4F8FF` berisi ilustrasi agent CS (komponen `resources/js/Components/CustomerServiceIllustration.vue` — SVG placeholder, mudah diganti gambar asli), input `#F5F8FC`, tombol utama abu muda, divider "atau", tombol Google, dan link Lupa password/Daftar. **Captcha & Google OAuth adalah placeholder** (captcha local tanpa verifikasi remote; Google button tidak terhubung OAuth) — siap diintegrasikan bila kredensial disediakan.
8. **Dashboard Admin/Merchant SaaS** dibangun mengikuti spesifikasi UI (tanpa akses ke gambar referensi): primary `#2949B5`, latar `#F7F9FE`, sidebar 120px yang bisa dilipat, tombol "Business Package" (dropdown demo), pemilih periode tanggal (7/30/90 hari / semua riwayat) yang di-reflect ke URL `?period=`.
9. **Kartu baru butuh data baru** — `Metrics` Golang & fallback Laravel diperluas dengan `paid_total`, `unpaid_total`, `net_revenue`, dan `profit`. **Laba Kotor adalah estimasi**: asumsi margin kotor 40% dari nilai produk (HPP 60%); ongkos kirim tidak masuk HPP. Bila data biaya riil tersedia, ganti konstanta `grossMargin` di `analytics-service/internal/analytics/analytics.go` (dan baris `* 0.4` di fallback `DashboardController`).

## Struktur

```
fullstack-takehome/
├── app/                      # Laravel monolith
│   ├── app/
│   │   ├── Http/Controllers/ # Auth, Dashboard, Order, Product
│   │   ├── Services/Orders/  # OrderService (transaction + lock)
│   │   ├── Services/Analytics/# AnalyticsClient (retry, timeout, fallback)
│   │   └── Models/           # Product, Order, OrderItem (+ User)
│   ├── database/
│   │   ├── migrations/       # 2026_09_18_012100_products / _orders / _order_items
│   │   └── seeders/          # ProductSeeder, AnalyticsSeeder (idempotent)
│   └── resources/js/         # Vue 3 : Pages, Layouts, Shared
└── analytics-service/        # Go service (cmd/server, internal/api, internal/analytics)
```

## Verifikasi cepat

```bash
cd app
php scripts/smoke_dashboard.php   # dashboard render + metrics (fallback saat Go mati)
php scripts/smoke_pages.php       # 4 halaman utama render status 200

# Uji order langsung
# melalui UI: /orders/create, isi qty melebihi stok → harus ditolak
```