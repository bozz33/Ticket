<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Ticket\Payments\Application\ReceiptNumberAllocator;

class ReceiptNumberAllocatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('ticket.tenant_connection', 'tenant');
        DB::purge('tenant');

        Schema::connection('tenant')->dropAllTables();
        Schema::connection('tenant')->create('receipt_number_sequences', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('tenant');

        parent::tearDown();
    }

    public function test_numbers_are_sequential_and_zero_padded_within_a_year(): void
    {
        $allocator = new ReceiptNumberAllocator;

        $this->assertSame('RCP-2026-000001', $allocator->allocate('tenant', 2026));
        $this->assertSame('RCP-2026-000002', $allocator->allocate('tenant', 2026));
        $this->assertSame('RCP-2026-000003', $allocator->allocate('tenant', 2026));
    }

    public function test_counter_resets_per_year(): void
    {
        $allocator = new ReceiptNumberAllocator;

        $allocator->allocate('tenant', 2026);
        $allocator->allocate('tenant', 2026);

        $this->assertSame('RCP-2027-000001', $allocator->allocate('tenant', 2027));
        $this->assertSame('RCP-2026-000003', $allocator->allocate('tenant', 2026));
    }
}
