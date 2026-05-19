<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Ticket\Ticketing\Contracts\DocumentCatalog;

class PublicTenantDocumentController extends Controller
{
    public function index(Request $request, TenantContext $tenantContext, DocumentCatalog $documentCatalog): JsonResponse
    {
        return response()->json([
            'tenant' => $tenantContext->get()?->only(['public_id', 'name', 'slug']),
            'data' => $documentCatalog->listPublic($request->query('resource_type_code')),
        ]);
    }

    public function show(string $document, TenantContext $tenantContext, DocumentCatalog $documentCatalog): JsonResponse
    {
        $record = $documentCatalog->findPublicByIdentifier($document);

        abort_if($record === null, 404);

        return response()->json([
            'tenant' => $tenantContext->get()?->only(['public_id', 'name', 'slug']),
            'data' => $record,
        ]);
    }
}
