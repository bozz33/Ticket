<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('error_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('request_id')->nullable()->index();
            $table->string('level', 20)->default('error')->index();
            $table->string('source', 20)->default('backend')->index();
            $table->string('type')->nullable()->index();
            $table->text('message')->nullable();
            $table->string('exception_class')->nullable();
            $table->string('file')->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable()->index();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('method', 10)->nullable();
            $table->text('url')->nullable();
            $table->string('route')->nullable();
            $table->string('ip', 64)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('context')->nullable();
            $table->text('trace')->nullable();
            $table->timestamp('occurred_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('error_logs');
    }
};
