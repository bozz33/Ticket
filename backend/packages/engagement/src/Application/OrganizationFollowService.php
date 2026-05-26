<?php

namespace Ticket\Engagement\Application;

use App\Models\OrganizationProfile;
use App\Models\User;
use Ticket\Engagement\Contracts\OrganizationAudienceWorkflow;

class OrganizationFollowService implements OrganizationAudienceWorkflow
{
    public function status(User $user): array
    {
        $profile = $this->profile();

        return [
            'following' => $profile->followers()->where('user_id', $user->getKey())->exists(),
            'followers' => $profile->followers()->count(),
        ];
    }

    public function follow(User $user): array
    {
        $profile = $this->profile();

        $profile->followers()->firstOrCreate([
            'user_id' => $user->getKey(),
        ]);

        return [
            'following' => true,
            'followers' => $profile->followers()->count(),
        ];
    }

    public function unfollow(User $user): array
    {
        $profile = $this->profile();

        $profile->followers()->where('user_id', $user->getKey())->delete();

        return [
            'following' => false,
            'followers' => $profile->followers()->count(),
        ];
    }

    private function profile(): OrganizationProfile
    {
        return OrganizationProfile::query()->firstOrCreate([], []);
    }
}
