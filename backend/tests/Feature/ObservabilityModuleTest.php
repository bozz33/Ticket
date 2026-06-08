<?php

namespace Tests\Feature;

use App\Models\ErrorLog;
use App\Support\Observability\ErrorLogWriter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ObservabilityModuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Runs against the dedicated PostgreSQL testing database (phpunit.xml).
        DB::purge('central');
        $this->createErrorLogsTable();
    }

    protected function tearDown(): void
    {
        Schema::connection('central')->dropIfExists('error_logs');
        DB::disconnect('central');

        parent::tearDown();
    }

    public function test_frontend_client_errors_are_persisted(): void
    {
        $this->postJson('/api/v1/observability/client-events', [
            'type' => 'client-error',
            'name' => 'TypeError',
            'message' => 'Cannot read properties of undefined',
            'path' => '/evenements/demo',
            'stack' => 'at Component (app.js:10)',
        ])->assertStatus(202)->assertJson(['accepted' => true]);

        $log = ErrorLog::query()->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame('frontend', $log->source);
        $this->assertSame('error', $log->level);
        $this->assertSame('client-error', $log->type);
        $this->assertSame('/evenements/demo', $log->url);
    }

    public function test_web_vitals_are_recorded_as_info(): void
    {
        $this->postJson('/api/v1/observability/client-events', [
            'type' => 'web-vital',
            'name' => 'LCP',
            'value' => 1234,
            'rating' => 'good',
            'path' => '/',
        ])->assertStatus(202);

        $this->assertSame('info', ErrorLog::query()->latest('id')->first()->level);
    }

    public function test_backend_exceptions_are_captured(): void
    {
        ErrorLogWriter::fromException(new \RuntimeException('kaboom'));

        $log = ErrorLog::query()->where('message', 'kaboom')->first();

        $this->assertNotNull($log);
        $this->assertSame('backend', $log->source);
        $this->assertSame(\RuntimeException::class, $log->exception_class);
    }

    public function test_writer_never_throws_when_table_is_missing(): void
    {
        Schema::connection('central')->dropIfExists('error_logs');

        // Must be a no-op rather than raising — observability cannot break requests.
        ErrorLogWriter::record(['message' => 'ignored']);

        $this->assertFalse(Schema::connection('central')->hasTable('error_logs'));
    }

    private function createErrorLogsTable(): void
    {
        Schema::connection('central')->dropIfExists('error_logs');
        Schema::connection('central')->create('error_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('request_id')->nullable();
            $table->string('level', 20)->default('error');
            $table->string('source', 20)->default('backend');
            $table->string('type')->nullable();
            $table->text('message')->nullable();
            $table->string('exception_class')->nullable();
            $table->string('file')->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('method', 10)->nullable();
            $table->text('url')->nullable();
            $table->string('route')->nullable();
            $table->string('ip', 64)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('context')->nullable();
            $table->text('trace')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
        });
    }
}
