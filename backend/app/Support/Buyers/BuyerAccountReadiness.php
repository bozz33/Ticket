<?php

namespace App\Support\Buyers;

use App\Exceptions\BuyerAccountActionBlockedException;
use App\Models\User;

class BuyerAccountReadiness
{
    public function summary(User $user): array
    {
        $profileChecks = [
            'name' => filled(trim((string) $user->name)),
            'first_name' => filled(trim((string) $user->first_name)),
            'last_name' => filled(trim((string) $user->last_name)),
            'phone' => filled(trim((string) $user->phone)),
        ];

        $emailVerified = $user->hasVerifiedEmail();
        $profileCompleted = ! in_array(false, $profileChecks, true);

        return [
            'email_verified' => $emailVerified,
            'profile_completed' => $profileCompleted,
            'missing_profile_fields' => array_keys(array_filter($profileChecks, static fn (bool $isFilled): bool => $isFilled === false)),
            'ready_for_sensitive_actions' => $emailVerified && $profileCompleted,
        ];
    }

    public function assertReadyForSensitiveAction(User $user, string $actionLabel = 'effectuer cette action'): void
    {
        $summary = $this->summary($user);

        if (($summary['ready_for_sensitive_actions'] ?? false) === true) {
            return;
        }

        $messages = [];

        if (($summary['profile_completed'] ?? false) !== true) {
            $messages[] = 'compléter votre profil';
        }

        if (($summary['email_verified'] ?? false) !== true) {
            $messages[] = 'vérifier votre adresse e-mail';
        }

        throw new BuyerAccountActionBlockedException(
            sprintf(
                'Avant de %s, merci de %s.',
                $actionLabel,
                implode(' et ', $messages),
            ),
            'ACCOUNT_NOT_READY',
            $summary,
        );
    }
}
