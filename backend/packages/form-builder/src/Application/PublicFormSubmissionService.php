<?php

namespace Ticket\FormBuilder\Application;

use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Notifications\FormSubmissionConfirmationNotification;
use App\Notifications\FormSubmissionReceivedNotification;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Ticket\FormBuilder\Contracts\FormSchemaValidator;
use Ticket\FormBuilder\Contracts\FormSubmissionWriter;

class PublicFormSubmissionService implements FormSubmissionWriter
{
    public function __construct(private readonly FormSchemaValidator $validator) {}

    public function submit(FormDefinition $formDefinition, Request $request): FormSubmission
    {
        if ($formDefinition->status !== 'published') {
            throw ValidationException::withMessages([
                'form' => 'Ce formulaire n’est pas disponible.',
            ]);
        }

        $schema = (array) ($formDefinition->schema ?? []);
        $responses = (array) $request->input('responses', []);

        foreach ($this->fileFields($schema) as $field) {
            $key = (string) ($field['key'] ?? '');

            if ($key !== '' && $request->file("files.{$key}") instanceof UploadedFile) {
                $responses[$key] = '__uploaded_file__';
            }
        }

        $data = collect($this->validator->validateSubmission($schema, $responses))
            ->reject(fn ($value, string $key): bool => $this->isFileField($schema, $key))
            ->all();
        $publicId = (string) Str::uuid();
        $files = $this->storeFiles($formDefinition, $publicId, $schema, $request);

        $submission = FormSubmission::query()->create([
            'public_id' => $publicId,
            'form_definition_id' => $formDefinition->getKey(),
            'submitter_user_id' => $request->user()?->getAuthIdentifier(),
            'status' => 'submitted',
            'data' => $data,
            'files' => $files,
            'ip_hash' => $request->ip() ? Hash::make($request->ip()) : null,
            'user_agent_hash' => $request->userAgent() ? hash('sha256', Str::limit((string) $request->userAgent(), 500, '')) : null,
            'submitted_at' => now(),
            'meta' => [
                'form_public_id' => $formDefinition->public_id,
                'form_title' => $formDefinition->title,
            ],
        ]);

        $this->dispatchNotifications($formDefinition, $submission, $data);

        return $submission;
    }

    private function dispatchNotifications(FormDefinition $formDefinition, FormSubmission $submission, array $data): void
    {
        $settings = (array) ($formDefinition->settings ?? []);

        // Notify the form owner (organizer) on every new submission.
        if (($settings['notify_owner_on_submission'] ?? true) !== false) {
            $owner = $formDefinition->owner;

            if ($owner !== null && method_exists($owner, 'notify')) {
                try {
                    $owner->notify(new FormSubmissionReceivedNotification($formDefinition, $submission));
                } catch (\Throwable) {
                    // Notification failure must never block the submission response.
                }
            }
        }

        // Send a confirmation email to the submitter if an email field is present.
        if (($settings['send_confirmation_email'] ?? false) === true) {
            $submitterEmail = $this->resolveSubmitterEmail($data);

            if (filled($submitterEmail)) {
                try {
                    Notification::route('mail', $submitterEmail)
                        ->notify(new FormSubmissionConfirmationNotification($formDefinition, $submission));
                } catch (\Throwable) {
                    // Same: never block the submission.
                }
            }
        }
    }

    private function resolveSubmitterEmail(array $data): ?string
    {
        foreach ($data as $value) {
            if (is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return $value;
            }
        }

        return null;
    }

    private function storeFiles(FormDefinition $formDefinition, string $submissionPublicId, array $schema, Request $request): array
    {
        $storedFiles = [];

        foreach ($this->fileFields($schema) as $field) {
            $key = (string) ($field['key'] ?? '');
            $file = $request->file("files.{$key}");

            if ($key === '' || ! $file instanceof UploadedFile) {
                continue;
            }

            $this->validateUploadedFile($file, $field);

            $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'bin';
            $relativePath = sprintf(
                'form-submissions/%s/%s/%s.%s',
                $formDefinition->public_id,
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

    private function validateUploadedFile(UploadedFile $file, array $field): void
    {
        $acceptedMimes = (array) ($field['accept'] ?? []);
        $maxSizeMb = isset($field['max_size_mb']) ? (float) $field['max_size_mb'] : null;

        if ($acceptedMimes !== []) {
            $fileMime = (string) $file->getMimeType();
            $allowed = false;

            foreach ($acceptedMimes as $pattern) {
                $pattern = (string) $pattern;

                // Support wildcards like "image/*"
                if (str_ends_with($pattern, '/*')) {
                    $prefix = rtrim($pattern, '/*').'/';
                    if (str_starts_with($fileMime, $prefix)) {
                        $allowed = true;
                        break;
                    }
                } elseif ($fileMime === $pattern) {
                    $allowed = true;
                    break;
                }
            }

            if (! $allowed) {
                $key = (string) ($field['key'] ?? 'file');
                throw ValidationException::withMessages([
                    "files.{$key}" => sprintf(
                        'Le type de fichier "%s" n\'est pas accepté. Types autorisés : %s.',
                        $fileMime,
                        implode(', ', $acceptedMimes),
                    ),
                ]);
            }
        }

        if ($maxSizeMb !== null && $maxSizeMb > 0) {
            $maxBytes = (int) ($maxSizeMb * 1024 * 1024);

            if ($file->getSize() > $maxBytes) {
                $key = (string) ($field['key'] ?? 'file');
                throw ValidationException::withMessages([
                    "files.{$key}" => sprintf(
                        'Le fichier dépasse la taille maximale autorisée de %.0f Mo.',
                        $maxSizeMb,
                    ),
                ]);
            }
        }
    }

    private function fileFields(array $schema): array
    {
        return array_values(array_filter($schema['fields'] ?? [], fn ($field): bool => is_array($field)
            && ($field['visible'] ?? true) !== false
            && ($field['type'] ?? null) === 'file'));
    }

    private function isFileField(array $schema, string $key): bool
    {
        foreach ($this->fileFields($schema) as $field) {
            if ((string) ($field['key'] ?? '') === $key) {
                return true;
            }
        }

        return false;
    }
}
