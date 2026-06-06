<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->table('user_api_tokens', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->after('token');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('user_api_tokens', function (Blueprint $table): void {
            $table->dropColumn('is_active');
        });
    }
};
