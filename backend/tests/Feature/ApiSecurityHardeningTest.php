<?php

namespace Tests\Feature;

use App\Http\Middleware\InitializeTenancyByRouteParameter;
use App\Models\PlatformUser;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\RouteTenantResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Tenancy;
use Tests\TestCase;
use Ticket\IdentityAccess\Contracts\PlatformTokenIssuer;
use Ticket\IdentityAccess\Contracts\TenantTokenIssuer;

class ApiSecurityHardeningTest extends TestCase
{
    use WithFaker;

    private string $tenantDatabasePath;

    protected function setUp(): void
    {
        parent::setUp();

        // Runs against the dedicated PostgreSQL testing database (phpunit.xml). The
        // tenant database name points at the same testing database so tenancy
        // initialization resolves the schema built in prepareTenantSchema().
        $this->tenantDatabasePath = (string) config('database.connections.tenant.database');

        DB::purge('tenant');
        DB::purge('tenant_template');

        Route::post('/_test/throttle/tenant-auth/{tenant}', static fn () => response()->json(['ok' => true]))
            ->middleware('throttle:tenant-auth');

        Route::post('/_test/throttle/public-payment/{tenant}', static fn () => response()->json(['ok' => true]))
            ->middleware('throttle:public-payment-initialize');

        $this->prepareCentralSchema();
        $this->prepareTenantSchema();
        RateLimiter::clear('platform-auth:guest:127.0.0.1');
    }

    protected function tearDown(): void
    {
        DB::disconnect('tenant');
        DB::disconnect('tenant_template');

        parent::tearDown();
    }

    public function test_public_onboarding_is_rate_limited(): void
    {
        config()->set('ticket.rate_limits.public_onboarding_per_minute', 1);

        $payload = [
            'org_name' => '',
            'email' => 'invalid',
            'password' => 'short',
        ];

        $this->postJson('/api/v1/public/onboarding/register', $payload)
            ->assertStatus(422);

        $this->postJson('/api/v1/public/onboarding/register', $payload)
            ->assertStatus(429)
            ->assertJson([
                'message' => 'Trop de tentatives. Réessayez plus tard.',
            ]);
    }

