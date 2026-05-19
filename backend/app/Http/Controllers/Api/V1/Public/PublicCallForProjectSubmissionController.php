<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Exceptions\BuyerAccountActionBlockedException;
use App\Http\Controllers\Controller;
use App\Models\CallForProject;
use App\Models\User;
use App\Services\Auth\TenantTokenService;
use App\Support\Buyers\BuyerAccountReadiness;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Ticket\PublicCatalog\Contracts\CallForProjectApplications;

class PublicCallForProjectSubmissionController extends Controller
{
    public function __construct(
        private readonly TenantTokenService $tenantTokenService,
        private readonly BuyerAccountReadiness $buyerAccountReadiness,
    ) {}

    public function __invoke(
        Request $request,
        string $tenant,
        string $callForProject,
        CallForProjectApplications $callForProjectApplications,
    ): JsonResponse {
        $record = CallForProject::query()
            ->where('slug', $callForProject)
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->with(['offers' => fn ($query) => $query->where('is_active', true)])
            ->first();

        if (! $record) {
            return response()->json([
                'message' => 'Appel à projets introuvable.',
            ], 404);
        }

        $buyer = $this->resolveAuthenticatedBuyer($request);

        if ($buyer === null) {
            return response()->json([
                'message' => 'Connexion acheteur requise pour soumettre une candidature.',
                'code' => 'AUTH_REQUIRED',
            ], 401);
        }

        try {
            $this->buyerAccountReadiness->assertReadyForSensitiveAction($buyer, 'soumettre une candidature');
        } catch (BuyerAccountActionBlockedException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'code' => $exception->errorCode(),
                'requirements' => $exception->requirements(),
            ], 422);
        }

        $submission = $callForProjectApplications->submit($record, $request);

        return response()->json([
            'message' => 'Votre candidature a été enregistrée avec succès.',
            'data' => [
                'id' => $submission->public_id,
                'status' => $submission->status,
                'submitted_at' => $submission->submitted_at?->toIso8601String(),
            ],
        ], 201);
    }

    private function resolveAuthenticatedBuyer(Request $request): ?User
    {
        $header = (string) $request->header('Authorization', '');

        if (! str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = trim(substr($header, 7));

        if ($token === '') {
            return null;
        }

        $apiToken = $this->tenantTokenService->findToken($token);

        if ($apiToken === null || ! $apiToken->user instanceof User || ! $apiToken->user->is_active) {
            return null;
        }

        $apiToken->touchLastUsed();

        return $apiToken->user;
    }
}
