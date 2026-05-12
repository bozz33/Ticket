<?php

namespace App\Tenancy\Bootstrappers;

use App\Models\Tenant;
use App\Services\Tenancy\TenantStorageManager;
use App\Support\Tenancy\TenantFilesystemSuffix;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;

class SlugFilesystemTenancyBootstrapper implements TenancyBootstrapper
{
    public array $originalPaths = [];

    public function __construct(
        protected Application $app,
    ) {
        $this->originalPaths = [
            'disks' => [],
            'storage' => $this->app->storagePath(),
            'asset_url' => $this->app['config']['app.asset_url'],
        ];

        $this->app['url']->macro('setAssetRoot', function ($root) {
            $reflection = new \ReflectionObject($this);
            $property = $reflection->getProperty('assetRoot');
            $property->setAccessible(true);
            $property->setValue($this, $root);

            return $this;
        });
    }

    public function bootstrap($tenant): void
    {
        if (! $tenant instanceof Tenant) {
            return;
        }

        $suffix = TenantFilesystemSuffix::for($tenant);

        app(TenantStorageManager::class)->ensure($tenant);

        if ($this->app['config']['tenancy.filesystem.suffix_storage_path'] ?? true) {
            $this->app->useStoragePath($this->originalPaths['storage'] . DIRECTORY_SEPARATOR . $suffix);
        }

        if ($this->app['config']['tenancy.filesystem.asset_helper_tenancy'] ?? true) {
            if ($this->originalPaths['asset_url']) {
                $this->app['config']['app.asset_url'] = ($this->originalPaths['asset_url'] ?? $this->app['config']['app.url']) . "/{$suffix}";
                $this->app['url']->setAssetRoot($this->app['config']['app.asset_url']);
            } else {
                $this->app['url']->setAssetRoot($this->app['url']->route('stancl.tenancy.asset', ['path' => '']));
            }
        }

        Storage::forgetDisk($this->app['config']['tenancy.filesystem.disks']);

        foreach ($this->app['config']['tenancy.filesystem.disks'] as $disk) {
            $originalRoot = $this->app['config']["filesystems.disks.{$disk}.root"];
            $this->originalPaths['disks'][$disk] = $originalRoot;

            $finalPrefix = str_replace(
                ['%storage_path%', '%tenant%'],
                [storage_path(), $suffix],
                $this->app['config']["tenancy.filesystem.root_override.{$disk}"] ?? '',
            );

            if (! $finalPrefix) {
                $finalPrefix = $originalRoot
                    ? rtrim($originalRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $suffix
                    : $suffix;
            }

            $this->app['config']["filesystems.disks.{$disk}.root"] = $finalPrefix;
        }
    }

    public function revert(): void
    {
        $this->app->useStoragePath($this->originalPaths['storage']);
        $this->app['config']['app.asset_url'] = $this->originalPaths['asset_url'];
        $this->app['url']->setAssetRoot($this->app['config']['app.asset_url']);

        Storage::forgetDisk($this->app['config']['tenancy.filesystem.disks']);

        foreach ($this->app['config']['tenancy.filesystem.disks'] as $disk) {
            $this->app['config']["filesystems.disks.{$disk}.root"] = $this->originalPaths['disks'][$disk];
        }
    }
}
