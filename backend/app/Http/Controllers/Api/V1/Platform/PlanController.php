<?php

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Platform\AssignTenantPlanRequest;
use App\Http\Requests\Api\V1\Platform\StorePlanRequest;
use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    public function index(): JsonResponse
    {
        $plans = Plan::query()
            ->withCount('tenantSubscriptions')
            ->orderBy('price_amount')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $plans,
        ]);
    }

    public function store(StorePlanRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $plan = Plan::query()->create([
            'public_id' => (string) Str::uuid(),
            'code' => Str::lower($payload['code']),
            'name' => $payload['name'],
            'description' => $payload['description'] ?? null,
            'price_amount' => $payload['price_amount'] ?? 0,
            'currency_code' => Str::upper($payload['currency_code'] ?? 'XOF'),
            'billing_interval' => $payload['billing_interval'] ?? 'monthly',
            'trial_days' => $payload['trial_days'] ?? 0,
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'meta' => $payload['meta'] ?? [],
        ]);

        return response()->json([
            'data' => $plan->loadCount('tenantSubscriptions'),
        ], 201);
    }

    public function show(Plan $plan): JsonResponse
    {
        return response()->json([
            'data' => $plan->loadCount('tenantSubscriptions'),
        ]);
    }

    public function assignToTenant(Tenant $tenant, AssignTenantPlanRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $subscription = DB::connection(config('ticket.central_connection'))->transaction(function () use ($tenant, $payload) {
            $tenant->subscriptions()
                ->whereIn('status', ['active', 'trialing'])
                ->update([
                    'status' => 'replaced',
                    'cancelled_at' => now(),
                ]);

            return $tenant->subscriptions()->create([
                'plan_id' => $payload['plan_id'],
                'status' => $payload['status'] ?? 'active',
                'started_at' => $payload['started_at'] ?? now(),
                'ends_at' => $payload['ends_at'] ?? null,
                'trial_ends_at' => $payload['trial_ends_at'] ?? null,
                'cancelled_at' => $payload['cancelled_at'] ?? null,
                'meta' => $payload['meta'] ?? [],
            ]);
        });

        return response()->json([
            'data' => $subscription->load(['tenant', 'plan']),
        ], 201);
    }
}
