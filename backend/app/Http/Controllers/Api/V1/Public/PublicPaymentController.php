<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Exceptions\BuyerAccountActionBlockedException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\TenantTokenService;
use App\Services\Payments\PublicPaymentService;
use App\Support\Buyers\BuyerAccountReadiness;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

class PublicPaymentController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly PublicPaymentService $publicPaymentService,
        private readonly TenantTokenService $tenantTokenService,
        private readonly BuyerAccountReadiness $buyerAccountReadiness,
    ) {}

    public function options(Request $request, string $tenant): JsonResponse
    {
        $tenantModel = $this->tenantContext->get();

        if ($tenantModel === null) {
            return response()->json(['message' => 'Tenant introuvable.'], 404);
        }

        $offer = (string) $request->query('offer', '');

        if ($offer === '') {
            return response()->json(['message' => 'Offre requise.'], 422);
        }

        try {
            $data = $tenantModel->run(fn () => $this->publicPaymentService->options(
                $tenantModel,
                $offer,
                max(1, (int) $request->query('quantity', 1)),
            ));

            return response()->json([
                'tenant' => $tenantModel->only(['id', 'public_id', 'name', 'slug']),
                'data' => $data,
            ]);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function initialize(Request $request, string $tenant): JsonResponse
    {
        $tenantModel = $this->tenantContext->get();

        if ($tenantModel === null) {
            return response()->json(['message' => 'Tenant introuvable.'], 404);
        }

        $validated = $request->validate([
            'offer' => ['required', 'string'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'payment_method' => ['nullable', 'string', 'max:80'],
            'buyer_name' => ['nullable', 'string', 'max:255'],
            'buyer_email' => ['nullable', 'email', 'max:255'],
            'buyer_phone' => ['nullable', 'string', 'max:50'],
            'content_module' => ['nullable', 'string', 'max:100'],
            'content_slug' => ['nullable', 'string', 'max:255'],
            'callback_url' => ['required', 'url', 'max:2048'],
        ]);

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
            $data = $tenantModel->run(fn () => $this->publicPaymentService->initialize($tenantModel, $validated));

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
            $data = $this->publicPaymentService->verify($tenantModel, $reference);

            return response()->json([
                'tenant' => $tenantModel->only(['id', 'public_id', 'name', 'slug']),
                'data' => $data,
            ]);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}
