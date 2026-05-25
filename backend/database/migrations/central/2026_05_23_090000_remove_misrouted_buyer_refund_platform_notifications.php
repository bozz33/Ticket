<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('central')->hasTable('notifications')) {
            return;
        }

        DB::connection('central')
            ->table('notifications')
            ->where('type', 'App\\Notifications\\BuyerRefundRequestPlatformNotification')
            ->delete();
    }

    public function down(): void
    {
        // Misrouted platform notifications cannot be restored safely.
    }
};
