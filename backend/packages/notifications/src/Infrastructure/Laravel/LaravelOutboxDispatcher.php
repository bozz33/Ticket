<?php

namespace Ticket\Notifications\Infrastructure\Laravel;

use Illuminate\Support\Facades\DB;
use Ticket\Notifications\Application\OutboxStatuses;
use Ticket\Notifications\Contracts\OutboxDispatcher;
use Ticket\Notifications\Events\DomainOutboxMessagePublished;

class LaravelOutboxDispatcher implements OutboxDispatcher
{
    public function dispatchPending(int $limit = 100): array
    {
        $summary = [
            'processed' => 0,
            'failed' => 0,
        ];

        DomainOutboxMessage::query()
            ->where('status', OutboxStatuses::Pending)
            ->where('available_at', '<=', now())
            ->orderBy('id')
            ->limit(max(1, $limit))
            ->get()
            ->each(function (DomainOutboxMessage $message) use (&$summary): void {
                try {
                    DB::connection(config('ticket.central_connection', 'central'))->transaction(function () use ($message): void {
                        event(new DomainOutboxMessagePublished(
                            (string) $message->event_id,
                            (string) $message->type,
                            (array) $message->payload,
                            (array) $message->metadata,
                        ));

                        $message->forceFill([
                            'status' => OutboxStatuses::Published,
                            'published_at' => now(),
                            'attempts' => (int) $message->attempts + 1,
                            'last_error' => null,
                        ])->save();
                    });

                    $summary['processed']++;
                } catch (\Throwable $exception) {
                    $message->forceFill([
                        'status' => OutboxStatuses::Failed,
                        'attempts' => (int) $message->attempts + 1,
                        'last_error' => $exception->getMessage(),
                    ])->save();

                    $summary['failed']++;
                }
            });

        return $summary;
    }
}
