<?php

namespace Ticket\Tenancy\Contracts;

interface TenantProvisioner
{
    public function handle(array $payload): array;
}
