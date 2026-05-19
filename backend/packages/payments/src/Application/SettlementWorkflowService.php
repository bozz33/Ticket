<?php

namespace Ticket\Payments\Application;

use App\Models\PlatformUser;
use App\Models\Settlement;
use App\Notifications\SettlementRequestedPlatformNotification;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Ticket\Notifications\Contracts\DomainEventPublisher;
use Ticket\Notifications\Contracts\NotificationDispatcher;

class SettlementWorkflowService
{
    public function __construct(
        private readonly NotificationDispatcher $notifications,
        private readonly DomainEventPublisher $domainEvents,
    ) {}

    public function notifyPlatformOfRequest(Settlement $settlement): void
    {
        $settlement->loadMissing('tenant');

        PlatformUser::query()
            ->where(function ($query): void {
                $query->where('is_super_admin', true)
                    ->orWhereHas('roles', fn ($roles) => $roles->where('name', 'super-admin'));
            })
            ->get()
            ->each(function (PlatformUser $user) use ($settlement): void {
                $this->notifications->send($user, new SettlementRequestedPlatformNotification($settlement));
            });

        $this->domainEvents->publish(
            'payments.settlement.requested',
            [
                'settlement_id' => $settlement->getKey(),
                'tenant_id' => $settlement->tenant_id,
                'status' => $settlement->status,
            ],
            Settlement::class,
            (string) $settlement->getKey(),
            ['module' => 'payments'],
        );
    }

    public function approve(Settlement $settlement, PlatformUser $actor): Settlement
    {
        $approved = DB::connection('central')->transaction(function () use ($settlement, $actor): Settlement {
            $settlement->refresh();

            if (! in_array((string) $settlement->status, ['pending', 'under_review'], true)) {
                throw new \RuntimeException('Seules les demandes en attente peuvent être approuvées.');
            }

            $meta = array_merge((array) ($settlement->meta ?? []), [
                'review' => [
                    'decision' => 'approved',
                    'reviewed_by' => [
                        'id' => $actor->getKey(),
                        'name' => $actor->name,
                        'email' => $actor->email,
                    ],
                    'reviewed_at' => now()->toIso8601String(),
                    'rejection_reason' => null,
                ],
            ]);

            $settlement->forceFill([
                'status' => 'approved',
                'meta' => $meta,
            ])->save();

            return $settlement->fresh(['tenant']);
        });

        $this->domainEvents->publish(
            'payments.settlement.approved',
            [
                'settlement_id' => $approved->getKey(),
                'tenant_id' => $approved->tenant_id,
                'actor_id' => $actor->getKey(),
            ],
            Settlement::class,
            (string) $approved->getKey(),
            ['module' => 'payments'],
        );

        return $approved;
    }

    public function reject(Settlement $settlement, PlatformUser $actor, string $reason): Settlement
    {
        $rejected = DB::connection('central')->transaction(function () use ($settlement, $actor, $reason): Settlement {
            $settlement->refresh();

            if (! in_array((string) $settlement->status, ['pending', 'under_review'], true)) {
                throw new \RuntimeException('Seules les demandes en attente peuvent être rejetées.');
            }

            $meta = array_merge((array) ($settlement->meta ?? []), [
                'review' => [
                    'decision' => 'rejected',
                    'reviewed_by' => [
                        'id' => $actor->getKey(),
                        'name' => $actor->name,
                        'email' => $actor->email,
                    ],
                    'reviewed_at' => now()->toIso8601String(),
                    'rejection_reason' => $reason,
                ],
            ]);

            $settlement->forceFill([
                'status' => 'rejected',
                'scheduled_at' => null,
                'meta' => $meta,
            ])->save();

            return $settlement->fresh(['tenant']);
        });

        $this->domainEvents->publish(
            'payments.settlement.rejected',
            [
                'settlement_id' => $rejected->getKey(),
                'tenant_id' => $rejected->tenant_id,
                'actor_id' => $actor->getKey(),
                'reason' => $reason,
            ],
            Settlement::class,
            (string) $rejected->getKey(),
            ['module' => 'payments'],
        );

        return $rejected;
    }

    public function reviewSummary(Settlement $settlement): string
    {
        $review = (array) data_get($settlement->meta, 'review', []);
        $decision = Arr::get($review, 'decision');
        $reviewer = Arr::get($review, 'reviewed_by.name');
        $reviewedAt = Arr::get($review, 'reviewed_at');

        if (! $decision || ! $reviewer || ! $reviewedAt) {
            return 'En attente de revue super-admin';
        }

        return sprintf('%s par %s le %s', $decision, $reviewer, date('d/m/Y H:i', strtotime((string) $reviewedAt)));
    }

    public function pendingCount(): int
    {
        return Settlement::query()
            ->whereIn('status', ['pending', 'under_review'])
            ->count();
    }

    public function makePlatformDatabaseNotification(Settlement $settlement): array
    {
        return FilamentNotification::make()
            ->title('Nouvelle demande de reversement')
            ->body(sprintf('Demande %s en attente.', $settlement->reference))
            ->icon('heroicon-o-building-library')
            ->getDatabaseMessage();
    }
}
