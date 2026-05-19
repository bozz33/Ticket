<?php

namespace App\Filament\Platform\Resources\Refunds\Pages;

use App\Filament\Platform\Resources\Refunds\RefundResource;
use App\Filament\Support\Pages\CreateRecordPage;
use App\Models\PlatformTransaction;
use App\Models\PlatformUser;
use App\Models\Refund;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Ticket\Payments\Contracts\RefundManager;

class CreateRefund extends CreateRecordPage
{
    protected static string $resource = RefundResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function handleRecordCreation(array $data): Refund
    {
        $transaction = PlatformTransaction::query()->findOrFail((int) $data['platform_transaction_id']);

        /** @var PlatformUser|null $actor */
        $actor = Filament::auth()->user();

        return app(RefundManager::class)->create($transaction, [
            'reason_code' => $data['reason_code'] ?? 'manual_refund',
            'reason' => $data['reason'] ?? '',
        ], $actor);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Remboursement lancé')
            ->body('Le remboursement a été créé et la transaction a été synchronisée avec le ledger.');
    }
}
