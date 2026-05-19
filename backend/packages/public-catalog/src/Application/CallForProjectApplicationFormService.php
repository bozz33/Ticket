<?php

namespace Ticket\PublicCatalog\Application;

use App\Models\CallForProject;
use App\Models\Offer;

class CallForProjectApplicationFormService
{
    public function schemaFor(CallForProject $callForProject): array
    {
        $defaultSchema = config('call_for_project_forms.default', []);
        $configuredSchema = data_get($callForProject->meta ?? [], 'application_form');

        $schema = is_array($configuredSchema) && is_array($configuredSchema['fields'] ?? null)
            ? array_replace_recursive($defaultSchema, $configuredSchema)
            : $defaultSchema;

        $offers = $this->activeOffers($callForProject);
        $hasPaidOffers = collect($offers)->contains(fn (array $offer): bool => (float) ($offer['price'] ?? 0) > 0);
        $requiresReceipt = (bool) data_get($callForProject->meta ?? [], 'application_payment.requires_receipt', false);

        $schema['fields'] = collect($schema['fields'] ?? [])
            ->filter(fn ($field): bool => is_array($field) && filled($field['key'] ?? null))
            ->map(function (array $field) use ($hasPaidOffers, $requiresReceipt): array {
                if (($field['key'] ?? null) === 'payment_receipt') {
                    $field['required'] = $requiresReceipt;
                    $field['visible'] = $requiresReceipt || $hasPaidOffers || (bool) ($field['visible'] ?? false);
                }

                return $field;
            })
            ->values()
            ->all();

        $schema['payment'] = [
            'has_offers' => count($offers) > 0,
            'has_paid_offers' => $hasPaidOffers,
            'requires_receipt' => $requiresReceipt,
            'offers' => $offers,
        ];

        return $schema;
    }

    /**
     * @return array<int, array{id: string, title: string, price: float|int, currency: string, cta_label: string}>
     */
    private function activeOffers(CallForProject $callForProject): array
    {
        $offers = $callForProject->relationLoaded('offers')
            ? $callForProject->offers
            : $callForProject->offers()->where('is_active', true)->get();

        return $offers
            ->filter(fn (Offer $offer): bool => (bool) $offer->is_active)
            ->map(fn (Offer $offer): array => [
                'id' => (string) $offer->public_id,
                'title' => (string) $offer->name,
                'price' => $offer->price_amount,
                'currency' => (string) $offer->currency_code,
                'cta_label' => (string) data_get($offer->meta ?? [], 'ctaLabel', 'Payer'),
            ])
            ->values()
            ->all();
    }
}
