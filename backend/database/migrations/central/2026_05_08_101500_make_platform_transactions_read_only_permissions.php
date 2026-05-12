<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection('central');

        $permissions = [
            'platform.transactions.create',
            'platform.transactions.update',
            'platform.transactions.delete',
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

    public function down(): void
    {
        $connection = DB::connection('central');
        $now = now();

        $permissions = [
            'platform.transactions.create',
            'platform.transactions.update',
            'platform.transactions.delete',
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
    }
};
