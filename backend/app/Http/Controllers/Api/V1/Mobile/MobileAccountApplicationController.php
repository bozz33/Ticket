<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Models\CallForProjectSubmission;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileAccountApplicationController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');

        $submissions = CallForProjectSubmission::query()
            ->with('callForProject')
            ->where('applicant_email', $user->email)
            ->latest('submitted_at')
            ->limit(max(1, min(100, (int) $request->query('limit', 50))))
            ->get();

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $submissions->map(fn (CallForProjectSubmission $submission): array => [
                'id' => $submission->id,
                'public_id' => $submission->public_id,
                'status' => $submission->status,
                'applicant_name' => $submission->applicant_name,
                'applicant_email' => $submission->applicant_email,
                'submitted_at' => $submission->submitted_at?->toISOString(),
                'reviewed_at' => $submission->reviewed_at?->toISOString(),
                'call_for_project' => $submission->callForProject ? [
                    'id' => $submission->callForProject->id,
                    'public_id' => $submission->callForProject->public_id,
                    'title' => $submission->callForProject->title,
                    'slug' => $submission->callForProject->slug,
                    'public_status_code' => $submission->callForProject->public_status_code,
                ] : null,
            ])->values(),
        ]);
    }
}
