<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\V1\TenantContentLikeController;
use App\Models\ContentLike;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantContentLikeControllerTest extends TestCase
{
    private string $tenantDatabasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantDatabasePath = (string) tempnam(sys_get_temp_dir(), 'ticket-tenant-content-likes-');

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

    public function test_index_returns_batched_content_like_summaries(): void
    {
        $buyer = User::query()->create([
            'name' => 'Buyer One',
            'email' => 'buyer.one@example.test',
            'password' => 'password123',
            'is_active' => true,
        ]);
        $otherBuyer = User::query()->create([
            'name' => 'Buyer Two',
            'email' => 'buyer.two@example.test',
            'password' => 'password123',
            'is_active' => true,
        ]);

        ContentLike::query()->create(['module' => 'evenements', 'content_slug' => 'summit-demo', 'user_id' => $buyer->id]);
        ContentLike::query()->create(['module' => 'evenements', 'content_slug' => 'summit-demo', 'user_id' => $otherBuyer->id]);
        ContentLike::query()->create(['module' => 'crowdfunding', 'content_slug' => 'projet-demo', 'user_id' => $otherBuyer->id]);

        $request = Request::create('/content/likes', 'GET', [
            'items' => [
                'evenements:summit-demo',
                'evenements:summit-demo',
                'crowdfunding:projet-demo',
                'unknown:ignored',
            ],
        ]);
        $request->attributes->set('tenant_user', $buyer);

        $payload = app(TenantContentLikeController::class)
            ->index($request, 'tenant-demo')
            ->getData(true);

        $this->assertTrue($payload['data']['evenements:summit-demo']['liked']);
        $this->assertSame(2, $payload['data']['evenements:summit-demo']['likes']);
        $this->assertFalse($payload['data']['crowdfunding:projet-demo']['liked']);
        $this->assertSame(1, $payload['data']['crowdfunding:projet-demo']['likes']);
        $this->assertArrayNotHasKey('unknown:ignored', $payload['data']);
    }

    private function prepareTenantSchema(): void
    {
        Schema::connection('tenant')->dropAllTables();

        Schema::connection('tenant')->create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::connection('tenant')->create('content_likes', function (Blueprint $table): void {
            $table->id();
            $table->string('module', 80)->index();
            $table->string('content_slug')->index();
            $table->uuid('content_public_id')->nullable()->index();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['module', 'content_slug', 'user_id'], 'content_likes_unique_user_content');
            $table->index(['module', 'content_slug', 'created_at'], 'content_likes_content_week_index');
        });
    }
}
