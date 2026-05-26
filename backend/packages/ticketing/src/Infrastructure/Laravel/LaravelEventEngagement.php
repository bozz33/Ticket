<?php

namespace Ticket\Ticketing\Infrastructure\Laravel;

use App\Models\Event;
use App\Models\User;
use Ticket\Engagement\Contracts\EventEngagementWorkflow;
use Ticket\Ticketing\Contracts\EventEngagement;

class LaravelEventEngagement implements EventEngagement
{
    public function __construct(private readonly EventEngagementWorkflow $likes) {}

    public function summary(?User $user, Event $event): array
    {
        return $this->likes->summary($user, $event);
    }

    public function summaries(?User $user, array $identifiers): array
    {
        return $this->likes->summaries($user, $identifiers);
    }

    public function like(User $user, Event $event): array
    {
        return $this->likes->like($user, $event);
    }

    public function unlike(User $user, Event $event): array
    {
        return $this->likes->unlike($user, $event);
    }
}
