<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('payment_gateways', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('provider')->index();
            $table->string('mode')->default('live')->index();
            $table->string('public_key')->nullable();
            $table->text('secret_key')->nullable();
            $table->text('webhook_secret')->nullable();
            $table->json('supported_currencies')->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('compliance_policies', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('policy_type')->index();
            $table->string('status')->default('draft')->index();
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_to')->nullable();
            $table->json('requirements')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::connection('central')->table('payment_gateways')->insert([
            'public_id' => (string) \Illuminate\Support\Str::uuid(),
            'code' => 'paystack',
            'name' => 'Paystack',
            'provider' => 'paystack',
            'mode' => 'live',
            'supported_currencies' => json_encode(['NGN', 'GHS', 'ZAR', 'USD', 'XOF']),
            'is_active' => false,
            'meta' => json_encode(['webhook_strategy' => 'signature']),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::connection('central')->table('compliance_policies')->insert([
            [
                'public_id' => (string) \Illuminate\Support\Str::uuid(),
                'code' => 'privacy.default',
                'name' => 'Politique de confidentialité',
                'description' => 'Cadre par défaut pour la protection des données.',
                'policy_type' => 'privacy',
                'status' => 'active',
                'effective_from' => $now,
                'requirements' => json_encode(['consent_required' => true, 'retention_days' => 365]),
                'meta' => json_encode(['scope' => 'global']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'public_id' => (string) \Illuminate\Support\Str::uuid(),
                'code' => 'kyc.default',
                'name' => 'Politique KYC tenant',
                'description' => 'Vérifications minimales pour les tenants sensibles.',
                'policy_type' => 'kyc',
                'status' => 'active',
                'effective_from' => $now,
                'requirements' => json_encode(['documents' => ['identity', 'registration']]),
                'meta' => json_encode(['scope' => 'tenant']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('compliance_policies');
        Schema::connection('central')->dropIfExists('payment_gateways');
    }
};
