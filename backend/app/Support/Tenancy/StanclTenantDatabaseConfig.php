<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;
use Stancl\Tenancy\DatabaseConfig;

class StanclTenantDatabaseConfig extends DatabaseConfig
{
    public function __construct(Tenant $tenant)
    {
        parent::__construct($tenant);
    }

    public function getName(): ?string
    {
        return $this->tenant->database_name ?? parent::getName();
    }

    public function getTemplateConnectionName(): string
    {
        return config('tenancy.database.template_tenant_connection', 'tenant_template');
    }

    public function tenantConfig(): array
    {
        $config = array_filter([
            'host' => $this->tenant->database_host,
            'port' => $this->tenant->database_port,
            'username' => $this->tenant->database_username,
            'password' => $this->tenant->database_password,
        ], fn (mixed $value): bool => $value !== null && $value !== '');

        return array_merge($config, is_array($this->tenant->database_options) ? $this->tenant->database_options : []);
    }
}
