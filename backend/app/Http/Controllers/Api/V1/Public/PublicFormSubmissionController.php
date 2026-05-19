<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\FormDefinition;
use App\Services\Forms\PublicFormSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicFormSubmissionController extends Controller
{
    public function __construct(private readonly PublicFormSubmissionService $submissions) {}

    public function show(string $tenant, string $formDefinition): JsonResponse
    {
        $formDefinition = $this->resolveFormDefinition($formDefinition);

        abort_unless($formDefinition->status === 'published', 404);

        return response()->json([
            'data' => [
                'id' => $formDefinition->public_id,
                'title' => $formDefinition->title,
                'description' => $formDefinition->description,
                'submit_label' => $formDefinition->submit_label,
                'success_message' => $formDefinition->success_message,
                'schema' => $formDefinition->schema ?? ['fields' => []],
                'settings' => $formDefinition->settings ?? [],
            ],
        ]);
    }

    public function submit(string $tenant, string $formDefinition, Request $request): JsonResponse
    {
        $formDefinition = $this->resolveFormDefinition($formDefinition);
        $submission = $this->submissions->submit($formDefinition, $request);

        return response()->json([
            'data' => [
                'id' => $submission->public_id,
                'status' => $submission->status,
                'submitted_at' => $submission->submitted_at?->toIso8601String(),
            ],
        ], 201);
    }

    private function resolveFormDefinition(string $formDefinition): FormDefinition
    {
        return FormDefinition::query()
            ->where('public_id', $formDefinition)
            ->orWhere('id', $formDefinition)
            ->firstOrFail();
    }
}
