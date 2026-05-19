<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Ticket\Notifications\Events\DomainOutboxMessagePublished;
use Ticket\Notifications\Infrastructure\Laravel\DomainOutboxMessage;
use Ticket\Notifications\Infrastructure\Laravel\LaravelDomainEventPublisher;
use Ticket\Notifications\Infrastructure\Laravel\LaravelOutboxDispatcher;

class NotificationsOutboxTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.central.driver', 'sqlite');
        config()->set('database.connections.central.database', ':memory:');

        DB::purge('central');

        Schema::connection('central')->create('domain_outbox_messages', function (Blueprint $table): void {
            $table->id();
            $table->uuid('event_id')->unique();
            $table->string('type');
            $table->string('aggregate_type')->nullable();
            $table->string('aggregate_id')->nullable();
            $table->json('payload');
            $table->json('metadata')->nullable();
            $table->string('status', 32)->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('available_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function test_domain_events_are_persisted_and_dispatched_from_outbox(): void
    {
        Event::fake();

        $eventId = (new LaravelDomainEventPublisher)->publish(
            'ticketing.refund.requested',
            ['order_reference' => 'ORD-1'],
            'orders',
            '1',
            ['module' => 'ticketing'],
        );

        $this->assertDatabaseHas('domain_outbox_messages', [
            'event_id' => $eventId,
            'type' => 'ticketing.refund.requested',
            'status' => 'pending',
        ], 'central');

        $summary = (new LaravelOutboxDispatcher)->dispatchPending(10);

        $this->assertSame(['processed' => 1, 'failed' => 0], $summary);
        $this->assertSame('published', DomainOutboxMessage::query()->first()?->status);

        Event::assertDispatched(DomainOutboxMessagePublished::class);
    }
}
