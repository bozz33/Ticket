<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::connection('tenant')->hasTable('content_likes')
            || ! Schema::connection('tenant')->hasTable('event_likes')
            || ! Schema::connection('tenant')->hasTable('events')
        ) {
            return;
        }

        $rows = DB::connection('tenant')
            ->table('event_likes')
            ->join('events', 'events.id', '=', 'event_likes.event_id')
            ->whereNotNull('events.slug')
            ->select([
                DB::raw("'evenements' as module"),
                'events.slug as content_slug',
                'events.public_id as content_public_id',
                'event_likes.user_id',
                'event_likes.created_at',
                'event_likes.updated_at',
            ])
            ->orderBy('event_likes.id')
            ->get()
            ->map(fn (object $row): array => [
                'module' => (string) $row->module,
                'content_slug' => (string) $row->content_slug,
                'content_public_id' => $row->content_public_id,
                'user_id' => (int) $row->user_id,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ])
            ->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::connection('tenant')->table('content_likes')->insertOrIgnore($chunk);
        }
    }

    public function down(): void
    {
        //
    }
};
