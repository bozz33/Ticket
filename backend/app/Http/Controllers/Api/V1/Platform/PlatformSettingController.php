<?php

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Platform\UpsertPlatformSettingsRequest;
use App\Services\PlatformSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlatformSettingController extends Controller
{
    public function index(Request $request, PlatformSettingsService $platformSettingsService): JsonResponse
    {
        $group = $request->query('group');
        $publicOnly = $request->has('public') ? $request->boolean('public') : null;

        return response()->json([
            'data' => $platformSettingsService->list($group, $publicOnly),
        ]);
    }

    public function upsert(UpsertPlatformSettingsRequest $request, PlatformSettingsService $platformSettingsService): JsonResponse
    {
        return response()->json([
            'data' => $platformSettingsService->upsertMany($request->validated('items')),
        ]);
    }
}
