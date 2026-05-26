<?php

namespace Ticket\FormBuilder\Application;

use App\Models\FormDefinition;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
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

        return FormSubmission::query()->create([
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
