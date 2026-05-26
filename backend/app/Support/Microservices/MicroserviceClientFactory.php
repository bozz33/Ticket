<?php

namespace App\Support\Microservices;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MicroserviceClientFactory
{
    public function __construct(private readonly MicroserviceRegistry $registry) {}

    public function for(
        string $service,
        ?string $tenantId = null,
        ?string $correlationId = null,
        array $headers = [],
    ): PendingRequest {
        $forwardHeaders = array_filter([
            'X-Correlation-ID' => $correlationId ?: (string) Str::uuid(),
            'X-Tenant-ID' => $tenantId,
        ], fn (?string $value): bool => $value !== null && $value !== '');

        $internalToken = $this->registry->internalToken();

        if ($internalToken !== null) {
            $forwardHeaders[$this->registry->internalTokenHeader()] = $internalToken;
        }

        return Http::acceptJson()
            ->asJson()
            ->baseUrl($this->registry->baseUrl($service))
            ->timeout($this->registry->timeout())
            ->connectTimeout($this->registry->connectTimeout())
            ->retry($this->registry->retryTimes(), $this->registry->retrySleepMs(), throw: false)
            ->withHeaders(array_merge($forwardHeaders, $headers));
    }

    public function enabled(string $service): bool
    {
        return $this->registry->isEnabled($service);
    }
}
