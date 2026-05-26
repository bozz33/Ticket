<?php

namespace Ticket\Notifications\Contracts;

interface OutboxDispatcher
{
    public function dispatchPending(int $limit = 100): array;

    public function retryFailed(int $limit = 100, int $delaySeconds = 60): array;

    public function stats(): array;
}
