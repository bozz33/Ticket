<?php

namespace App\Notifications;

use App\Models\Order;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BuyerRefundRequestTenantNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly array $requestData,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $buyerLabel = $this->order->buyer_name ?: $this->order->buyer_email ?: 'Un acheteur';
        $reason = (string) ($this->requestData['reason'] ?? $this->requestData['reason_code'] ?? 'Demande acheteur');

        return FilamentNotification::make()
            ->title('Demande de remboursement à valider')
            ->body(sprintf(
                '%s a demandé un remboursement pour la commande %s (%s). Validez ou rejetez la demande depuis le panel organisateur.',
                $buyerLabel,
                $this->order->reference,
                $reason,
            ))
            ->icon('heroicon-o-arrow-uturn-left')
            ->getDatabaseMessage();
    }
}
