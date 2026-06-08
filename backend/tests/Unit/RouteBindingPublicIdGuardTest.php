<?php

namespace Tests\Unit;

use App\Models\PaymentGateway;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regression guard for the route binding UUID crash.
 *
 * resolveRouteBinding() used to always add `orWhere('public_id', $value)`. Because
 * public_id is a UUID column, resolving by a non-UUID slug/code (e.g. "paystack")
 * made PostgreSQL throw a 22P02 invalid-text-representation error and broke the
 * payment webhook endpoint. SQLite tolerates the bad comparison, so this asserts on
 * the generated SQL to catch the regression on any database driver.
 */
class RouteBindingPublicIdGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Runs against the dedicated PostgreSQL testing database (phpunit.xml).
        // dropAllTables() cascades, clearing any tables (and their FKs) left by other
        // central-connection tests that share this database.
        DB::purge('central');

        Schema::connection('central')->dropAllTables();
        Schema::connection('central')->create('payment_gateways', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('code')->unique();
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::connection('central')->dropAllTables();
        DB::disconnect('central');

        parent::tearDown();
    }

    public function test_non_uuid_value_does_not_query_the_public_id_column(): void
    {
        $sql = $this->captureBindingSql('paystack');

        $this->assertStringNotContainsString('public_id', $sql, 'A non-UUID value must not be compared against the UUID public_id column.');
        $this->assertStringContainsString('code', $sql);
    }

    public function test_uuid_value_still_resolves_by_public_id(): void
    {
        $sql = $this->captureBindingSql((string) Str::uuid());

        $this->assertStringContainsString('public_id', $sql, 'A valid UUID must still be resolvable by public_id.');
    }

    private function captureBindingSql(string $value): string
    {
        DB::connection('central')->enableQueryLog();
        DB::connection('central')->flushQueryLog();

        (new PaymentGateway)->resolveRouteBinding($value);

        $log = DB::connection('central')->getQueryLog();
        DB::connection('central')->disableQueryLog();

        return $log[0]['query'] ?? '';
    }
}
