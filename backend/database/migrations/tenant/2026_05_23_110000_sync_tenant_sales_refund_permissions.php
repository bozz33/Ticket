<?php

use App\Support\Rbac\PermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('tenant')->hasTable('permissions') || ! Schema::connection('tenant')->hasTable('roles')) {
            return;
        }

        $connection = DB::connection('tenant');
        $now = now();

        $permissions = collect(PermissionCatalog::tenantPermissions())
            ->map(fn (string $permission): array => [
                'name' => $permission,
                'guard_name' => 'tenant',
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        $connection->table('permissions')->upsert($permissions, ['name', 'guard_name'], ['updated_at']);

        foreach (PermissionCatalog::tenantRoles() as $role => $rolePermissions) {
            $roleId = $connection->table('roles')
                ->where('name', $role)
                ->where('guard_name', 'tenant')
                ->value('id');

            if ($roleId === null) {
                continue;
            }

            $permissionIds = $connection->table('permissions')
                ->where('guard_name', 'tenant')
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
        // Permissions can stay in place; removing them would break existing tenant roles.
    }
};
