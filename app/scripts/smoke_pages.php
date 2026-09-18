<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

Illuminate\Support\Facades\Auth::login(App\Models\User::firstOrFail(), false);

$kernel = $app->make(Illuminate\Foundation\Http\Kernel::class);

$routes = [
    '/?period=all' => 'Dashboard/Index',
    '/products' => 'Products/Index',
    '/orders' => 'Orders/Index',
    '/orders/create' => 'Orders/Create',
];

foreach ($routes as $path => $expectedComponent) {
    $response = $kernel->handle(Illuminate\Http\Request::create($path, 'GET'));
    $html = $response->getContent();

    if (preg_match('/<script data-page[^>]*>(.*?)<\/script>/s', $html, $m)) {
        $d = json_decode($m[1], true);
        $p = $d['props'];
        printf(
            "%-16s status=%d component=%-16s analytics_ok=%-5s metrics=%s\n",
            $path,
            $response->getStatusCode(),
            $d['component'],
            var_export($p['analytics_ok'] ?? null, true),
            json_encode($p['metrics']['total_orders'] ?? $p['products']['total'] ?? $p['orders']['total'] ?? null),
        );
    } else {
        printf("%-16s status=%d DATA_PAGE_NOT_FOUND %s\n", $path, $response->getStatusCode(), substr(strip_tags($html), 0, 80));
    }
}