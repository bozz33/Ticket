<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\TenantAvatarUpdateRequest;
use App\Http\Requests\Api\V1\Auth\TenantForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\TenantLoginRequest;
use App\Http\Requests\Api\V1\Auth\TenantPasswordUpdateRequest;
use App\Http\Requests\Api\V1\Auth\TenantProfileUpdateRequest;
use App\Http\Requests\Api\V1\Auth\TenantRegisterRequest;
use App\Http\Requests\Api\V1\Auth\TenantResetPasswordRequest;
use App\Models\User;
use App\Notifications\BuyerAccountActivityNotification;
use App\Services\Auth\TenantTokenService;
use App\Support\Buyers\BuyerAccountReadiness;
use App\Support\Tenancy\TenantContext;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class TenantAuthController extends Controller
{
    public function __construct(
        private readonly TenantTokenService $tokenService,
        private readonly TenantContext $tenantContext,
        private readonly BuyerAccountReadiness $buyerAccountReadiness,
    ) {}

    public function login(TenantLoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $email = Str::lower($validated['email']);

        $user = User::query()
            ->where('email', $email)
            ->where('is_active', true)
            ->first();

        if ($user === null || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Identifiants incorrects.'],
            ]);
        }

        $token = $this->tokenService->createToken(
            $user,
            $validated['token_name'] ?? 'api',
            ['*'],
        );

        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        return response()->json([
            'token' => $token,
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'user' => $this->serializeUser($user),
        ]);
    }

    public function register(TenantRegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $email = Str::lower($validated['email']);

        if (User::withTrashed()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['Cette adresse e-mail possède déjà un compte.'],
            ]);
        }

        $user = User::query()->create([
            'name' => $validated['name'],
            'username' => Str::lower(Str::before($email, '@')).'_'.Str::random(4),
            'email' => $email,
            'password' => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        $user->sendEmailVerificationNotification();

        $token = $this->tokenService->createToken(
            $user,
            $validated['token_name'] ?? 'panel_acheteur',
            ['*'],
        );

        return response()->json([
            'token' => $token,
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'user' => $this->serializeUser($user),
        ], 201);
    }

    public function forgotPassword(TenantForgotPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $status = Password::broker('users')->sendResetLink([
            'email' => Str::lower($validated['email']),
        ]);

        if ($status !== Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => Lang::get($status),
            ], 422);
        }

        return response()->json([
            'message' => 'Lien de reinitialisation envoye.',
        ]);
    }

    public function resetPassword(TenantResetPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $status = Password::broker('users')->reset(
            [
                'email' => Str::lower($validated['email']),
                'password' => $validated['password'],
                'password_confirmation' => $validated['password_confirmation'],
                'token' => $validated['token'],
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'message' => Lang::get($status),
            ], 422);
        }

        return response()->json([
            'message' => 'Mot de passe reinitialise avec succes.',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $apiToken = $request->attributes->get('tenant_api_token');

        if ($apiToken !== null) {
            $this->tokenService->revokeToken($apiToken);
        }

        return response()->json(['message' => 'Déconnecté avec succès.']);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->serializeUser($user),
        ]);
    }

    public function updateMe(TenantProfileUpdateRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');

        $validated = $request->validated();

        $nextEmail = Str::lower($validated['email']);
        $emailChanged = $nextEmail !== Str::lower((string) $user->email);

        if ($emailChanged && User::withTrashed()->where('email', $nextEmail)->whereKeyNot($user->getKey())->exists()) {
            throw ValidationException::withMessages([
                'email' => ['Cette adresse e-mail possède déjà un compte.'],
            ]);
        }

        $user->forceFill([
            'name' => trim($validated['name']),
            'first_name' => filled($validated['first_name'] ?? null) ? trim((string) $validated['first_name']) : null,
            'last_name' => filled($validated['last_name'] ?? null) ? trim((string) $validated['last_name']) : null,
            'email' => $nextEmail,
            'phone' => filled($validated['phone'] ?? null) ? trim((string) $validated['phone']) : null,
            'locale' => filled($validated['locale'] ?? null) ? trim((string) $validated['locale']) : null,
            'timezone' => filled($validated['timezone'] ?? null) ? trim((string) $validated['timezone']) : null,
            'email_verified_at' => $emailChanged ? null : $user->email_verified_at,
        ])->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        $this->notifyBuyer($user, new BuyerAccountActivityNotification(
            'Profil mis à jour',
            'Vos informations personnelles ont été mises à jour avec succès.',
            '/compte/profil',
            'user'
        ));

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->serializeUser($user->fresh()),
            'message' => 'Profil mis à jour avec succès.',
        ]);
    }

    public function sendVerificationNotification(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Adresse e-mail déjà vérifiée.',
                'data' => $this->serializeUser($user),
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Lien de vérification envoyé.',
            'data' => $this->serializeUser($user),
        ]);
    }

    public function verifyEmail(Request $request, string $tenant, string $id, string $hash): RedirectResponse|JsonResponse
    {
        /** @var User|null $user */
        $user = User::query()->find($id);

        abort_if($user === null, 404);

        if (! hash_equals($hash, sha1((string) $user->getEmailForVerification()))) {
            abort(403);
        }

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        $baseUrl = rtrim((string) config('ticket.public_frontend_url', config('app.url')), '/');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Adresse e-mail vérifiée.',
                'data' => $this->serializeUser($user->fresh()),
            ]);
        }

        return redirect()->away(sprintf('%s/compte/connexion?verified=success', $baseUrl));
    }

    public function updatePassword(TenantPasswordUpdateRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');

        $validated = $request->validated();

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ])->save();

        $this->notifyBuyer($user, new BuyerAccountActivityNotification(
            'Mot de passe modifié',
            'Le mot de passe de votre compte acheteur a été modifié.',
            '/compte/profil',
            'lock'
        ));

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->serializeUser($user->fresh()),
            'message' => 'Mot de passe mis à jour avec succès.',
        ]);
    }

    public function updateAvatar(TenantAvatarUpdateRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');

        $validated = $request->validated();

        if (filled($user->avatar_path)) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $avatarFile = $validated['avatar'];
        $filename = sprintf('avatar-%s.%s', now()->format('YmdHis'), $avatarFile->getClientOriginalExtension());
        $path = $avatarFile->storeAs(sprintf('buyer/avatars/%s', $user->getKey()), $filename, 'public');

        $user->forceFill([
            'avatar_path' => $path,
        ])->save();

        $this->notifyBuyer($user, new BuyerAccountActivityNotification(
            'Photo de profil mise à jour',
            'Votre photo de profil a été remplacée.',
            '/compte/profil',
            'camera'
        ));

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $this->serializeUser($user->fresh()),
            'message' => 'Photo de profil mise à jour avec succès.',
        ]);
    }

    public function avatar(Request $request): Response
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');

        if (! filled($user->avatar_path) || ! Storage::disk('public')->exists($user->avatar_path)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($user->avatar_path), [
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    public function notifications(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');

        if (! $this->notificationsTableExists()) {
            return response()->json([
                'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
                'data' => [],
                'meta' => [
                    'unread_count' => 0,
                ],
            ]);
        }

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $user->notifications()
                ->latest()
                ->limit(20)
                ->get()
                ->map(fn (DatabaseNotification $notification): array => $this->serializeNotification($notification))
                ->values()
                ->all(),
            'meta' => [
                'unread_count' => $user->unreadNotifications()->count(),
            ],
        ]);
    }

    public function markNotificationAsRead(Request $request, string $notification): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');

        if (! $this->notificationsTableExists()) {
            return response()->json([
                'message' => 'Notification introuvable.',
            ], 404);
        }

        $record = $user->notifications()
            ->whereKey($notification)
            ->first();

        if ($record === null) {
            return response()->json([
                'message' => 'Notification introuvable.',
            ], 404);
        }

        if ($record->read_at === null) {
            $record->markAsRead();
        }

        return response()->json([
            'data' => $this->serializeNotification($record->fresh()),
            'meta' => [
                'unread_count' => $user->fresh()->unreadNotifications()->count(),
            ],
        ]);
    }

    public function markAllNotificationsAsRead(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('tenant_user');

        if (! $this->notificationsTableExists()) {
            return response()->json([
                'message' => 'Toutes les notifications ont été marquées comme lues.',
                'meta' => [
                    'unread_count' => 0,
                ],
            ]);
        }

        $user->unreadNotifications()->update(['read_at' => now()]);

        return response()->json([
            'message' => 'Toutes les notifications ont été marquées comme lues.',
            'meta' => [
                'unread_count' => 0,
            ],
        ]);
    }

    private function serializeUser(User $user): array
    {
        $readiness = $this->buyerAccountReadiness->summary($user);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'username' => $user->username,
            'email' => $user->email,
            'email_verified' => $user->hasVerifiedEmail(),
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'phone' => $user->phone,
            'locale' => $user->locale,
            'timezone' => $user->timezone,
            'avatar_url' => filled($user->avatar_path) ? Storage::disk('public')->url($user->avatar_path) : null,
            'last_login_at' => $user->last_login_at?->toIso8601String(),
            'unread_notifications_count' => $this->unreadNotificationsCount($user),
            'profile_completed' => $readiness['profile_completed'],
            'missing_profile_fields' => $readiness['missing_profile_fields'],
            'account_ready_for_actions' => $readiness['ready_for_sensitive_actions'],
        ];
    }

    private function serializeNotification(DatabaseNotification $notification): array
    {
        return [
            'id' => $notification->getKey(),
            'title' => (string) data_get($notification->data, 'title', 'Notification'),
            'body' => (string) data_get($notification->data, 'body', ''),
            'icon' => (string) data_get($notification->data, 'icon', 'bell'),
            'action_url' => data_get($notification->data, 'action_url'),
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }

    private function notifyBuyer(User $user, BuyerAccountActivityNotification $notification): void
    {
        if (! $this->notificationsTableExists()) {
            return;
        }

        $user->notify($notification);
    }

    private function unreadNotificationsCount(User $user): int
    {
        if (! $this->notificationsTableExists()) {
            return 0;
        }

        return $user->unreadNotifications()->count();
    }

    private function notificationsTableExists(): bool
    {
        return Schema::connection('tenant')->hasTable('notifications');
    }
}
