<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Services\Public\PublicContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicContentController extends Controller
{
    public function __construct(
        private readonly PublicContentService $service,
    ) {}

    public function index(Request $request, string $tenant): JsonResponse
    {
        $filters = $request->only(['module', 'q', 'category', 'city', 'price', 'sort', 'featured']);
        $page    = max(1, (int) $request->query('page', 1));
        $perPage = min(48, max(1, (int) $request->query('per_page', 12)));

        $result  = $this->service->list($filters, $page, $perPage);
        $available = $this->service->availableFilters();

        return response()->json([
            'data'    => $result['items'],
            'meta'    => [
                'current_page' => $result['currentPage'],
                'total'        => $result['total'],
                'total_pages'  => $result['totalPages'],
                'per_page'     => $perPage,
            ],
            'filters' => $available,
        ]);
    }

    public function show(string $tenant, string $module, string $slug): JsonResponse
    {
        $item = $this->service->find($module, $slug);

        if ($item === null) {
            return response()->json(['message' => 'Contenu introuvable.'], 404);
        }

        return response()->json(['data' => $item]);
    }

    public function filters(string $tenant): JsonResponse
    {
        return response()->json(['data' => $this->service->availableFilters()]);
    }
}
