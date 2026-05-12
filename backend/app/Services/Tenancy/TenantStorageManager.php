<?php

namespace App\Services\Tenancy;

use App\Models\Tenant;
use App\Support\Tenancy\TenantFilesystemSuffix;
use Illuminate\Filesystem\Filesystem;

class TenantStorageManager
{
    public function __construct(
        protected Filesystem $filesystem,
        protected ?string $baseStoragePath = null,
    ) {}

    public function tenantStoragePath(Tenant $tenant): string
    {
        return $this->baseStoragePath() . DIRECTORY_SEPARATOR . TenantFilesystemSuffix::for($tenant);
    }

    public function legacyTenantStoragePath(Tenant $tenant): string
    {
        return $this->baseStoragePath() . DIRECTORY_SEPARATOR . TenantFilesystemSuffix::legacyFor($tenant);
    }

    public function ensure(Tenant $tenant): void
    {
        $this->migrateLegacyStorage($tenant);

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

    public function migrateLegacyStorage(Tenant $tenant): void
    {
        $root = $this->tenantStoragePath($tenant);
        $legacyRoot = $this->legacyTenantStoragePath($tenant);

        if ($legacyRoot === $root || ! $this->filesystem->isDirectory($legacyRoot)) {
            return;
        }

        if (! $this->filesystem->isDirectory($root)) {
            $this->filesystem->ensureDirectoryExists(dirname($root));

            if (! $this->filesystem->moveDirectory($legacyRoot, $root, true)) {
                $this->filesystem->copyDirectory($legacyRoot, $root);
            }

            if ($this->filesystem->isDirectory($legacyRoot)) {
                $this->filesystem->deleteDirectory($legacyRoot);
            }

            return;
        }

        $this->filesystem->copyDirectory($legacyRoot, $root);
        $this->filesystem->deleteDirectory($legacyRoot);
    }

    public function delete(Tenant $tenant): void
    {
        $root = $this->tenantStoragePath($tenant);
        $legacyRoot = $this->legacyTenantStoragePath($tenant);

        if ($this->filesystem->isDirectory($root)) {
            $this->filesystem->deleteDirectory($root);
        }

        if ($legacyRoot !== $root && $this->filesystem->isDirectory($legacyRoot)) {
            $this->filesystem->deleteDirectory($legacyRoot);
        }
    }

    protected function baseStoragePath(): string
    {
        return rtrim($this->baseStoragePath ?? base_path('storage'), DIRECTORY_SEPARATOR);
    }
}
