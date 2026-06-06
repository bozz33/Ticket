<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Mobile\StoreMobileSupportRequest;
use App\Models\PlatformSupportTicket;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MobileSupportRequestController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');
        $tenant = $this->tenantContext->get();

        $tickets = PlatformSupportTicket::query()
            ->where('tenant_id', $tenant?->getKey())
            ->where('requester_email', $user->email)
            ->latest('last_activity_at')
            ->limit(max(1, min(50, (int) $request->query('limit', 20))))
            ->get();

        return response()->json([
            'tenant' => $tenant?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $tickets->map(fn (PlatformSupportTicket $ticket): array => $this->serializeTicket($ticket))->values(),
        ]);
    }

    public function store(StoreMobileSupportRequest $formRequest): JsonResponse
    {
        /** @var User $user */
        $user = $formRequest->attributes->get('tenant_user');
        $tenant = $this->tenantContext->get();

        $validated = $formRequest->validated();

        $ticket = PlatformSupportTicket::query()->create([
            'tenant_id' => $tenant instanceof Tenant ? $tenant->getKey() : null,
            'reference' => $this->makeReference(),
            'subject' => $validated['subject'],
            'requester_name' => $user->name,
            'requester_email' => $user->email,
            'status' => 'open',
            'priority' => $validated['priority'] ?? 'normal',
            'category' => $validated['category'] ?? 'other',
            'opened_at' => now(),
            'last_activity_at' => now(),
            'meta' => [
                'source' => 'mobile',
                'message' => $validated['message'],
                'context' => $validated['context'] ?? [],
                'tenant_user_id' => $user->getKey(),
            ],
        ]);

        return response()->json([
            'tenant' => $tenant?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->serializeTicket($ticket),
            'message' => 'Demande support envoyée.',
        ], 201);
    }

    public function show(Request $request, string $tenant, string $ticket): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');
        $currentTenant = $this->tenantContext->get();

        $record = PlatformSupportTicket::query()
            ->where('tenant_id', $currentTenant?->getKey())
            ->where('requester_email', $user->email)
            ->where(function ($query) use ($ticket): void {
                $query->where('reference', $ticket)
                    ->orWhere('id', is_numeric($ticket) ? (int) $ticket : 0);
            })
            ->firstOrFail();

        return response()->json([
            'tenant' => $currentTenant?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->serializeTicket($record, true),
        ]);
    }

    private function serializeTicket(PlatformSupportTicket $ticket, bool $withMessage = false): array
    {
        $payload = [
            'id' => $ticket->id,
            'reference' => $ticket->reference,
            'subject' => $ticket->subject,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'category' => $ticket->category,
            'opened_at' => $ticket->opened_at?->toISOString(),
            'last_activity_at' => $ticket->last_activity_at?->toISOString(),
            'resolved_at' => $ticket->resolved_at?->toISOString(),
        ];

        if ($withMessage) {
            $payload['message'] = data_get($ticket->meta ?? [], 'message');
            $payload['context'] = data_get($ticket->meta ?? [], 'context', []);
        }

        return $payload;
    }

    private function makeReference(): string
    {
        do {
            $reference = 'SUP-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
        } while (PlatformSupportTicket::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
