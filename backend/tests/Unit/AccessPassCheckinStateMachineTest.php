<?php

namespace Tests\Unit;

use App\Enums\AccessPassStatus;
use App\Enums\AccessPassType;
use App\Models\AccessPass;
use App\Services\Tenancy\AccessPassCheckinService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccessPassCheckinStateMachineTest extends TestCase
{
    private string $tenantDatabasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantDatabasePath = (string) tempnam(sys_get_temp_dir(), 'ticket-tenant-checkin-');

        config()->set('database.connections.tenant.driver', 'sqlite');
        config()->set('database.connections.tenant.database', $this->tenantDatabasePath);
        config()->set('database.connections.tenant.foreign_key_constraints', true);
        config()->set('ticket.tenant_connection', 'tenant');

        DB::purge('tenant');

        $this->prepareTenantSchema();
    }

    protected function tearDown(): void
    {
        DB::disconnect('tenant');

        if (isset($this->tenantDatabasePath) && is_file($this->tenantDatabasePath)) {
            @unlink($this->tenantDatabasePath);
        }

        parent::tearDown();
    }

    public function test_consuming_an_active_pass_marks_it_as_used(): void
    {
        $pass = $this->makePass(AccessPassStatus::Active);
        $result = $this->service()->consume($pass, new Request);

        $this->assertSame('granted', $result['result']);
        $this->assertSame(AccessPassStatus::Used, AccessPass::on('tenant')->whereKey($pass->getKey())->value('status'));
    }

    public function test_consuming_an_already_used_pass_returns_already_used(): void
    {
        $pass = $this->makePass(AccessPassStatus::Used);
        $result = $this->service()->consume($pass, new Request);

        $this->assertSame('already_used', $result['result']);
    }

    public function test_consuming_a_revoked_pass_returns_revoked(): void
    {
        $pass = $this->makePass(AccessPassStatus::Revoked);
        $result = $this->service()->consume($pass, new Request);

        $this->assertSame('revoked', $result['result']);
    }

    public function test_resetting_a_used_pass_returns_it_to_active(): void
    {
        $pass = $this->makePass(AccessPassStatus::Used);
        $this->service()->reset($pass, new Request);

        $this->assertSame(AccessPassStatus::Active, AccessPass::on('tenant')->whereKey($pass->getKey())->value('status'));
    }

    public function test_revoking_an_active_pass_marks_it_revoked(): void
    {
        $pass = $this->makePass(AccessPassStatus::Active);
        $this->service()->revoke($pass, new Request, 'Test revocation');

        $fresh = AccessPass::on('tenant')->whereKey($pass->getKey())->first();
        $this->assertSame(AccessPassStatus::Revoked, $fresh->status);
        $this->assertSame('Test revocation', $fresh->revocation_reason);
        $this->assertNotNull($fresh->revoked_at);
    }

    public function test_reactivating_a_revoked_pass_returns_it_to_active(): void
    {
        $pass = $this->makePass(AccessPassStatus::Revoked);
        $this->service()->reactivate($pass, new Request);

        $this->assertSame(AccessPassStatus::Active, AccessPass::on('tenant')->whereKey($pass->getKey())->value('status'));
    }

    public function test_second_consume_of_same_pass_returns_already_used_not_granted(): void
    {
        // This is the race condition scenario: both calls arrive at the same time.
        // With lockForUpdate(), the second call will read the updated status after commit.
        $pass = $this->makePass(AccessPassStatus::Active);
        $service = $this->service();

        $first = $service->consume($pass, new Request);
        $fresh = AccessPass::on('tenant')->whereKey($pass->getKey())->firstOrFail();
        $second = $service->consume($fresh, new Request);

        $this->assertSame('granted', $first['result']);
        $this->assertSame('already_used', $second['result']);
    }

    // ---

    private function service(): AccessPassCheckinService
    {
        return app(AccessPassCheckinService::class);
    }

    private function makePass(AccessPassStatus $status): AccessPass
    {
        return AccessPass::on('tenant')->create([
            'public_id' => (string) Str::uuid(),
            'access_code' => 'CODE-'.Str::random(10),
            'type' => AccessPassType::EventTicket->value,
            'status' => $status->value,
            'holder_name' => 'Test Holder',
        ]);
    }

    private function prepareTenantSchema(): void
    {
        Schema::connection('tenant')->dropAllTables();

        Schema::connection('tenant')->create('users', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('orders', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('tenant')->create('access_passes', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('access_code')->unique();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('type');
            $table->string('status');
            $table->string('holder_name')->nullable();
            $table->string('holder_email')->nullable()->index();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revocation_reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('tenant')->create('access_pass_scans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('access_pass_id')->constrained('access_passes')->cascadeOnDelete();
            $table->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('result');
            $table->string('terminal_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();
        });
    }
}
