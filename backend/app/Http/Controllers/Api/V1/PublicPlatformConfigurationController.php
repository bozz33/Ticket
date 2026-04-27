<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\FeatureFlagService;
use App\Services\PlatformSettingsService;
use Illuminate\Http\JsonResponse;

class PublicPlatformConfigurationController extends Controller
{
    public function __invoke(
        PlatformSettingsService $platformSettingsService,
        FeatureFlagService $featureFlagService,
    ): JsonResponse {
        return response()->json([
            'settings' => $platformSettingsService->grouped(publicOnly: true),
            'feature_flags' => $featureFlagService->publicFlags(),
        ]);
    }
}
