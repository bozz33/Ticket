<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->table('events', function (Blueprint $table): void {
            $table->foreignId('category_id')->nullable()->after('organization_profile_id')->constrained('categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('events', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
