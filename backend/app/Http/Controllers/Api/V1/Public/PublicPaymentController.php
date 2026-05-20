<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Exceptions\BuyerAccountActionBlockedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Public\PublicPaymentInitializeRequest;
use App\Models\User;
use App\Services\Auth\TenantTokenService;
use App\Support\Buyers\BuyerAccountReadiness;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;
use Ticket\Payments\Contracts\CheckoutManager;

class PublicPaymentController extends Controller
{
    private const OPTIONS_TTL = 45;

    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly CheckoutManager $checkoutManager,
        private readonly TenantTokenService $tenantTokenService,
        private readonly BuyerAccountReadiness $buyerAccountReadiness,
    ) {}

    public function options(Request $request, string $tenant): JsonResponse
    {
        $tenantModel = $this->tenantContext->get();

        if ($tenantModel === null) {
            return response()->json(['message' => 'Tenant introuvable.'], 404);
        }

        $ticket = trim((string) $request->query('ticket', ''));
        $offer = trim((string) $request->query('offer', ''));
        $checkoutItemIdentifier = $ticket !== '' ? $ticket : $offer;
        $checkoutItemType = $ticket !== '' ? 'event_ticket' : ($offer !== '' ? 'offer' : null);
        $paymentMethod = (string) $request->query('payment_method', '');

        if ($checkoutItemIdentifier === '') {
            return response()->json(['message' => 'Offre ou ticket requis.'], 422);
        }

        try {
            $data = $tenantModel->run(fn () => $this->checkoutManager->options(
                $tenantModel,
                $checkoutItemIdentifier,
                max(1, (int) $request->query('quantity', 1)),
                $paymentMethod !== '' ? $paymentMethod : null,
                $checkoutItemType,
            ));

            return response()->json([
                'tenant' => $tenantModel->only(['id', 'public_id', 'name', 'slug']),
                'data' => $data,
            ], 200, [
                'Cache-Control' => sprintf('public, max-age=0, s-maxage=%d, stale-while-revalidate=%d', self::OPTIONS_TTL, self::OPTIONS_TTL),
            ]);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function initialize(PublicPaymentInitializeRequest $request, string $tenant): JsonResponse
    {
        $tenantModel = $this->tenantContext->get();

        if ($tenantModel === null) {
            return response()->json(['message' => 'Tenant introuvable.'], 404);
        }

        $validated = $request->validated();

        $buyer = $this->resolveAuthenticatedBuyer($request);

        if ($buyer === null) {
            return response()->json([
                'message' => 'Connexion acheteur requise pour réserver ou acheter.',
                'code' => 'AUTH_REQUIRED',
            ], 401);
        }

        $validated['buyer_user_id'] = $buyer->getKey();
        $validated['buyer_name'] = $buyer->name;
        $validated['buyer_email'] = Str::lower($buyer->email);
        $validated['buyer_phone'] = $buyer->phone;

        try {
            $this->buyerAccountReadiness->assertReadyForSensitiveAction($buyer, 'acheter ou réserver');
            $data = $tenantModel->run(fn () => $this->checkoutManager->initialize($tenantModel, $validated));

            return response()->json([
                'tenant' => $tenantModel->only(['id', 'public_id', 'name', 'slug']),
                'data' => $data,
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

    public function verify(Request $request, string $tenant, string $reference): JsonResponse
    {
        $tenantModel = $this->tenantContext->get();

        if ($tenantModel === null) {
            return response()->json(['message' => 'Tenant introuvable.'], 404);
        }

        try {
            $data = $this->checkoutManager->verify($tenantModel, $reference);

            return response()->json([
                'tenant' => $tenantModel->only(['id', 'public_id', 'name', 'slug']),
                'data' => $data,
            ]);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}
