<?php

namespace Tests\Feature;

use App\Support\Health\HealthProbe;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_endpoint_reports_ok_when_dependencies_are_reachable(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database.ok', true)
            ->assertJsonStructure(['name', 'environment', 'status', 'checks', 'timestamp']);
    }

    public function test_probe_reports_a_failing_check_for_an_unreachable_connection(): void
    {
        $probe = new HealthProbe;

        $result = $probe->checkConnection('connection-that-does-not-exist');

        $this->assertFalse($result['ok']);
        $this->assertArrayHasKey('error', $result);
        $this->assertFalse($probe->isHealthy(['database' => $result]));
    }

    public function test_probe_reports_healthy_when_all_checks_pass(): void
    {
        $probe = new HealthProbe;

        $this->assertTrue($probe->isHealthy(['database' => ['ok' => true]]));
        $this->assertFalse($probe->isHealthy(['database' => ['ok' => true], 'cache' => ['ok' => false]]));
    }
}
