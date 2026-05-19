<?php

namespace Ticket\Ticketing\Contracts;

use App\Models\User;

interface OrganizationAudience
{
    public function status(User $user): array;

    public function follow(User $user): array;

    public function unfollow(User $user): array;
}
