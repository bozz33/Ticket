<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventTicket;
use App\Models\Offer;
use App\Models\Order;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Ticket\Ticketing\Contracts\ReceiptCatalog;

class PublicReceiptVerificationController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly ReceiptCatalog $receiptCatalog,
    ) {}

    public function show(string $tenant, string $receipt): JsonResponse
    {
        $record = $this->receiptCatalog->findByIdentifier($receipt);

        if ($record === null) {
            return response()->json(['message' => 'Reçu introuvable.'], 404);
        }

        $record->loadMissing([
            'order.accessPasses.offer.offerable',
            'order.accessPasses.passable',
            'order.offer.offerable',
            'order.orderable',
        ]);

        $buyerPhone = (string) ($record->order?->buyer_phone ?? '');
        $buyerEmail = (string) ($record->buyer_email ?? '');
        $gatewayReference = (string) data_get($record->meta ?? [], 'gateway_reference', $record->order?->transaction_reference);
        $gatewayTransactionId = data_get($record->meta ?? [], 'gateway_transaction_id');
        $event = $this->eventFromOrder($record->order);

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => [
                'public_id' => $record->public_id,
                'reference' => $record->reference,
                'status' => $record->status,
                'total_amount' => (int) $record->total_amount,
                'currency_code' => (string) $record->currency_code,
                'issued_at' => $record->issued_at?->toIso8601String(),
                'buyer_name' => $record->buyer_name,
                'buyer_email_masked' => $this->maskEmail($buyerEmail),
                'buyer_phone_masked' => $this->maskPhone($buyerPhone),
                'order_reference' => $record->order?->reference,
                'transaction_reference' => $record->order?->transaction_reference,
                'gateway_reference' => $gatewayReference,
                'gateway_transaction_id' => $gatewayTransactionId,
                'offer_name' => $record->order?->offer?->name,
                'access_passes_count' => $record->order?->accessPasses?->count() ?? 0,
                'event' => $this->eventPayload($event),
            ],
        ]);
    }

    private function eventFromOrder(?Order $order): ?Event
    {
        if (! $order instanceof Order) {
            return null;
        }

        foreach ($order->accessPasses ?? [] as $pass) {
            $event = $this->eventFromSubject($pass->passable) ?? $this->eventFromOffer($pass->offer);

            if ($event instanceof Event) {
                return $event;
            }
        }

        return $this->eventFromSubject($order->orderable) ?? $this->eventFromOffer($order->offer);
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

        $event->loadMissing(['dates', 'city']);
        $primaryDate = $event->dates->first();
        $cityName = $event->city?->name;
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

    private function maskEmail(string $email): ?string
    {
        if ($email === '' || ! str_contains($email, '@')) {
            return null;
        }

        [$localPart, $domain] = explode('@', Str::lower($email), 2);

        if ($localPart === '') {
            return null;
        }

        $visible = Str::substr($localPart, 0, min(2, Str::length($localPart)));
        $masked = $visible.str_repeat('*', max(2, Str::length($localPart) - Str::length($visible)));

        return sprintf('%s@%s', $masked, $domain);
    }

    private function maskPhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone ?? '');

        if ($digits === '') {
            return null;
        }

        $suffix = Str::substr($digits, -4);

        return sprintf('*** *** %s', $suffix);
    }
}
