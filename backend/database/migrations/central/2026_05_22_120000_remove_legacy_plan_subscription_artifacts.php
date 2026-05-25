<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('central');

        if ($schema->hasTable('commercial_policies') && $schema->hasColumn('commercial_policies', 'plan_id')) {
            $schema->table('commercial_policies', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('plan_id');
            });
        }

        if ($schema->hasTable('platform_transactions') && $schema->hasColumn('platform_transactions', 'plan_id')) {
            $schema->table('platform_transactions', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('plan_id');
            });
        }

        if ($schema->hasTable('feature_flags') && $schema->hasColumn('feature_flags', 'requires_subscription')) {
            DB::connection('central')->table('feature_flags')->update(['requires_subscription' => false]);

            $schema->table('feature_flags', function (Blueprint $table): void {
                $table->dropColumn('requires_subscription');
            });
        }

        if ($schema->hasTable('tenant_subscriptions')) {
            $schema->drop('tenant_subscriptions');
        }

        if ($schema->hasTable('plans')) {
            $schema->drop('plans');
        }

        if ($schema->hasTable('permissions')) {
            $permissionIds = DB::connection('central')
                ->table('permissions')
                ->where('guard_name', 'platform')
                ->where(function ($query): void {
                    $query->where('name', 'like', 'platform.plans.%')
                        ->orWhere('name', 'like', 'platform.subscriptions.%');
                })
                ->pluck('id')
                ->all();

            if ($permissionIds !== []) {
                DB::connection('central')->table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
                DB::connection('central')->table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
                DB::connection('central')->table('permissions')->whereIn('id', $permissionIds)->delete();
            }
        }
    }

    public function down(): void
    {
        // Legacy plans and tenant subscriptions were intentionally removed from the product model.
    }
};
