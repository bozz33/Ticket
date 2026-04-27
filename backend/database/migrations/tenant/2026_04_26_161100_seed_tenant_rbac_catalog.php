<?php

use App\Support\Rbac\PermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
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
            $connection->table('roles')->updateOrInsert(
                ['name' => $role, 'guard_name' => 'tenant'],
                ['created_at' => $now, 'updated_at' => $now],
            );

            $roleId = $connection->table('roles')
                ->where('name', $role)
                ->where('guard_name', 'tenant')
                ->value('id');

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
        DB::connection('tenant')->table('role_has_permissions')->delete();
        DB::connection('tenant')->table('model_has_roles')->delete();
        DB::connection('tenant')->table('model_has_permissions')->delete();
        DB::connection('tenant')->table('roles')->where('guard_name', 'tenant')->delete();
        DB::connection('tenant')->table('permissions')->where('guard_name', 'tenant')->delete();
    }
};
