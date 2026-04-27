<?php

namespace App\Services\Tenancy;

class CreateTenant
{
    public function handle(array $payload): array
    {
        return app(ProvisionTenant::class)->handle($payload);
    }
}
