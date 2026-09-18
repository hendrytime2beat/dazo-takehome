<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$request = Illuminate\Http\Request::create('/?period=all', 'GET');

// Auth sebagai user demo untuk menembus middleware 'auth'.
Illuminate\Support\Facades\Auth::login(App\Models\User::firstOrFail(), false);

$response = $app->make(Illuminate\Foundation\Http\Kernel::class)->handle($request);
$html = $response->getContent();

if (preg_match('/<script data-page[^>]*>(.*?)<\/script>/s', $html, $m)) {
    $d = json_decode($m[1], true);
    $p = $d['props'];
    echo 'status='.$response->getStatusCode().PHP_EOL;
    echo 'component='.$d['component'].PHP_EOL;
    echo 'analytics_ok='.var_export($p['analytics_ok'] ?? null, true).PHP_EOL;
    echo 'total_revenue='.json_encode($p['metrics']['total_revenue'] ?? null).PHP_EOL;
    echo 'total_orders='.json_encode($p['metrics']['total_orders'] ?? null).PHP_EOL;
    echo 'series='.count($p['sales_series'] ?? []).PHP_EOL;
    echo 'recent='.count($p['recent_orders'] ?? []).PHP_EOL;
    echo 'top='.count($p['top_products'] ?? []).PHP_EOL;
    echo 'low_stock='.count($p['low_stock_products'] ?? []).PHP_EOL;
    echo 'period='.json_encode($p['period'] ?? null).PHP_EOL;
} else {
    echo 'DATA_PAGE_NOT_FOUND'.PHP_EOL;
    echo substr($html, 0, 500).PHP_EOL;
}