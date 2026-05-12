<?php

namespace Tests\Feature;

use App\Models\GatewayWebhookLog;
use App\Models\IncidentLog;
use App\Models\PaymentGateway;
use App\Models\PaymentIncident;
use App\Models\PlatformTransaction;
use App\Models\Tenant;
use App\Services\Payments\PaymentWebhookService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentWebhookServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareCentralSchema();
    }

    public function test_receive_links_webhook_log_to_existing_transaction_and_tenant(): void
    {
        config()->set('services.paystack.webhook_secret', 'env-webhook-secret');

        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->create([
            'public_id' => (string) fake()->uuid(),
            'name' => 'Webhook Tenant',
            'slug' => 'webhook-tenant',
            'status' => 'active',
            'database_name' => 'ticket_webhook_tenant',
        ]));

        $gateway = PaymentGateway::query()->create([
            'public_id' => (string) fake()->uuid(),
            'code' => 'paystack',
            'name' => 'Paystack',
            'provider' => 'Paystack',
            'mode' => 'live',
            'is_active' => true,
        ]);

        $transaction = PlatformTransaction::query()->create([
            'tenant_id' => $tenant->id,
            'payment_gateway_id' => $gateway->id,
            'transaction_reference' => 'PAY-REF-001',
            'type' => 'gateway_charge',
            'direction' => 'credit',
            'status' => 'pending',
            'gross_amount' => 1000,
            'fee_amount' => 100,
            'net_amount' => 900,
            'currency_code' => 'XOF',
            'occurred_at' => now(),
        ]);

        $payload = [
            'event' => 'charge.success',
            'data' => [
                'reference' => 'PAY-REF-001',
                'id' => 'gw_123',
                'status' => 'success',
                'amount' => 1000,
                'fees' => 100,
                'currency' => 'XOF',
            ],
        ];

        $request = $this->makePaystackRequest($payload, 'env-webhook-secret');

        $log = app(PaymentWebhookService::class)->receive($gateway, $request);

        $this->assertInstanceOf(GatewayWebhookLog::class, $log);
        $this->assertSame('processed', $log->status);
        $this->assertSame($tenant->id, $log->tenant_id);
        $this->assertSame($transaction->id, $log->platform_transaction_id);
        $this->assertDatabaseCount('payment_incidents', 0, 'central');
        $this->assertDatabaseCount('incident_logs', 0, 'central');
    }

    public function test_receive_creates_incident_records_when_signature_is_invalid(): void
    {
        config()->set('services.paystack.webhook_secret', 'env-webhook-secret');

        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->create([
            'public_id' => (string) fake()->uuid(),
            'name' => 'Webhook Tenant',
            'slug' => 'webhook-tenant-2',
            'status' => 'active',
            'database_name' => 'ticket_webhook_tenant_2',
        ]));

        $gateway = PaymentGateway::query()->create([
            'public_id' => (string) fake()->uuid(),
            'code' => 'paystack',
            'name' => 'Paystack',
            'provider' => 'Paystack',
            'mode' => 'live',
            'is_active' => true,
        ]);

        $transaction = PlatformTransaction::query()->create([
            'tenant_id' => $tenant->id,
            'payment_gateway_id' => $gateway->id,
            'transaction_reference' => 'PAY-REF-002',
            'type' => 'gateway_charge',
            'direction' => 'credit',
            'status' => 'pending',
            'gross_amount' => 1000,
            'fee_amount' => 100,
            'net_amount' => 900,
            'currency_code' => 'XOF',
            'occurred_at' => now(),
        ]);

        $payload = [
            'event' => 'charge.success',
            'data' => [
                'reference' => 'PAY-REF-002',
                'id' => 'gw_456',
                'status' => 'success',
                'amount' => 1000,
                'fees' => 100,
                'currency' => 'XOF',
            ],
        ];

        $request = $this->makePaystackRequest($payload, 'wrong-secret');

        $log = app(PaymentWebhookService::class)->receive($gateway, $request);

        $incident = PaymentIncident::query()->first();
        $incidentLog = IncidentLog::query()->first();

        $this->assertSame('failed', $log->status);
        $this->assertSame($tenant->id, $log->tenant_id);
        $this->assertSame($transaction->id, $log->platform_transaction_id);
        $this->assertNotNull($incident);
        $this->assertSame($tenant->id, $incident->tenant_id);
        $this->assertSame($transaction->id, $incident->platform_transaction_id);
        $this->assertSame('payment_webhook_failed', $incident->incident_code);
        $this->assertNotNull($incidentLog);
        $this->assertSame($incident->id, $incidentLog->payment_incident_id);
        $this->assertSame($tenant->id, $incidentLog->tenant_id);
    }

    private function makePaystackRequest(array $payload, string $secret): Request
    {
        $content = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha512', $content, $secret);

        return Request::create(
            '/api/v1/payments/webhooks/paystack',
            'POST',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_PAYSTACK_SIGNATURE' => $signature,
            ],
            $content,
        );
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

        Schema::connection('central')->create('payment_gateways', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('provider')->nullable();
            $table->string('mode')->nullable();
            $table->string('public_key')->nullable();
            $table->text('secret_key')->nullable();
            $table->text('webhook_secret')->nullable();
            $table->json('supported_currencies')->nullable();
            $table->json('supported_countries')->nullable();
            $table->json('supported_channels')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('platform_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('plan_id')->nullable();
            $table->foreignId('payment_gateway_id')->nullable();
            $table->string('transaction_reference')->unique();
            $table->string('gateway_reference')->nullable();
            $table->string('type');
            $table->string('direction')->default('credit');
            $table->string('status')->default('pending');
            $table->bigInteger('gross_amount')->default(0);
            $table->bigInteger('fee_amount')->default(0);
            $table->bigInteger('net_amount')->default(0);
            $table->bigInteger('gateway_fee_amount')->default(0);
            $table->bigInteger('platform_fee_amount')->default(0);
            $table->bigInteger('tax_amount')->default(0);
            $table->bigInteger('payout_fee_amount')->default(0);
            $table->bigInteger('customer_fee_amount')->default(0);
            $table->bigInteger('absorbed_fee_amount')->default(0);
            $table->string('currency_code', 3)->default('XOF');
            $table->timestamp('occurred_at')->nullable();
            $table->json('meta')->nullable();
            $table->json('pricing_snapshot')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('payment_incidents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('platform_transaction_id')->nullable();
            $table->foreignId('payment_gateway_id')->nullable();
            $table->string('severity')->default('medium');
            $table->string('status')->default('open');
            $table->string('incident_code')->nullable();
            $table->text('summary');
            $table->timestamp('detected_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('platform_support_tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('platform_user_id')->nullable();
            $table->string('reference')->unique();
            $table->string('subject');
            $table->string('requester_name')->nullable();
            $table->string('requester_email')->nullable();
            $table->string('status')->default('open');
            $table->string('priority')->default('normal');
            $table->string('category')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('incident_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('payment_incident_id')->nullable();
            $table->foreignId('platform_support_ticket_id')->nullable();
            $table->string('title');
            $table->string('severity')->default('medium');
            $table->string('status')->default('open');
            $table->string('incident_type')->nullable();
            $table->text('summary')->nullable();
            $table->timestamp('detected_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('gateway_webhook_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_gateway_id');
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('platform_transaction_id')->nullable();
            $table->string('event_name');
            $table->string('external_id')->nullable();
            $table->text('signature')->nullable();
            $table->json('headers')->nullable();
            $table->json('payload')->nullable();
            $table->string('status')->default('received');
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->unsignedInteger('attempt_count')->default(1);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('platform_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('platform_user_id')->nullable();
            $table->foreignId('tenant_id')->nullable();
            $table->string('event');
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();
            $table->string('subject_label')->nullable();
            $table->json('changes')->nullable();
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('logged_at')->nullable();
        });
    }
}
