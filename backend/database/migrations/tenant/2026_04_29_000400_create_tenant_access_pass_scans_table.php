<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('access_pass_scans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('access_pass_id')->constrained('access_passes')->cascadeOnDelete();
            $table->unsignedBigInteger('scanned_by')->nullable()->index();
            $table->string('action')->default('consume')->index();
            $table->string('result')->default('granted')->index();
            $table->string('terminal_id')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('scanned_at')->useCurrent()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('access_pass_scans');
    }
};
