<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Ticket\Notifications\Domain\DomainEventEnvelope;
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

    public function test_domain_event_envelope_is_versioned_in_outbox_payload(): void
    {
        $event = DomainEventEnvelope::make(
            type: 'order.paid',
            payload: ['order_reference' => 'ORD-2'],
            aggregateType: 'orders',
            aggregateId: '2',
            metadata: ['tenant_id' => 'tenant-demo'],
            version: 2,
        );

        $eventId = (new LaravelDomainEventPublisher)->publishEnvelope($event);
        $message = DomainOutboxMessage::query()->where('event_id', $eventId)->firstOrFail();

        $this->assertSame(2, data_get($message->payload, '_event.version'));
        $this->assertSame('ORD-2', data_get($message->payload, 'order_reference'));
        $this->assertSame(2, data_get($message->metadata, 'event_version'));
        $this->assertSame('tenant-demo', data_get($message->metadata, 'tenant_id'));
    }

    public function test_failed_outbox_messages_can_be_retried_and_counted(): void
    {
        DomainOutboxMessage::query()->create([
            'event_id' => '00000000-0000-0000-0000-000000000001',
            'type' => 'notification.email.failed',
            'payload' => ['email' => 'user@example.test'],
            'metadata' => [],
            'status' => 'failed',
            'attempts' => 2,
            'available_at' => now(),
            'last_error' => 'timeout',
        ]);

        $dispatcher = new LaravelOutboxDispatcher;

        $this->assertSame(['failed' => 1], $dispatcher->stats());

        $summary = $dispatcher->retryFailed(10, 30);

        $this->assertSame(['retried' => 1, 'delay_seconds' => 30], $summary);
        $this->assertSame(['pending' => 1], $dispatcher->stats());

        $message = DomainOutboxMessage::query()->firstOrFail();

        $this->assertSame('pending', $message->status);
        $this->assertNull($message->last_error);
        $this->assertTrue($message->available_at->greaterThanOrEqualTo(now()->addSeconds(25)));
    }
}
