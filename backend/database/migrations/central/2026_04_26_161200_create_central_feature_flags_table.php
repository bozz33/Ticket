<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('feature_flags', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('module')->nullable()->index();
            $table->boolean('default_enabled')->default(false)->index();
            $table->boolean('requires_subscription')->default(false)->index();
            $table->boolean('is_public')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('tenant_feature_flags', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('feature_flag_id')->constrained('feature_flags')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->string('source')->default('manual');
            $table->timestamp('expires_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'feature_flag_id']);
        });

        $now = now();
        DB::connection('central')->table('feature_flags')->insert([
            [
                'code' => 'tenant.access',
                'name' => 'Accès backoffice tenant',
                'description' => 'Autorise l’accès général au panneau tenant.',
                'module' => 'core',
                'default_enabled' => true,
                'requires_subscription' => true,
                'is_public' => false,
                'is_active' => true,
                'meta' => json_encode(['group' => 'subscription']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'tenant.users',
                'name' => 'Gestion des utilisateurs tenant',
                'description' => 'Autorise la gestion des comptes et rôles tenant.',
                'module' => 'core',
                'default_enabled' => true,
                'requires_subscription' => true,
                'is_public' => false,
                'is_active' => true,
                'meta' => json_encode(['group' => 'rbac']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'tenant.finance',
                'name' => 'Finance tenant',
                'description' => 'Expose la lecture des données financières tenant.',
                'module' => 'finance',
                'default_enabled' => true,
                'requires_subscription' => true,
                'is_public' => false,
                'is_active' => true,
                'meta' => json_encode(['group' => 'finance']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'tenant.support',
                'name' => 'Support tenant',
                'description' => 'Expose les outils de support côté tenant.',
                'module' => 'support',
                'default_enabled' => true,
                'requires_subscription' => true,
                'is_public' => false,
                'is_active' => true,
                'meta' => json_encode(['group' => 'support']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'public.catalog',
                'name' => 'Catalogue public',
                'description' => 'Active le catalogue public global.',
                'module' => 'public',
                'default_enabled' => true,
                'requires_subscription' => false,
                'is_public' => true,
                'is_active' => true,
                'meta' => json_encode(['group' => 'public']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenant_feature_flags');
        Schema::connection('central')->dropIfExists('feature_flags');
    }
};
