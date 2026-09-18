<?php

namespace App\Services\Analytics;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Thin HTTP client for the Golang analytics service.
 *
 * Failures are surfaced as exceptions so the caller can decide whether to
 * fall back to locally computed numbers (dashboard) or abort.
 */
class AnalyticsClient
{
    public function __construct(private readonly string $baseUrl) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function compute(array $payload): array
    {
        try {
            $response = Http::timeout(3)
                ->connectTimeout(2)
                ->retry(2, 150)
                ->post(rtrim($this->baseUrl, '/').'/api/analytics', $payload);

            if ($response->failed()) {
                throw new RuntimeException('Analytics service returned HTTP '.$response->status());
            }

            $result = $response->throw()->json();

            if (! is_array($result)) {
                throw new RuntimeException('Analytics service returned an invalid response');
            }

            return $result;
        } catch (ConnectionException|RuntimeException|Throwable $e) {
            Log::warning('Analytics service unreachable: '.$e->getMessage());

            throw $e;
        }
    }
}