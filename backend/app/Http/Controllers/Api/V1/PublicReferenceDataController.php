<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Language;
use App\Models\PaymentMethodType;
use App\Models\PublicStatus;
use App\Models\ResourceType;
use App\Support\ReferenceData\CityReferenceSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicReferenceDataController extends Controller
{
    private const REFERENCES_TTL = 3600;

    private const CITIES_TTL = 300;

    public function __construct(
        private readonly CityReferenceSearchService $cityReferenceSearchService,
    ) {}

    public function countries(): JsonResponse
    {
        $payload = Cache::remember('public_references_countries', now()->addSeconds(self::REFERENCES_TTL), fn (): array => [
            'data' => Country::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
        ]);

        return $this->cachedJson($payload, self::REFERENCES_TTL);
    }

    public function currencies(): JsonResponse
    {
        return response()->json([
            'data' => Currency::query()->where('is_active', true)->orderBy('sort_order')->orderBy('code')->get(),
        ]);
    }

    public function languages(): JsonResponse
    {
        return response()->json([
            'data' => Language::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function publicStatuses(): JsonResponse
    {
        return response()->json([
            'data' => PublicStatus::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function resourceTypes(): JsonResponse
    {
        $payload = Cache::remember('public_references_resource_types', now()->addSeconds(self::REFERENCES_TTL), fn (): array => [
            'data' => ResourceType::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
        ]);

        return $this->cachedJson($payload, self::REFERENCES_TTL);
    }

    public function paymentMethodTypes(): JsonResponse
    {
        return response()->json([
            'data' => PaymentMethodType::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function cities(Request $request): JsonResponse
    {
        $country = $request->query('country');
        $query = $request->query('q');
        $limit = (int) ($request->query('limit', 50));

        $payload = Cache::remember(
            sprintf(
                'public_references_cities:%s',
                md5(json_encode([
                    'country' => is_string($country) ? $country : null,
                    'q' => is_string($query) ? $query : null,
                    'limit' => $limit,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            ),
            now()->addSeconds(self::CITIES_TTL),
            fn (): array => [
                'data' => $this->cityReferenceSearchService->search(
                    countryCode: is_string($country) ? $country : null,
                    query: is_string($query) ? $query : null,
                    limit: $limit,
                    onlyActive: true,
                ),
            ],
        );

        return $this->cachedJson($payload, self::CITIES_TTL);
    }

    private function cachedJson(array $payload, int $ttl): JsonResponse
    {
        return response()->json($payload, 200, [
            'Cache-Control' => sprintf('public, max-age=0, s-maxage=%d, stale-while-revalidate=%d', $ttl, $ttl),
        ]);
    }
}
