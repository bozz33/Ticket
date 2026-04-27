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

        $permissions = collect(PermissionCatalog::platformPermissions())
            ->map(fn (string $permission): array => [
                'name' => $permission,
                'guard_name' => 'platform',
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        $connection->table('permissions')->upsert($permissions, ['name', 'guard_name'], ['updated_at']);

        foreach (PermissionCatalog::platformRoles() as $role => $rolePermissions) {
            $connection->table('roles')->updateOrInsert(
                ['name' => $role, 'guard_name' => 'platform'],
                ['created_at' => $now, 'updated_at' => $now],
            );

            $roleId = $connection->table('roles')
                ->where('name', $role)
                ->where('guard_name', 'platform')
                ->value('id');

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
        DB::connection('central')->table('role_has_permissions')->delete();
        DB::connection('central')->table('model_has_roles')->delete();
        DB::connection('central')->table('model_has_permissions')->delete();
        DB::connection('central')->table('roles')->where('guard_name', 'platform')->delete();
        DB::connection('central')->table('permissions')->where('guard_name', 'platform')->delete();
    }
};
