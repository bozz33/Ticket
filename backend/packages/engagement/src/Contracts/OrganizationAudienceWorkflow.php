<?php

namespace Ticket\Engagement\Contracts;

use App\Models\User;

interface OrganizationAudienceWorkflow
{
    public function status(User $user): array;

    public function follow(User $user): array;

    public function unfollow(User $user): array;
}
