<?php

namespace Ticket\Ticketing\Infrastructure\Laravel;

use App\Models\User;
use Ticket\Ticketing\Application\OrganizationFollowService;
use Ticket\Ticketing\Contracts\OrganizationAudience;

class LaravelOrganizationAudience implements OrganizationAudience
{
    public function __construct(private readonly OrganizationFollowService $audience) {}

    public function status(User $user): array
    {
        return $this->audience->status($user);
    }

    public function follow(User $user): array
    {
        return $this->audience->follow($user);
    }

    public function unfollow(User $user): array
    {
        return $this->audience->unfollow($user);
    }
}
