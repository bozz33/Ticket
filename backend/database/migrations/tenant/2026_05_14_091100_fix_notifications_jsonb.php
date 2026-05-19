<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('tenant')->hasTable('notifications')) {
            Schema::connection('tenant')->create('notifications', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->jsonb('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });

            return;
        }

        $column = DB::connection('tenant')->selectOne(
            "select data_type from information_schema.columns where table_name = 'notifications' and column_name = 'data'"
        );

        if (in_array($column?->data_type, ['json', 'jsonb'], true)) {
            return;
        }

        if (DB::connection('tenant')->getDriverName() === 'pgsql') {
            DB::connection('tenant')->statement(<<<'SQL'
                ALTER TABLE notifications
                ALTER COLUMN data TYPE jsonb
                USING CASE
                    WHEN data IS NULL OR btrim(data::text) = '' THEN '{}'::jsonb
                    ELSE data::jsonb
                END
            SQL);
        }
    }

    public function down(): void
    {
        // no-op: we intentionally keep notifications on jsonb for Filament + PostgreSQL
    }
};
