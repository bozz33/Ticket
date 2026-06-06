<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Mobile\StoreMobileDeviceRequest;
use App\Models\MobileDevice;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileDeviceController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function store(StoreMobileDeviceRequest $formRequest): JsonResponse
    {
        /** @var User $user */
        $user = $formRequest->attributes->get('tenant_user');

        $validated = $formRequest->validated();

        $device = MobileDevice::query()->updateOrCreate(
            [
                'user_id' => $user->getKey(),
                'device_id' => $validated['device_id'],
            ],
            [
                'platform' => $validated['platform'],
                'push_provider' => $validated['push_provider'] ?? 'expo',
                'push_token' => $validated['push_token'],
                'app_version' => $validated['app_version'] ?? null,
                'device_name' => $validated['device_name'] ?? null,
                'locale' => $validated['locale'] ?? null,
                'timezone' => $validated['timezone'] ?? null,
                'last_seen_at' => now(),
                'revoked_at' => null,
                'meta' => $validated['meta'] ?? [],
            ]
        );

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->serializeDevice($device),
            'message' => 'Appareil mobile enregistré.',
        ], $device->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request, string $tenant, string $device): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');

        $record = MobileDevice::query()
            ->where('user_id', $user->getKey())
            ->where(function ($query) use ($device): void {
                $query->where('id', is_numeric($device) ? (int) $device : 0)
                    ->orWhere('device_id', $device);
            })
            ->firstOrFail();

        $record->forceFill(['revoked_at' => now()])->save();

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->serializeDevice($record),
            'message' => 'Appareil mobile désactivé.',
        ]);
    }

    private function serializeDevice(MobileDevice $device): array
    {
        return [
            'id' => $device->id,
            'device_id' => $device->device_id,
            'platform' => $device->platform,
            'push_provider' => $device->push_provider,
            'app_version' => $device->app_version,
            'device_name' => $device->device_name,
            'locale' => $device->locale,
            'timezone' => $device->timezone,
            'last_seen_at' => $device->last_seen_at?->toISOString(),
            'revoked_at' => $device->revoked_at?->toISOString(),
        ];
    }
}
