<?php

namespace Ticket\PublicCatalog\Application;

use App\Models\CallForProject;
use App\Models\FormDefinition;
use App\Models\Offer;
use Illuminate\Support\Arr;

class CallForProjectApplicationFormService
{
    private const SUPPORTED_FIELD_TYPES = [
        'text',
        'textarea',
        'email',
        'date',
        'number',
        'country',
        'city',
        'phone',
        'radio',
        'checkbox_group',
        'boolean',
        'file',
    ];

    public function schemaFor(CallForProject $callForProject): ?array
    {
        $baseSchema = $this->baseSchema();
        $publishedForm = $this->publishedFormDefinition($callForProject);

        $schema = $publishedForm instanceof FormDefinition
            ? $this->schemaFromFormDefinition($publishedForm, $baseSchema)
            : $this->schemaFromMeta($callForProject, $baseSchema);

        if ($schema === null) {
            return null;
        }

        $offers = $this->activeOffers($callForProject);
        $paidSetting = data_get($callForProject->meta ?? [], 'application_payment.is_paid');
        $isPaid = $paidSetting === null
            ? collect($offers)->contains(fn (array $offer): bool => (float) ($offer['price'] ?? 0) > 0)
            : filter_var($paidSetting, FILTER_VALIDATE_BOOLEAN);

        if (! $isPaid) {
            $offers = [];
        }

        $hasPaidOffers = $isPaid && collect($offers)->contains(fn (array $offer): bool => (float) ($offer['price'] ?? 0) > 0);
        $requiresReceipt = $isPaid && (bool) data_get($callForProject->meta ?? [], 'application_payment.requires_receipt', false);

        $schema['fields'] = collect($schema['fields'] ?? [])
            ->filter(fn ($field): bool => is_array($field) && filled($field['key'] ?? null))
            ->map(function (array $field) use ($requiresReceipt): array {
                if (($field['key'] ?? null) === 'payment_receipt') {
                    $field['required'] = $requiresReceipt;
                    $field['visible'] = $requiresReceipt || (bool) ($field['visible'] ?? false);
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

    private function baseSchema(): array
    {
        $schema = config('call_for_project_forms.default', []);
        $schema['fields'] = [];
        $schema['steps'] = [
            ['key' => 'identity', 'title' => 'Informations', 'description' => 'Renseignez les informations principales demandées.'],
            ['key' => 'project', 'title' => 'Projet', 'description' => 'Présentez le projet ou le dossier soumis.'],
            ['key' => 'documents', 'title' => 'Documents', 'description' => 'Ajoutez les pièces et justificatifs requis.'],
            ['key' => 'payment', 'title' => 'Validation', 'description' => 'Finalisez la candidature et les éléments liés au paiement si nécessaire.'],
        ];

        return $schema;
    }

    private function schemaFromMeta(CallForProject $callForProject, array $baseSchema): ?array
    {
        $configuredSchema = data_get($callForProject->meta ?? [], 'application_form');

        return is_array($configuredSchema)
            ? $this->mergeConfiguredSchema($baseSchema, $configuredSchema)
            : null;
    }

    private function schemaFromFormDefinition(FormDefinition $form, array $baseSchema): ?array
    {
        $configuredSchema = is_array($form->schema) ? $form->schema : [];

        return $this->mergeConfiguredSchema($baseSchema, array_merge($configuredSchema, [
            'title' => $form->title,
            'description' => $form->description,
            'submit_label' => $form->submit_label,
            'success_message' => $form->success_message,
        ]));
    }

    private function mergeConfiguredSchema(array $baseSchema, array $configuredSchema): ?array
    {
        $fields = $this->normalizeFields($configuredSchema['fields'] ?? []);

        if ($fields === []) {
            return null;
        }

        $schema = $baseSchema;

        foreach (['title', 'description', 'submit_label', 'success_message'] as $key) {
            if (filled($configuredSchema[$key] ?? null)) {
                $schema[$key] = $configuredSchema[$key];
            }
        }

        $schema['fields'] = $fields;

        return $schema;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeFields(mixed $fields): array
    {
        if (! is_array($fields)) {
            return [];
        }

        $normalizedFields = collect($fields)
            ->map(fn ($field): ?array => $this->normalizeField($field))
            ->filter()
            ->values()
            ->all();

        $normalizedFields = $this->autoLinkLocationFields($normalizedFields);

        return $this->isLegacyDefaultFieldSet($normalizedFields) ? [] : $normalizedFields;
    }

    /**
     * @param array<int, array<string, mixed>> $fields
     * @return array<int, array<string, mixed>>
     */
    private function autoLinkLocationFields(array $fields): array
    {
        $countryFields = collect($fields)
            ->filter(fn (array $field): bool => ($field['type'] ?? null) === 'country')
            ->values();

        if ($countryFields->isEmpty()) {
            return $fields;
        }

        return collect($fields)
            ->map(function (array $field) use ($countryFields): array {
                if (! in_array($field['type'] ?? null, ['city', 'phone'], true) || filled($field['country_field'] ?? null)) {
                    return $field;
                }

                $matchingCountry = $countryFields->first(fn (array $countryField): bool => (
                    (($countryField['step'] ?? null) === ($field['step'] ?? null))
                    && (($countryField['section'] ?? null) === ($field['section'] ?? null))
                )) ?? $countryFields->first(fn (array $countryField): bool => (($countryField['step'] ?? null) === ($field['step'] ?? null)))
                    ?? $countryFields->first();

                if (filled($matchingCountry['key'] ?? null)) {
                    $field['country_field'] = $matchingCountry['key'];
                }

                return $field;
            })
            ->values()
            ->all();
    }

    /**
     * Filament Builder stores blocks as [type => ..., data => ...]. The public
     * renderer expects a flat field object, so both formats are accepted here.
     */
    private function normalizeField(mixed $field): ?array
    {
        if (! is_array($field)) {
            return null;
        }

        if (isset($field['type'], $field['data']) && is_array($field['data'])) {
            $field = array_merge($field['data'], ['type' => $field['type']]);
        }

        $type = (string) ($field['type'] ?? 'text');

        if ($type === 'section') {
            return null;
        }

        if (in_array($type, ['checkbox', 'consent'], true)) {
            $type = 'boolean';
        }

        if (! in_array($type, self::SUPPORTED_FIELD_TYPES, true)) {
            $type = 'text';
        }

        $key = trim((string) ($field['key'] ?? ''));
        $label = trim((string) ($field['label'] ?? ''));

        if ($key === '' || $label === '') {
            return null;
        }

        $field['type'] = $type;
        $field['key'] = $key;
        $field['label'] = $label;
        $field['required'] = filter_var($field['required'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (array_key_exists('visible', $field)) {
            $field['visible'] = filter_var($field['visible'], FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($field['options'])) {
            $field['options'] = $this->normalizeOptions($field['options']);
        }

        if (isset($field['accept'])) {
            $field['accept'] = $this->normalizeStringList($field['accept']);
        }

        if (isset($field['column_span'])) {
            $field['column_span'] = in_array((int) $field['column_span'], [1, 2], true) ? (int) $field['column_span'] : 2;
        }

        if (isset($field['max_size_mb'])) {
            $field['max_size_mb'] = max(1, (int) $field['max_size_mb']);
        }

        return Arr::where($field, fn ($value): bool => $value !== null && $value !== '');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function normalizeOptions(mixed $options): array
    {
        if (! is_array($options)) {
            return [];
        }

        if (! array_is_list($options)) {
            $options = collect($options)
                ->map(fn ($label, $value): array => ['value' => (string) $value, 'label' => (string) $label])
                ->values()
                ->all();
        }

        return collect($options)
            ->map(function ($option): ?array {
                if (is_array($option)) {
                    $value = trim((string) ($option['value'] ?? ''));
                    $label = trim((string) ($option['label'] ?? $value));
                } else {
                    $value = trim((string) $option);
                    $label = $value;
                }

                return $value !== '' ? ['value' => $value, 'label' => $label !== '' ? $label : $value] : null;
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function normalizeStringList(mixed $value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(fn ($entry): string => trim((string) $entry))
            ->filter()
            ->values()
            ->all();
    }

    private function publishedFormDefinition(CallForProject $callForProject): ?FormDefinition
    {
        if ($callForProject->relationLoaded('formDefinition')) {
            $form = $callForProject->formDefinition;

            if ($form instanceof FormDefinition && $form->status === 'published') {
                return $form;
            }
        }

        return FormDefinition::query()
            ->where('owner_type', CallForProject::class)
            ->where('owner_id', $callForProject->getKey())
            ->where('status', 'published')
            ->latest('updated_at')
            ->first();
    }

    private function isLegacyDefaultFieldSet(array $fields): bool
    {
        if ($fields === []) {
            return false;
        }

        $defaultFields = collect(config('call_for_project_forms.default.fields', []))
            ->map(fn ($field): ?array => $this->normalizeField($field))
            ->filter()
            ->values()
            ->all();

        if (count($fields) !== count($defaultFields)) {
            return false;
        }

        return $this->fieldSetSignature($fields) === $this->fieldSetSignature($defaultFields);
    }

    private function fieldSetSignature(array $fields): array
    {
        return collect($fields)
            ->map(fn (array $field): string => implode('|', [
                (string) ($field['type'] ?? ''),
                (string) ($field['key'] ?? ''),
                (string) ($field['label'] ?? ''),
            ]))
            ->values()
            ->all();
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
