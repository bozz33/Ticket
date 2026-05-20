<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Exceptions\BuyerAccountActionBlockedException;
use App\Http\Controllers\Controller;
use App\Models\TicketReservation;
use App\Models\User;
use App\Services\Auth\TenantTokenService;
use App\Support\Buyers\BuyerAccountReadiness;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;
use Ticket\Payments\Contracts\CheckoutItemResolver;
use Ticket\Payments\Domain\CheckoutReservation;

class PublicTicketReservationController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly CheckoutItemResolver $checkoutItems,
        private readonly TenantTokenService $tenantTokenService,
        private readonly BuyerAccountReadiness $buyerAccountReadiness,
    ) {}

    public function store(Request $request, string $tenant): JsonResponse
    {
        $tenantModel = $this->tenantContext->get();

        if ($tenantModel === null) {
            return response()->json(['message' => 'Tenant introuvable.'], 404);
        }

        $buyer = $this->resolveAuthenticatedBuyer($request);

        if ($buyer === null) {
            return response()->json([
                'message' => 'Connexion acheteur requise pour réserver.',
                'code' => 'AUTH_REQUIRED',
            ], 401);
        }

        $validated = $request->validate([
            'ticket' => ['required', 'string', 'max:80'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $this->buyerAccountReadiness->assertReadyForSensitiveAction($buyer, 'réserver');

            $reservation = $tenantModel->run(function () use ($validated, $buyer): CheckoutReservation {
                $item = $this->checkoutItems->resolve((string) $validated['ticket'], 'event_ticket');

                if ($item === null) {
                    throw new RuntimeException('Ticket introuvable.');
                }

                $bounds = $this->checkoutItems->quantityBounds($item);
                $quantity = max(1, (int) ($validated['quantity'] ?? 1));

                if ($quantity < $bounds['min'] || $quantity > $bounds['max']) {
                    throw new RuntimeException('Quantité invalide pour ce ticket.');
                }

                $reservation = $this->checkoutItems->reserve($item, $quantity, [
                    'buyer_user_id' => $buyer->getKey(),
                    'buyer_email' => Str::lower((string) $buyer->email),
                    'reservation_source' => 'public_cart',
                ]);

                if (! $reservation instanceof CheckoutReservation) {
                    throw new RuntimeException('Impossible de réserver ce ticket.');
                }

                return $reservation;
            });

            return response()->json([
                'tenant' => $tenantModel->only(['id', 'public_id', 'name', 'slug']),
                'data' => [
                    'type' => $reservation->type,
                    'id' => $reservation->publicId,
                    'quantity' => $reservation->quantity,
                    'expires_at' => $reservation->expiresAt?->toIso8601String(),
                    'metadata' => $reservation->metadata,
                ],
            ], 201);
        } catch (BuyerAccountActionBlockedException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'code' => $exception->errorCode(),
                'requirements' => $exception->requirements(),
            ], 422);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function destroy(Request $request, string $tenant, string $reservation): JsonResponse
    {
        $tenantModel = $this->tenantContext->get();

        if ($tenantModel === null) {
            return response()->json(['message' => 'Tenant introuvable.'], 404);
        }

        $released = $tenantModel->run(fn (): bool => $this->checkoutItems->release([
            'ticket_reservation_public_id' => $reservation,
        ]));

        return response()->json([
            'tenant' => $tenantModel->only(['id', 'public_id', 'name', 'slug']),
            'data' => [
                'id' => $reservation,
                'released' => $released,
            ],
        ]);
    }

    public function show(Request $request, string $tenant, string $reservation): JsonResponse
    {
        $tenantModel = $this->tenantContext->get();

        if ($tenantModel === null) {
            return response()->json(['message' => 'Tenant introuvable.'], 404);
        }

        $ticketReservation = $tenantModel->run(fn () => TicketReservation::query()
            ->with('eventTicket')
            ->where('public_id', $reservation)
            ->first());

        if (! $ticketReservation instanceof TicketReservation) {
            return response()->json(['message' => 'Réservation introuvable.'], 404);
        }

        return response()->json([
            'tenant' => $tenantModel->only(['id', 'public_id', 'name', 'slug']),
            'data' => [
                'id' => $ticketReservation->public_id,
                'ticket' => $ticketReservation->eventTicket?->public_id,
                'quantity' => $ticketReservation->quantity,
                'status' => $ticketReservation->status,
                'expires_at' => $ticketReservation->expires_at?->toIso8601String(),
            ],
        ]);
    }

    private function resolveAuthenticatedBuyer(Request $request): ?User
    {
        $header = (string) $request->header('Authorization', '');

        if (! str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = trim(substr($header, 7));

        if ($token === '') {
            return null;
        }

        $apiToken = $this->tenantTokenService->findToken($token);

        if ($apiToken === null || ! $apiToken->user instanceof User || ! $apiToken->user->is_active) {
            return null;
        }

        $apiToken->touchLastUsed();

        return $apiToken->user;
    }
}
