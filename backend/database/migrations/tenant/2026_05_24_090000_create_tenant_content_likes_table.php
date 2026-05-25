<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::connection('tenant')->hasTable('content_likes')) {
            return;
        }

        Schema::connection('tenant')->create('content_likes', function (Blueprint $table): void {
            $table->id();
            $table->string('module', 80)->index();
            $table->string('content_slug')->index();
            $table->uuid('content_public_id')->nullable()->index();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['module', 'content_slug', 'user_id'], 'content_likes_unique_user_content');
            $table->index(['module', 'content_slug', 'created_at'], 'content_likes_content_week_index');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('content_likes');
    }
};
