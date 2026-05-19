<?php

namespace Tests\Unit;

use App\Models\Tenant;
use App\Services\Tenancy\TenantStorageManager;
use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

class TenantStorageManagerTest extends TestCase
{
    public function test_it_migrates_legacy_tenant_storage_to_slug_storage(): void
    {
        $filesystem = new Filesystem;
        $baseStoragePath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'ticket-storage-manager-test-'.bin2hex(random_bytes(4));
        $manager = new TenantStorageManager($filesystem, $baseStoragePath);
        $tenant = new Tenant([
            'name' => 'Demo Tenant Storage',
            'slug' => 'demo-tenant-storage-test',
        ]);
        $tenant->id = 987654;

        $legacyPath = $manager->legacyTenantStoragePath($tenant);
        $slugPath = $manager->tenantStoragePath($tenant);

        $filesystem->deleteDirectory($legacyPath);
        $filesystem->deleteDirectory($slugPath);
        $filesystem->ensureDirectoryExists($legacyPath.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'public');
        $filesystem->put($legacyPath.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'marker.txt', 'ok');

        try {
            $manager->ensure($tenant);

            $this->assertDirectoryDoesNotExist($legacyPath);
            $this->assertDirectoryExists($slugPath);
            $this->assertFileExists($slugPath.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'marker.txt');
            $this->assertDirectoryExists($slugPath.DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR.'views');
        } finally {
            $filesystem->deleteDirectory($baseStoragePath);
        }
    }
}
