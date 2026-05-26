<?php

namespace App\Support\Microservices;

use InvalidArgumentException;

class MicroserviceRegistry
{
    /**
     * @return list<string>
     */
    public function names(): array
    {
        return MicroserviceNames::All;
    }

    public function isKnown(string $name): bool
    {
        return in_array($name, MicroserviceNames::All, true);
    }

    public function isEnabled(string $name): bool
    {
        $this->ensureKnown($name);

        return (bool) config("services.microservices.{$name}.enabled", false);
    }

    public function baseUrl(string $name): string
    {
        $this->ensureKnown($name);

        return rtrim((string) config("services.microservices.{$name}.url", ''), '/');
    }

    public function timeout(): int
    {
        return max(1, (int) config('services.microservices.timeout', 8));
    }

    public function connectTimeout(): int
    {
        return max(1, (int) config('services.microservices.connect_timeout', 3));
    }

    public function retryTimes(): int
    {
        return max(0, (int) config('services.microservices.retry_times', 2));
    }

    public function retrySleepMs(): int
    {
        return max(0, (int) config('services.microservices.retry_sleep_ms', 100));
    }

    public function internalToken(): ?string
    {
        $token = trim((string) config('services.microservices.internal_token', ''));

        return $token === '' ? null : $token;
    }

    public function internalTokenHeader(): string
    {
        $header = trim((string) config('services.microservices.internal_token_header', 'X-Internal-Service-Token'));

        return $header === '' ? 'X-Internal-Service-Token' : $header;
    }

    private function ensureKnown(string $name): void
    {
        if (! $this->isKnown($name)) {
            throw new InvalidArgumentException("Unknown microservice [{$name}].");
        }
    }
}
