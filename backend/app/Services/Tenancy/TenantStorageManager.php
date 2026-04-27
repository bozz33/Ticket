<?php

namespace App\Services\Tenancy;

use App\Models\Tenant;
use Illuminate\Filesystem\Filesystem;

class TenantStorageManager
{
    public function __construct(
        protected Filesystem $filesystem,
    ) {}

    public function tenantStoragePath(Tenant $tenant): string
    {
        return storage_path(sprintf('%s%s', config('tenancy.filesystem.suffix_base', 'tenant'), $tenant->getTenantKey()));
    }

    public function ensure(Tenant $tenant): void
    {
        $root = $this->tenantStoragePath($tenant);

        foreach ([
            $root,
            $root . DIRECTORY_SEPARATOR . 'app',
            $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'public',
            $root . DIRECTORY_SEPARATOR . 'framework',
            $root . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache',
            $root . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'sessions',
            $root . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'testing',
            $root . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'views',
            $root . DIRECTORY_SEPARATOR . 'logs',
        ] as $directory) {
            $this->filesystem->ensureDirectoryExists($directory);
        }
    }

    public function delete(Tenant $tenant): void
    {
        $root = $this->tenantStoragePath($tenant);

        if ($this->filesystem->isDirectory($root)) {
            $this->filesystem->deleteDirectory($root);
        }
    }
}
