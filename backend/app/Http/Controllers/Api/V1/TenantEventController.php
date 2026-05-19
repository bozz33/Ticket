<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreEventRequest;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Ticket\Ticketing\Contracts\EventCatalog;

class TenantEventController extends Controller
{
    public function index(Request $request, TenantContext $tenantContext, EventCatalog $eventCatalog): JsonResponse
    {
        return response()->json([
            'tenant' => $tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $eventCatalog->list($request->query('status')),
        ]);
    }

    public function store(StoreEventRequest $request, TenantContext $tenantContext, EventCatalog $eventCatalog): JsonResponse
    {
        return response()->json([
            'tenant' => $tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $eventCatalog->create($request->validated()),
        ], 201);
    }

    public function show(string $event, TenantContext $tenantContext, EventCatalog $eventCatalog): JsonResponse
    {
        $record = $eventCatalog->findByIdentifier($event);

        abort_if($record === null, 404);

        return response()->json([
            'tenant' => $tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $record,
        ]);
    }
}
