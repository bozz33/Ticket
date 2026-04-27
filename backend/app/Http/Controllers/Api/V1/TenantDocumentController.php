<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDocumentRequest;
use App\Services\Tenancy\DocumentService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantDocumentController extends Controller
{
    public function index(Request $request, TenantContext $tenantContext, DocumentService $documentService): JsonResponse
    {
        return response()->json([
            'tenant' => $tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $documentService->list($request->query('visibility'), $request->query('resource_type_code')),
        ]);
    }

    public function store(StoreDocumentRequest $request, TenantContext $tenantContext, DocumentService $documentService): JsonResponse
    {
        return response()->json([
            'tenant' => $tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $documentService->create($request->validated()),
        ], 201);
    }

    public function show(string $document, TenantContext $tenantContext, DocumentService $documentService): JsonResponse
    {
        $record = $documentService->findByIdentifier($document);

        abort_if($record === null, 404);

        return response()->json([
            'tenant' => $tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $record,
        ]);
    }
}
