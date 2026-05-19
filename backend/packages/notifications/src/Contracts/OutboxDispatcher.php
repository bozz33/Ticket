<?php

namespace Ticket\Notifications\Contracts;

interface OutboxDispatcher
{
    public function dispatchPending(int $limit = 100): array;
}
