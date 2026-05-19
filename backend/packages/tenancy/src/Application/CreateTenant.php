<?php

namespace Ticket\Tenancy\Application;

class CreateTenant
{
    public function handle(array $payload): array
    {
        return app(ProvisionTenant::class)->handle($payload);
    }
}
