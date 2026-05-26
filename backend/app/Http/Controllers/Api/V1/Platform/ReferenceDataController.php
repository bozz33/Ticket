<?php

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Language;
use App\Models\PaymentMethodType;
use App\Models\PublicStatus;
use App\Models\ResourceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Ticket\ReferenceData\Contracts\CityReferenceSearch;

class ReferenceDataController extends Controller
{
    public function __construct(
        private readonly CityReferenceSearch $cityReferenceSearchService,
    ) {}

    public function countries(): JsonResponse
    {
        return response()->json([
            'data' => Country::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function currencies(): JsonResponse
    {
        return response()->json([
            'data' => Currency::query()->orderBy('sort_order')->orderBy('code')->get(),
        ]);
    }

    public function languages(): JsonResponse
    {
        return response()->json([
            'data' => Language::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function publicStatuses(): JsonResponse
    {
        return response()->json([
            'data' => PublicStatus::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function resourceTypes(): JsonResponse
    {
        return response()->json([
            'data' => ResourceType::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function paymentMethodTypes(): JsonResponse
    {
        return response()->json([
            'data' => PaymentMethodType::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function cities(Request $request): JsonResponse
    {
        $country = $request->query('country');
        $query = $request->query('q');
        $limit = (int) ($request->query('limit', 50));

        return response()->json([
            'data' => $this->cityReferenceSearchService->search(
                countryCode: is_string($country) ? $country : null,
                query: is_string($query) ? $query : null,
                limit: $limit,
                onlyActive: false,
            ),
        ]);
    }
}
