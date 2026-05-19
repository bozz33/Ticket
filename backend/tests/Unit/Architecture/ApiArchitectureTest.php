<?php

namespace Tests\Unit\Architecture;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class ApiArchitectureTest extends TestCase
{
    public function test_api_controllers_use_form_requests_instead_of_inline_validation(): void
    {
        $violations = [];

        foreach ($this->phpFiles(app_path('Http/Controllers/Api')) as $file) {
            $contents = (string) file_get_contents($file->getPathname());

            foreach (['$request->validate(', 'request()->validate('] as $forbidden) {
                if (str_contains($contents, $forbidden)) {
                    $violations[] = sprintf('%s contains %s', $file->getPathname(), $forbidden);
                }
            }
        }

        $this->assertSame([], $violations);
    }

    public function test_api_routes_are_split_by_surface(): void
    {
        $entrypoint = (string) file_get_contents(base_path('routes/api.php'));

        foreach ([
            "require __DIR__.'/api/public.php';",
            "require __DIR__.'/api/webhooks.php';",
            "require __DIR__.'/api/platform.php';",
            "require __DIR__.'/api/tenant.php';",
        ] as $requiredRouteFile) {
            $this->assertStringContainsString($requiredRouteFile, $entrypoint);
        }
    }

    public function test_shared_payment_status_rules_are_not_duplicated_in_application_code(): void
    {
        $duplicatedLiteral = "['success', 'successful', 'confirmed', 'completed', 'paid']";
        $violations = [];

        foreach ($this->phpFiles(app_path()) as $file) {
            $contents = (string) file_get_contents($file->getPathname());

            if (str_contains($contents, $duplicatedLiteral)) {
                $violations[] = $file->getPathname();
            }
        }

        $this->assertSame([], $violations);
    }

    public function test_public_catalog_module_owns_public_module_registry(): void
    {
        $legacyService = (string) file_get_contents(app_path('Services/Public/PublicContentService.php'));

        $this->assertStringNotContainsString('MODULE_MAP', $legacyService);
        $this->assertStringNotContainsString('MODULE_PRESENTATION', $legacyService);
        $this->assertFileExists(base_path('packages/public-catalog/src/Domain/PublicCatalogModules.php'));
    }

    public function test_global_public_catalog_uses_a_central_projection_read_model(): void
    {
        $service = (string) file_get_contents(base_path('packages/public-catalog/src/Application/PublicContentService.php'));
        $reader = base_path('packages/public-catalog/src/Application/PublicCatalogProjectionReader.php');
        $projector = base_path('packages/public-catalog/src/Application/PublicCatalogProjector.php');
        $migration = base_path('packages/public-catalog/database/migrations/central/2026_05_15_100000_create_public_catalog_items_table.php');
        $console = (string) file_get_contents(base_path('routes/console.php'));

        $this->assertFileExists($reader);
        $this->assertFileExists($projector);
        $this->assertFileExists($migration);
        $this->assertStringContainsString('PublicCatalogProjectionReader', $service);
        $this->assertStringContainsString('ticket:rebuild-public-catalog', $console);
    }

    public function test_openapi_contract_is_present_and_documents_api_paths(): void
    {
        $path = base_path('docs/api/openapi.json');

        $this->assertFileExists($path);

        $document = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('3.1.0', $document['openapi']);
        $this->assertArrayHasKey('/api/v1/health', $document['paths']);
        $this->assertArrayHasKey('/api/v1/platform/auth/login', $document['paths']);
        $this->assertArrayHasKey('/api/v1/public/content', $document['paths']);
        $this->assertArrayHasKey('/api/v1/tenants/{tenant}/auth/login', $document['paths']);
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function phpFiles(string $directory): iterable
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                yield $file;
            }
        }
    }
}
