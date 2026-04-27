<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Tenancy\DocumentService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicTenantDocumentController extends Controller
{
    public function index(Request $request, TenantContext $tenantContext, DocumentService $documentService): JsonResponse
    {
        return response()->json([
            'tenant' => $tenantContext->get()?->only(['public_id', 'name', 'slug']),
            'data' => $documentService->listPublic($request->query('resource_type_code')),
        ]);
    }

    public function show(string $document, TenantContext $tenantContext, DocumentService $documentService): JsonResponse
    {
        $record = $documentService->findPublicByIdentifier($document);

        abort_if($record === null, 404);

        return response()->json([
            'tenant' => $tenantContext->get()?->only(['public_id', 'name', 'slug']),
            'data' => $record,
        ]);
    }
}
