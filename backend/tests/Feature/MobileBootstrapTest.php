<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileBootstrapTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareCentralSchema();
    }

    public function test_bootstrap_returns_versions_capabilities_and_default_tenant(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->create([
            'public_id' => (string) fake()->uuid(),
            'name' => 'Bootstrap Tenant',
            'slug' => 'bootstrap-tenant',
            'status' => 'active',
            'database_name' => 'ticket_bootstrap_tenant',
        ]));

        $response = $this->getJson('/api/v1/mobile/bootstrap');

        $response->assertOk()
            ->assertJsonPath('data.api_version', 'v1')
            ->assertJsonPath('data.mobile_api_version', 'mvp-1')
            ->assertJsonPath('data.default_tenant.slug', $tenant->slug)
            ->assertJsonPath('data.capabilities.push_devices', true)
            ->assertJsonStructure([
                'data' => [
                    'api_version',
                    'mobile_api_version',
                    'default_tenant' => ['id', 'public_id', 'name', 'slug'],
                    'capabilities',
                    'enums' => ['order_statuses', 'access_pass_statuses'],
                ],
            ]);
    }

    public function test_bootstrap_is_public_and_works_without_a_tenant(): void
    {
        $response = $this->getJson('/api/v1/mobile/bootstrap');

        $response->assertOk()
            ->assertJsonPath('data.api_version', 'v1')
            ->assertJsonPath('data.default_tenant', null);
    }

    private function prepareCentralSchema(): void
    {
        Schema::connection('central')->dropAllTables();

        Schema::connection('central')->create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('active');
            $table->string('database_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