    public function test_api_validation_errors_use_standard_contract(): void
    {
        $this->postJson('/api/v1/public/onboarding/register', [
            'org_name' => '',
            'email' => 'invalid',
            'password' => 'short',
        ])
            ->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'error' => ['code'],
                'errors',
            ])
            ->assertJson([
                'error' => [
                    'code' => 'validation_failed',
                ],
            ]);
    }

    public function test_api_not_found_errors_use_standard_contract(): void
    {
        $this->getJson('/api/v1/unknown-endpoint')
            ->assertStatus(404)
            ->assertJson([
                'message' => 'Ressource introuvable.',
                'error' => [
                    'code' => 'not_found',
                ],
            ]);
    }

    public function test_platform_login_is_rate_limited(): void
    {
        config()->set('ticket.rate_limits.platform_auth_per_minute', 1);

        $payload = [
            'email' => '',
            'password' => '',
        ];

        $this->postJson('/api/v1/platform/auth/login', $payload)
            ->assertStatus(422);

        $this->postJson('/api/v1/platform/auth/login', $payload)
            ->assertStatus(429);
    }

    public function test_tenant_login_is_rate_limited(): void
    {
        config()->set('ticket.rate_limits.tenant_auth_per_minute', 1);

        $payload = [
            'email' => 'buyer@example.test',
            'password' => 'invalid-password',
        ];

        $this->postJson('/_test/throttle/tenant-auth/tenant-test', $payload)
            ->assertOk();

        $this->postJson('/_test/throttle/tenant-auth/tenant-test', $payload)
            ->assertStatus(429);
    }

    public function test_tenant_middleware_can_restore_context_from_livewire_referer(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->create([
            'public_id' => (string) str()->uuid(),
            'name' => 'Tenant Test',
            'slug' => 'tenant-test',
            'status' => 'active',
            'locale' => 'fr',
            'timezone' => 'UTC',
            'database_name' => $this->tenantDatabasePath,
            'database_host' => '127.0.0.1',
            'database_port' => 5432,
        ]));

        $request = Request::create('/livewire/update', 'POST');
        $request->headers->set('referer', 'http://127.0.0.1:8000/tenants/tenant-test/admin/login');

        $router = app('router');
        $router->post('/livewire/update', static fn () => response()->json(['ok' => true]));
        $route = $router->getRoutes()->match($request);
        $request->setRouteResolver(static fn () => $route);

        $tenancy = \Mockery::mock(Tenancy::class);
        $tenancy->shouldReceive('initialize')
            ->once()
            ->withArgs(static fn (Tenant $resolvedTenant): bool => $resolvedTenant->slug === 'tenant-test');

        $middleware = new InitializeTenancyByRouteParameter(
            $tenancy,
            app(RouteTenantResolver::class),
        );

        $response = $middleware->handle(
            $request,
            static fn (Request $passedRequest) => response()->json([
                'tenant' => $passedRequest->route('tenant'),
                'path' => $passedRequest->path(),
            ]),
        );

        $this->assertSame('tenant-test', $response->getData(true)['tenant'] ?? null);
    }

    public function test_public_payment_initialize_is_rate_limited(): void
    {
        config()->set('ticket.rate_limits.public_payment_initialize_per_minute', 1);

        $payload = [
            'offer' => 'offer-public',
            'buyer_email' => 'buyer@example.test',
        ];

        $this->postJson('/_test/throttle/public-payment/payment-tenant', $payload)
            ->assertOk();

        $this->postJson('/_test/throttle/public-payment/payment-tenant', $payload)
            ->assertStatus(429);
    }

    public function test_tenant_tokens_receive_default_expiration(): void
    {
        config()->set('ticket.token_expirations.tenant_api_minutes', 15);

        $user = User::query()->create([
            'name' => 'Buyer',
            'username' => 'buyer_test',
            'email' => 'buyer@example.test',
            'password' => 'password123',
            'is_active' => true,
        ]);

        $plainToken = app(TenantTokenIssuer::class)->createToken($user, 'panel');

        $this->assertNotEmpty($plainToken);

        $token = $user->apiTokens()->first();

        $this->assertNotNull($token);
        $this->assertNotNull($token->expires_at);
        $this->assertTrue($token->expires_at->between(now()->addMinutes(14), now()->addMinutes(16)));
    }

    public function test_platform_tokens_receive_default_expiration(): void
    {
        config()->set('ticket.token_expirations.platform_api_minutes', 45);

        $user = PlatformUser::query()->create([
            'name' => 'Platform Admin',
            'email' => 'admin@example.test',
            'password' => 'password123',
            'is_super_admin' => true,
        ]);

        $plainToken = app(PlatformTokenIssuer::class)->createToken($user, 'platform');

        $this->assertNotEmpty($plainToken);

        $token = $user->apiTokens()->first();

        $this->assertNotNull($token);
        $this->assertNotNull($token->expires_at);
        $this->assertTrue($token->expires_at->between(now()->addMinutes(44), now()->addMinutes(46)));
    }

    private function prepareCentralSchema(): void
    {
        Schema::connection('central')->dropAllTables();

        Schema::connection('central')->create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('draft');
            $table->string('country_code', 2)->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->string('locale', 10)->default('fr');
            $table->string('timezone')->default('UTC');
            $table->string('database_name')->unique();
            $table->string('database_host')->nullable();
            $table->unsignedInteger('database_port')->nullable();
            $table->string('database_username')->nullable();
            $table->text('database_password')->nullable();
            $table->json('database_options')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('central')->create('platform_users', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_super_admin')->default(false);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::connection('central')->create('platform_api_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('platform_user_id')->constrained('platform_users')->cascadeOnDelete();
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->json('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('platform_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('platform_user_id')->nullable();
            $table->foreignId('tenant_id')->nullable();
            $table->string('event');
            $table->string('subject_type');
            $table->string('subject_id')->nullable();
            $table->string('subject_label')->nullable();
            $table->json('changes')->nullable();
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('logged_at')->nullable();
            $table->timestamps();
        });
    }

    private function prepareTenantSchema(): void
    {
        Schema::connection('tenant')->dropAllTables();

        Schema::connection('tenant')->create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('username')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('locale', 10)->default('fr');
            $table->string('timezone')->default('UTC');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('tenant')->create('user_api_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->json('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }
}
