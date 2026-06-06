<?php

namespace Ticket\FormBuilder\Application;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Ticket\FormBuilder\Contracts\FormSchemaValidator;

class DynamicFormValidator implements FormSchemaValidator
{
    public const SUPPORTED_TYPES = [
        'text',
        'textarea',
        'email',
        'phone',
        'number',
        'date',
        'time',
        'datetime',
        'date_range',
        'rating',
        'hidden',
        'select',
        'radio',
        'checkbox',
        'checkbox_group',
        'country',
        'city',
        'file',
        'url',
        'boolean',
        'consent',
        'section',
    ];

    public function validateSchema(array $schema): array
    {
        $validator = Validator::make($schema, [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'submit_label' => ['nullable', 'string', 'max:80'],
            'success_message' => ['nullable', 'string'],
            'fields' => ['required', 'array'],
            'fields.*.key' => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z0-9_\-]+$/'],
            'fields.*.type' => ['required', 'string'],
            'fields.*.label' => ['required_unless:fields.*.type,section', 'nullable', 'string', 'max:255'],
            'fields.*.required' => ['sometimes', 'boolean'],
            'fields.*.visible' => ['sometimes', 'boolean'],
            'fields.*.visible_if' => ['sometimes'],
            'fields.*.options' => ['sometimes', 'array'],
        ]);

        $validator->after(function ($validator) use ($schema): void {
            $keys = [];

            foreach ($schema['fields'] ?? [] as $index => $field) {
                if (! is_array($field)) {
                    continue;
                }

                $key = (string) ($field['key'] ?? '');
                $type = (string) ($field['type'] ?? '');

                if ($key !== '') {
                    if (in_array($key, $keys, true)) {
                        $validator->errors()->add("fields.{$index}.key", 'La clé du champ doit être unique.');
                    }

                    $keys[] = $key;
                }

                if (! in_array($type, self::SUPPORTED_TYPES, true)) {
                    $validator->errors()->add("fields.{$index}.type", 'Le type de champ est invalide.');
                }

                $this->validateVisibilityConditions($validator, $field, $index);

                if (in_array($type, ['select', 'radio', 'checkbox_group'], true) && count((array) ($field['options'] ?? [])) === 0) {
                    $validator->errors()->add("fields.{$index}.options", 'Ce type de champ nécessite des options.');
                }
            }
        });

        return $validator->validate();
    }

    public function validateSubmission(array $schema, array $data): array
    {
        $this->validateSchema($schema);

        $rules = [];
        $attributes = [];

        foreach ($schema['fields'] ?? [] as $field) {
            if (! is_array($field) || ($field['visible'] ?? true) === false) {
                continue;
            }

            $key = (string) ($field['key'] ?? '');
            $type = (string) ($field['type'] ?? 'text');

            if ($key === '' || $type === 'section') {
                continue;
            }

            $rules["responses.{$key}"] = $this->rulesForField($field);
            $attributes["responses.{$key}"] = (string) ($field['label'] ?? $key);
        }

        return Validator::make(['responses' => $data], $rules, [], $attributes)->validate()['responses'] ?? [];
    }

    private function rulesForField(array $field): array
    {
        $type = (string) ($field['type'] ?? 'text');
        $required = (bool) ($field['required'] ?? false);
        $rules = [$required ? 'required' : 'nullable'];

        return array_values(array_filter(array_merge($rules, match ($type) {
            'email' => ['email:rfc', 'max:255'],
            'url' => ['url', 'max:255'],
            'number' => ['numeric'],
            'date' => ['date'],
            'time' => ['date_format:H:i'],
            'datetime' => ['date'],
            'date_range' => ['array'],
            'rating' => ['integer', 'min:0', 'max:'.max(1, (int) ($field['max_rating'] ?? 5))],
            'hidden' => ['string', 'max:5000'],
            'checkbox', 'boolean', 'consent' => ['boolean'],
            'checkbox_group' => ['array'],
            'select', 'radio' => ['string', 'max:255', function (string $attribute, mixed $value, callable $fail) use ($field): void {
                $allowed = collect((array) ($field['options'] ?? []))
                    ->map(fn ($option) => is_array($option) ? (string) Arr::get($option, 'value') : (string) $option)
                    ->all();

                if ($value !== null && $value !== '' && ! in_array((string) $value, $allowed, true)) {
                    $fail('La valeur sélectionnée est invalide.');
                }
            }],
            'file' => [],
            default => ['string', 'max:5000'],
        })));
    }

    private function validateVisibilityConditions($validator, array $field, int $index): void
    {
        if (! array_key_exists('visible_if', $field)) {
            return;
        }

        $conditions = array_is_list((array) $field['visible_if'])
            ? (array) $field['visible_if']
            : [$field['visible_if']];

        foreach ($conditions as $conditionIndex => $condition) {
            if (! is_array($condition)) {
                $validator->errors()->add("fields.{$index}.visible_if.{$conditionIndex}", 'La condition d’affichage est invalide.');

                continue;
            }

            if (blank($condition['field'] ?? '')) {
                $validator->errors()->add("fields.{$index}.visible_if.{$conditionIndex}.field", 'Le champ source est requis.');
            }

            $operator = (string) ($condition['operator'] ?? 'equals');

            if (! in_array($operator, ['equals', 'not_equals', 'in', 'not_in', 'filled', 'empty'], true)) {
                $validator->errors()->add("fields.{$index}.visible_if.{$conditionIndex}.operator", 'L’opérateur de condition est invalide.');
            }
        }
    }
}
