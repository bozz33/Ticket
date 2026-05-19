<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Tenant;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BuyerRefundRequestPlatformNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Tenant $tenant,
        private readonly Order $order,
        private readonly array $requestData,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(sprintf('Nouvelle demande de remboursement %s', $this->order->reference))
            ->greeting('Bonjour,')
            ->line(sprintf('Un acheteur a demandé un remboursement sur le tenant %s.', $this->tenant->name))
            ->line(sprintf('Commande: %s', $this->order->reference))
            ->line(sprintf('Montant: %s %s', number_format((int) $this->order->total_amount, 0, ',', ' '), $this->order->currency_code));
    }

    public function toArray(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Nouvelle demande de remboursement')
            ->body(sprintf(
                '%s a demandé un remboursement pour la commande %s.',
                $this->order->buyer_name ?: $this->order->buyer_email ?: 'Un acheteur',
                $this->order->reference,
            ))
            ->icon('heroicon-o-arrow-uturn-left')
            ->getDatabaseMessage();
    }
}
