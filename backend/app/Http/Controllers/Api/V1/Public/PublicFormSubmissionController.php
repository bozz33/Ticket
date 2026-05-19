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

    public function show(FormDefinition $formDefinition): JsonResponse
    {
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

    public function submit(FormDefinition $formDefinition, Request $request): JsonResponse
    {
        $submission = $this->submissions->submit($formDefinition, $request);

        return response()->json([
            'data' => [
                'id' => $submission->public_id,
                'status' => $submission->status,
                'submitted_at' => $submission->submitted_at?->toIso8601String(),
            ],
        ], 201);
    }
}
