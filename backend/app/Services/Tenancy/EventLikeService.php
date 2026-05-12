<?php

namespace App\Services\Tenancy;

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Str;

class EventLikeService
{
    public function summary(?User $user, Event $event): array
    {
        $likes = $event->likes()->count();
        $liked = $user !== null
            ? $event->likes()->where('user_id', $user->getKey())->exists()
            : false;

        return [
            'liked' => $liked,
            'likes' => $likes,
        ];
    }

    public function summaries(?User $user, array $identifiers): array
    {
        $normalizedIdentifiers = collect($identifiers)
            ->map(fn ($identifier) => is_string($identifier) ? trim($identifier) : '')
            ->filter()
            ->unique()
            ->values();

        if ($normalizedIdentifiers->isEmpty()) {
            return [];
        }

        $publicIds = $normalizedIdentifiers
            ->filter(static fn (string $identifier): bool => Str::isUuid($identifier))
            ->values();

        $slugs = $normalizedIdentifiers
            ->reject(static fn (string $identifier): bool => Str::isUuid($identifier))
            ->values();

        $events = Event::query()
            ->withCount('likes')
            ->where(function ($query) use ($publicIds, $slugs): void {
                if ($slugs->isNotEmpty()) {
                    $query->whereIn('slug', $slugs->all());
                }

                if ($publicIds->isNotEmpty()) {
                    $query->orWhereIn('public_id', $publicIds->all());
                }
            })
            ->get(['id', 'slug', 'public_id']);

        if ($events->isEmpty()) {
            return [];
        }

        $likedEventIds = $user !== null
            ? $user->eventLikes()
                ->whereIn('event_id', $events->pluck('id'))
                ->pluck('event_id')
                ->all()
            : [];

        $likedLookup = array_fill_keys($likedEventIds, true);

        return $events
            ->mapWithKeys(function (Event $event) use ($likedLookup): array {
                $summary = [
                    'liked' => isset($likedLookup[$event->getKey()]),
                    'likes' => (int) ($event->likes_count ?? 0),
                ];

                $keys = array_filter([
                    $event->slug,
                    $event->public_id,
                ]);

                return collect($keys)->mapWithKeys(fn (string $key): array => [$key => $summary])->all();
            })
            ->all();
    }

    public function like(User $user, Event $event): array
    {
        $event->likes()->firstOrCreate([
            'user_id' => $user->getKey(),
        ]);

        return [
            'liked' => true,
            'likes' => $event->likes()->count(),
        ];
    }

    public function unlike(User $user, Event $event): array
    {
        $event->likes()->where('user_id', $user->getKey())->delete();

        return [
            'liked' => false,
            'likes' => $event->likes()->count(),
        ];
    }
}
