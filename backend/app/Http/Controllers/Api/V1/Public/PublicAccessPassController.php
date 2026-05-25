<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Event;
use App\Models\EventTicket;
use App\Models\Offer;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Ticket\Ticketing\Contracts\AccessPassCatalog;

class PublicAccessPassController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly AccessPassCatalog $accessPassCatalog,
    ) {}

    public function show(string $tenant, string $code): JsonResponse
    {
        $pass = $this->accessPassCatalog->findByCode($code);

        if ($pass === null) {
            return response()->json(['message' => 'Pass introuvable.'], 404);
        }

        $pass->loadMissing([
            'offer.offerable',
            'order.offer.offerable',
            'order.orderable',
            'passable',
        ]);

        $event = $this->eventFromSubject($pass->passable)
            ?? $this->eventFromOffer($pass->offer)
            ?? $this->eventFromSubject($pass->order?->orderable)
            ?? $this->eventFromOffer($pass->order?->offer);

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => [
                'public_id' => $pass->public_id,
                'type' => $pass->type->value,
                'type_label' => $pass->type->label(),
                'status' => $pass->status->value,
                'holder_name' => $pass->holder_name,
                'used_at' => $pass->used_at?->toIso8601String(),
                'expires_at' => $pass->expires_at?->toIso8601String(),
                'qr_payload' => $pass->toQrPayload(),
                'event' => $this->eventPayload($event),
            ],
        ]);
    }

    private function eventFromOffer(?Offer $offer): ?Event
    {
        if (! $offer instanceof Offer) {
            return null;
        }

        $offer->loadMissing('offerable');

        return $this->eventFromSubject($offer->offerable);
    }

    private function eventFromSubject(mixed $subject): ?Event
    {
        if ($subject instanceof Event) {
            return $subject;
        }

        if ($subject instanceof EventTicket) {
            $subject->loadMissing('event');

            return $subject->event;
        }

        if ($subject instanceof Offer) {
            return $this->eventFromOffer($subject);
        }

        return null;
    }

    private function eventPayload(?Event $event): ?array
    {
        if (! $event instanceof Event) {
            return null;
        }

        $event->loadMissing('dates');
        $primaryDate = $event->dates->first();
        $cityName = $event->city_id ? City::query()->whereKey($event->city_id)->value('name') : null;
        $startsAt = $primaryDate?->starts_at ?? data_get($event->meta ?? [], 'schedule.starts_at');
        $endsAt = $primaryDate?->ends_at ?? data_get($event->meta ?? [], 'schedule.ends_at');
        $locationParts = array_values(array_filter([
            $event->venue_name,
            $event->venue_address,
            $cityName,
            $event->country_code,
        ], fn ($value): bool => filled($value)));

        return [
            'public_id' => $event->public_id,
            'title' => $event->title,
            'slug' => $event->slug,
            'starts_at' => $startsAt instanceof \DateTimeInterface ? $startsAt->format(DATE_ATOM) : $startsAt,
            'ends_at' => $endsAt instanceof \DateTimeInterface ? $endsAt->format(DATE_ATOM) : $endsAt,
            'venue_name' => $event->venue_name,
            'venue_address' => $event->venue_address,
            'city' => $cityName,
            'country_code' => $event->country_code,
            'location' => implode(', ', $locationParts),
        ];
    }
}
