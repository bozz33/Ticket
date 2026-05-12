<?php

use App\Support\Rbac\PermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection('central');
        $now = now();

        $permissions = [
            'platform.refunds.view',
            'platform.refunds.create',
            'platform.refunds.update',
            'platform.refunds.delete',
        ];

        $connection->table('permissions')->upsert(
            collect($permissions)->map(fn (string $permission): array => [
                'name' => $permission,
                'guard_name' => 'platform',
                'created_at' => $now,
                'updated_at' => $now,
            ])->all(),
            ['name', 'guard_name'],
            ['updated_at'],
        );

        foreach (PermissionCatalog::platformRoles() as $role => $rolePermissions) {
            $roleId = $connection->table('roles')
                ->where('name', $role)
                ->where('guard_name', 'platform')
                ->value('id');

            if (! $roleId) {
                continue;
            }

            $permissionIds = $connection->table('permissions')
                ->where('guard_name', 'platform')
                ->whereIn('name', $rolePermissions)
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

        $permissions = [
            'platform.refunds.view',
            'platform.refunds.create',
            'platform.refunds.update',
            'platform.refunds.delete',
        ];

        $permissionIds = $connection->table('permissions')
            ->where('guard_name', 'platform')
            ->whereIn('name', $permissions)
            ->pluck('id')
            ->all();

        if ($permissionIds === []) {
            return;
        }

        $connection->table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        $connection->table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        $connection->table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
