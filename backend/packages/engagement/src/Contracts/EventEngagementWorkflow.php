<?php

namespace Ticket\Engagement\Contracts;

use App\Models\Event;
use App\Models\User;

interface EventEngagementWorkflow
{
    public function summary(?User $user, Event $event): array;

    public function summaries(?User $user, array $identifiers): array;

    public function like(User $user, Event $event): array;

    public function unlike(User $user, Event $event): array;
}
