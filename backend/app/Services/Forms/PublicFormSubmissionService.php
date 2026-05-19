<?php

namespace App\Services\Forms;

use App\Models\FormDefinition;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PublicFormSubmissionService
{
    public function __construct(private readonly DynamicFormValidator $validator) {}

    public function submit(FormDefinition $formDefinition, Request $request): FormSubmission
    {
        if ($formDefinition->status !== 'published') {
            throw ValidationException::withMessages([
                'form' => 'Ce formulaire n’est pas disponible.',
            ]);
        }

        $schema = (array) ($formDefinition->schema ?? []);
        $data = $this->validator->validateSubmission($schema, (array) $request->input('responses', []));

        return FormSubmission::query()->create([
            'form_definition_id' => $formDefinition->getKey(),
            'submitter_user_id' => $request->user()?->getAuthIdentifier(),
            'status' => 'submitted',
            'data' => $data,
            'files' => [],
            'ip_hash' => $request->ip() ? Hash::make($request->ip()) : null,
            'user_agent_hash' => $request->userAgent() ? hash('sha256', Str::limit((string) $request->userAgent(), 500, '')) : null,
            'submitted_at' => now(),
            'meta' => [
                'form_public_id' => $formDefinition->public_id,
                'form_title' => $formDefinition->title,
            ],
        ]);
    }
}
