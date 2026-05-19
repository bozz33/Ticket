<?php

namespace App\Services\Ticketing;

use App\Models\Event;
use App\Models\EventTicket;
use App\Models\Offer;
use Illuminate\Support\Arr;

class EventTicketOfferSyncService
{
    public function sync(EventTicket $ticket): Offer
    {
        $event = $ticket->event;

        if (! $event instanceof Event) {
            throw new \RuntimeException('Le ticket doit être lié à un événement avant synchronisation.');
        }

        $offer = $ticket->offer ?: new Offer();
        $offer->fill([
            'offerable_type' => Event::class,
            'offerable_id' => $event->getKey(),
            'offer_type' => $ticket->ticket_type ?: 'event_ticket',
            'name' => $ticket->name,
            'code' => $ticket->code,
            'description' => $ticket->description,
            'price_amount' => $ticket->price_amount,
            'currency_code' => $ticket->currency_code ?: $event->currency_code,
            'quantity_total' => $ticket->quantity_total,
            'quantity_sold' => $ticket->quantity_sold,
            'min_per_order' => $ticket->min_per_order,
            'max_per_order' => $ticket->max_per_order,
            'max_per_account' => $ticket->max_per_account,
            'sales_start_at' => $ticket->sales_start_at,
            'sales_end_at' => $ticket->sales_end_at,
            'is_active' => $ticket->is_active,
            'sort_order' => $ticket->sort_order,
            'meta' => $this->mergeMeta($offer, $ticket),
        ]);
        $offer->save();

        if ((int) $ticket->offer_id !== (int) $offer->getKey()) {
            $ticket->forceFill(['offer_id' => $offer->getKey()])->save();
        }

        return $offer;
    }

    public function backfillFromEventOffers(): array
    {
        $created = 0;
        $linked = 0;
        $skipped = 0;

        Offer::query()
            ->where('offerable_type', Event::class)
            ->orderBy('id')
            ->chunkById(100, function ($offers) use (&$created, &$linked, &$skipped): void {
                foreach ($offers as $offer) {
                    $existing = EventTicket::query()->where('offer_id', $offer->getKey())->first();

                    if ($existing) {
                        $linked++;
                        continue;
                    }

                    if (! Event::query()->whereKey($offer->offerable_id)->exists()) {
                        $skipped++;
                        continue;
                    }

                    EventTicket::query()->create([
                        'event_id' => $offer->offerable_id,
                        'offer_id' => $offer->getKey(),
                        'name' => $offer->name,
                        'code' => $offer->code,
                        'description' => $offer->description,
                        'ticket_type' => $offer->offer_type ?: 'standard',
                        'price_amount' => $offer->price_amount,
                        'currency_code' => $offer->currency_code,
                        'quantity_total' => $offer->quantity_total,
                        'quantity_sold' => $offer->quantity_sold,
                        'quantity_reserved' => 0,
                        'min_per_order' => $offer->min_per_order,
                        'max_per_order' => $offer->max_per_order,
                        'max_per_account' => $offer->max_per_account,
                        'sales_start_at' => $offer->sales_start_at,
                        'sales_end_at' => $offer->sales_end_at,
                        'is_active' => $offer->is_active,
                        'sort_order' => $offer->sort_order,
                        'meta' => array_merge((array) ($offer->meta ?? []), [
                            'source' => 'offer_backfill',
                            'legacy_offer_public_id' => $offer->public_id,
                        ]),
                    ]);

                    $created++;
                }
            });

        return [
            'created' => $created,
            'linked' => $linked,
            'skipped' => $skipped,
        ];
    }

    private function mergeMeta(Offer $offer, EventTicket $ticket): array
    {
        $offerMeta = (array) ($offer->meta ?? []);
        $ticketMeta = (array) ($ticket->meta ?? []);

        return array_merge($offerMeta, Arr::except($ticketMeta, ['source']), [
            'source' => 'event_ticket_sync',
            'event_ticket_public_id' => $ticket->public_id,
        ]);
    }
}
