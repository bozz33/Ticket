<?php

namespace Ticket\PublicCatalog\Application;

use App\Models\CallForProject;
use App\Models\CallForProjectSubmission;
use App\Models\City;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CallForProjectSubmissionService
{
    public function __construct(
        private readonly CallForProjectApplicationFormService $formService,
    ) {}

    public function submit(CallForProject $callForProject, Request $request): CallForProjectSubmission
    {
        $this->ensureSubmissionWindowIsOpen($callForProject);

        $form = $this->formService->schemaFor($callForProject);

        if ($form === null) {
            throw ValidationException::withMessages([
                'call_for_project' => 'Aucun formulaire de candidature publié n’est disponible pour cet appel à projets.',
            ]);
        }

        $validator = Validator::make(
            $request->all(),
            $this->rulesFor($form),
            [],
            $this->attributeNamesFor($form),
        );

        $validator->after(function ($validator) use ($form, $request): void {
            foreach ($form['fields'] ?? [] as $field) {
                if (! is_array($field) || ($field['visible'] ?? true) === false) {
                    continue;
                }

                $key = (string) ($field['key'] ?? '');
                $type = (string) ($field['type'] ?? 'text');

                if ($key === '') {
                    continue;
                }

                if ($type === 'country') {
                    $value = strtoupper(trim((string) Arr::get($request->all(), "responses.{$key}")));

                    if ($value !== '' && ! Country::query()->where('is_active', true)->where('iso2', $value)->exists()) {
                        $validator->errors()->add("responses.{$key}", 'Le pays sélectionné est invalide.');
                    }
                }

                if ($type === 'city') {
                    $cityId = Arr::get($request->all(), "responses.{$key}");

                    if ($cityId === null || $cityId === '') {
                        continue;
                    }

                    $city = City::query()->with('country')->where('is_active', true)->find($cityId);

                    if (! $city) {
                        $validator->errors()->add("responses.{$key}", 'La ville sélectionnée est invalide.');

                        continue;
                    }

                    $countryField = (string) ($field['country_field'] ?? '');
                    $countryCode = strtoupper(trim((string) Arr::get($request->all(), "responses.{$countryField}")));

                    if ($countryField !== '' && $countryCode !== '' && strtoupper((string) $city->country?->iso2) !== $countryCode) {
                        $validator->errors()->add("responses.{$key}", 'La ville ne correspond pas au pays sélectionné.');
                    }
                }

                if ($type === 'phone') {
                    $dialCode = $this->normalizeDialCode((string) Arr::get($request->all(), "responses.{$key}.dial_code"));
                    $number = trim((string) Arr::get($request->all(), "responses.{$key}.number"));
                    $countryField = (string) ($field['country_field'] ?? '');
                    $countryCode = strtoupper(trim((string) Arr::get($request->all(), "responses.{$countryField}")));

                    if ($number !== '' && ! preg_match('/^[0-9 ]+$/', $number)) {
                        $validator->errors()->add("responses.{$key}.number", 'Le numéro saisi est invalide.');
                    }

                    if ($dialCode !== '' && $countryCode !== '') {
                        $country = Country::query()->where('is_active', true)->where('iso2', $countryCode)->first();
                        $expectedDialCode = $this->normalizeDialCode((string) ($country?->phone_code ?? ''));

                        if ($expectedDialCode !== '' && $expectedDialCode !== $dialCode) {
                            $validator->errors()->add("responses.{$key}.dial_code", 'L\'indicatif ne correspond pas au pays sélectionné.');
                        }
                    }
                }
            }
        });

        $validated = $validator->validate();
        $responses = $validated['responses'] ?? [];
        $submissionPublicId = (string) Str::uuid();
        $storedFiles = $this->storeFiles($callForProject, $submissionPublicId, $form, $request);

        return CallForProjectSubmission::query()->create([
            'public_id' => $submissionPublicId,
            'call_for_project_id' => $callForProject->id,
            'status' => 'submitted',
            'applicant_name' => $this->stringValue(Arr::get($responses, 'full_name')),
            'applicant_email' => $this->stringValue(Arr::get($responses, 'email')),
            'phone_country_code' => $this->normalizeDialCode((string) Arr::get($responses, 'whatsapp_number.dial_code')),
            'phone_number' => $this->stringValue(Arr::get($responses, 'whatsapp_number.number')),
            'country_code' => strtoupper($this->stringValue(Arr::get($responses, 'country_of_residence'))),
            'city_name' => $this->cityNameFromResponse(Arr::get($responses, 'city_of_residence')),
            'answers' => $this->normalizeResponses($form, $responses),
            'files' => $storedFiles,
            'submitted_at' => now(),
            'meta' => [
                'form_version' => $form['version'] ?? 1,
                'form_title' => $form['title'] ?? 'Formulaire de candidature',
                'schema' => $form,
                'source' => 'public_call_for_project_application',
                'ip' => request()->ip(),
                'user_agent' => Str::limit((string) request()->userAgent(), 500, ''),
            ],
        ]);
    }

    private function ensureSubmissionWindowIsOpen(CallForProject $callForProject): void
    {
        if (! $callForProject->is_active || blank($callForProject->published_at)) {
            throw ValidationException::withMessages([
                'call_for_project' => 'Cet appel à projets n\'est pas disponible pour les candidatures.',
            ]);
        }

        if ($callForProject->application_opens_at && $callForProject->application_opens_at->isFuture()) {
            throw ValidationException::withMessages([
                'call_for_project' => 'Les candidatures ne sont pas encore ouvertes.',
            ]);
        }

        if ($callForProject->application_closes_at && $callForProject->application_closes_at->isPast()) {
            throw ValidationException::withMessages([
                'call_for_project' => 'La période de candidature est terminée.',
            ]);
        }
    }

    private function rulesFor(array $form): array
    {
        $rules = [
            'responses' => ['sometimes', 'array'],
            'files' => ['sometimes', 'array'],
        ];

        foreach ($form['fields'] ?? [] as $field) {
            if (! is_array($field) || ($field['visible'] ?? true) === false) {
                continue;
            }

            $key = (string) ($field['key'] ?? '');
            $type = (string) ($field['type'] ?? 'text');
            $required = (bool) ($field['required'] ?? false);

            if ($key === '') {
                continue;
            }

            if ($type === 'file') {
                $rules["files.{$key}"] = array_values(array_filter([
                    $required ? 'required' : 'nullable',
                    'file',
                    $this->mimetypesRule($field['accept'] ?? []),
                    $this->maxSizeRule((int) ($field['max_size_mb'] ?? 10)),
                ]));

                continue;
            }

            $basePath = "responses.{$key}";
            $baseRules = [$required ? 'required' : 'nullable'];

            switch ($type) {
                case 'email':
                    $rules[$basePath] = [...$baseRules, 'string', 'email', 'max:190'];
                    break;
                case 'date':
                    $rules[$basePath] = [...$baseRules, 'date'];
                    break;
                case 'number':
                    $rules[$basePath] = [...$baseRules, 'numeric'];
                    if (isset($field['min'])) {
                        $rules[$basePath][] = 'min:'.$field['min'];
                    }
                    if (isset($field['max'])) {
                        $rules[$basePath][] = 'max:'.$field['max'];
                    }
                    break;
                case 'country':
                    $rules[$basePath] = [...$baseRules, 'string', 'size:2'];
                    break;
                case 'city':
                    $rules[$basePath] = [...$baseRules, 'integer'];
                    break;
                case 'phone':
                    $rules[$basePath] = [...$baseRules, 'array'];
                    $rules["{$basePath}.dial_code"] = [$required ? 'required' : 'nullable', 'string', 'max:10'];
                    $rules["{$basePath}.number"] = [$required ? 'required' : 'nullable', 'string', 'max:30'];
                    $rules["{$basePath}.country_code"] = ['nullable', 'string', 'size:2'];
                    break;
                case 'radio':
                    $rules[$basePath] = [...$baseRules, 'string', Rule::in($this->optionValues($field))];
                    break;
                case 'checkbox_group':
                    $rules[$basePath] = [...$baseRules, 'array'];
                    $rules["{$basePath}.*"] = ['string', Rule::in($this->optionValues($field))];
                    break;
                case 'boolean':
                    $rules[$basePath] = ($field['must_be_true'] ?? false)
                        ? ['accepted']
                        : [...$baseRules, 'boolean'];
                    break;
                case 'textarea':
                case 'text':
                default:
                    $rules[$basePath] = [...$baseRules, 'string'];
                    if (isset($field['max_length'])) {
                        $rules[$basePath][] = 'max:'.$field['max_length'];
                    }
                    break;
            }
        }

        return $rules;
    }

    private function attributeNamesFor(array $form): array
    {
        $attributes = [];

        foreach ($form['fields'] ?? [] as $field) {
            if (! is_array($field) || ($field['visible'] ?? true) === false) {
                continue;
            }

            $key = (string) ($field['key'] ?? '');
            $label = (string) ($field['label'] ?? $key);
            $type = (string) ($field['type'] ?? 'text');

            if ($key === '') {
                continue;
            }

            if ($type === 'file') {
                $attributes["files.{$key}"] = $label;

                continue;
            }

            $attributes["responses.{$key}"] = $label;
            $attributes["responses.{$key}.dial_code"] = $label;
            $attributes["responses.{$key}.number"] = $label;
        }

        return $attributes;
    }

    private function normalizeResponses(array $form, array $responses): array
    {
        $normalized = [];

        foreach ($form['fields'] ?? [] as $field) {
            if (! is_array($field) || ($field['visible'] ?? true) === false) {
                continue;
            }

            $key = (string) ($field['key'] ?? '');
            $type = (string) ($field['type'] ?? 'text');

            if ($key === '' || $type === 'file') {
                continue;
            }

            $value = Arr::get($responses, $key);

            if ($type === 'country') {
                $normalized[$key] = strtoupper($this->stringValue($value));

                continue;
            }

            if ($type === 'city') {
                $city = City::query()->with('country')->find($value);
                $normalized[$key] = $city ? [
                    'id' => $city->id,
                    'name' => $city->name,
                    'slug' => $city->slug,
                    'country_code' => strtoupper((string) $city->country?->iso2),
                ] : null;

                continue;
            }

            if ($type === 'phone') {
                $number = $this->stringValue(Arr::get($value ?? [], 'number'));
                $dialCode = $this->normalizeDialCode((string) Arr::get($value ?? [], 'dial_code'));
                $countryCode = strtoupper($this->stringValue(Arr::get($value ?? [], 'country_code')));
                $digits = preg_replace('/\D+/', '', $number) ?? '';

                $normalized[$key] = [
                    'dial_code' => $dialCode,
                    'number' => $number,
                    'country_code' => $countryCode,
                    'e164_like' => $dialCode !== '' || $digits !== '' ? $dialCode.$digits : '',
                ];

                continue;
            }

            if ($type === 'checkbox_group') {
                $normalized[$key] = array_values(array_filter((array) $value, fn ($entry): bool => $this->stringValue($entry) !== ''));

                continue;
            }

            if ($type === 'boolean') {
                $normalized[$key] = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

                continue;
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }

    private function storeFiles(CallForProject $callForProject, string $submissionPublicId, array $form, Request $request): array
    {
        $storedFiles = [];

        foreach ($form['fields'] ?? [] as $field) {
            if (! is_array($field) || ($field['visible'] ?? true) === false || ($field['type'] ?? null) !== 'file') {
                continue;
            }

            $key = (string) ($field['key'] ?? '');
            $file = $request->file("files.{$key}");

            if (! $file instanceof UploadedFile) {
                continue;
            }

            $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'bin';
            $relativePath = sprintf(
                'call-for-project-submissions/%s/%s/%s.%s',
                $callForProject->public_id,
                $submissionPublicId,
                Str::slug($key),
                Str::lower($extension),
            );

            Storage::disk('local')->putFileAs(
                dirname($relativePath),
                $file,
                basename($relativePath),
            );

            $storedFiles[$key] = [
                'label' => (string) ($field['label'] ?? $key),
                'disk' => 'local',
                'path' => $relativePath,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
                'extension' => Str::lower($extension),
            ];
        }

        return $storedFiles;
    }

    private function optionValues(array $field): array
    {
        return collect($field['options'] ?? [])
            ->map(fn ($option): string => (string) ($option['value'] ?? ''))
            ->filter()
            ->values()
            ->all();
    }

    private function mimetypesRule(array $accept): ?string
    {
        $mimetypes = collect($accept)
            ->filter(fn ($value): bool => is_string($value) && $value !== '')
            ->implode(',');

        return $mimetypes !== '' ? 'mimetypes:'.$mimetypes : null;
    }

    private function maxSizeRule(int $maxSizeMb): string
    {
        return 'max:'.max(1, $maxSizeMb) * 1024;
    }

    private function stringValue(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    private function normalizeDialCode(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        return $digits !== '' ? '+'.$digits : '';
    }

    private function cityNameFromResponse(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return City::query()->find($value)?->name;
    }
}
