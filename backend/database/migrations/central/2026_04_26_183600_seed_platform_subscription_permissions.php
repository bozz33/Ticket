<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection('central');
        $now = now();

        $permissions = collect([
            'platform.subscriptions.view',
            'platform.subscriptions.create',
            'platform.subscriptions.update',
            'platform.subscriptions.delete',
        ])->map(fn (string $permission): array => [
            'name' => $permission,
            'guard_name' => 'platform',
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        $connection->table('permissions')->upsert($permissions, ['name', 'guard_name'], ['updated_at']);

        $roleId = $connection->table('roles')
            ->where('name', 'finance-manager')
            ->where('guard_name', 'platform')
            ->value('id');

        if ($roleId !== null) {
            $permissionIds = $connection->table('permissions')
                ->where('guard_name', 'platform')
                ->whereIn('name', [
                    'platform.subscriptions.view',
                    'platform.subscriptions.create',
                    'platform.subscriptions.update',
                    'platform.subscriptions.delete',
                ])
                ->pluck('id')
                ->all();

            foreach ($permissionIds as $permissionId) {
                $connection->table('role_has_permissions')->updateOrInsert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $connection = DB::connection('central');

        $permissionIds = $connection->table('permissions')
            ->where('guard_name', 'platform')
            ->whereIn('name', [
                'platform.subscriptions.view',
                'platform.subscriptions.create',
                'platform.subscriptions.update',
                'platform.subscriptions.delete',
            ])
            ->pluck('id')
            ->all();

        if ($permissionIds !== []) {
            $connection->table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            $connection->table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            $connection->table('permissions')->whereIn('id', $permissionIds)->delete();
        }
    }
};
