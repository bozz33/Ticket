<?php

namespace Ticket\Ticketing\Application;

use App\Models\ContentLike;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Str;

class EventLikeService
{
    public function summary(?User $user, Event $event): array
    {
        $likes = $this->likesCount($event);
        $liked = $user !== null && $this->isLikedBy($user, $event);

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

        return $events
            ->mapWithKeys(function (Event $event) use ($user): array {
                $summary = [
                    'liked' => $user !== null && $this->isLikedBy($user, $event),
                    'likes' => $this->likesCount($event),
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
        ContentLike::query()->firstOrCreate([
            'module' => 'evenements',
            'content_slug' => $event->slug,
            'user_id' => $user->getKey(),
        ], [
            'content_public_id' => $event->public_id,
        ]);

        $event->likes()->where('user_id', $user->getKey())->delete();

        return [
            'liked' => true,
            'likes' => $this->likesCount($event),
        ];
    }

    public function unlike(User $user, Event $event): array
    {
        ContentLike::query()
            ->where('module', 'evenements')
            ->where('content_slug', $event->slug)
            ->where('user_id', $user->getKey())
            ->delete();

        $event->likes()->where('user_id', $user->getKey())->delete();

        return [
            'liked' => false,
            'likes' => $this->likesCount($event),
        ];
    }

    private function isLikedBy(User $user, Event $event): bool
    {
        return ContentLike::query()
            ->where('module', 'evenements')
            ->where('content_slug', $event->slug)
            ->where('user_id', $user->getKey())
            ->exists()
            || $event->likes()->where('user_id', $user->getKey())->exists();
    }

    private function likesCount(Event $event): int
    {
        $contentUserIds = ContentLike::query()
            ->where('module', 'evenements')
            ->where('content_slug', $event->slug)
            ->pluck('user_id')
            ->all();

        $legacyUserIds = $event->likes()
            ->pluck('user_id')
            ->all();

        return collect($contentUserIds)
            ->merge($legacyUserIds)
            ->unique()
            ->count();
    }
}
