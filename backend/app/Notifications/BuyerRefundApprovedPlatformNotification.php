<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\PlatformTransaction;
use App\Models\Tenant;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BuyerRefundApprovedPlatformNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Tenant $tenant,
        private readonly Order $order,
        private readonly ?PlatformTransaction $transaction = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(sprintf('Remboursement validé par organisateur %s', $this->order->reference))
            ->greeting('Bonjour,')
            ->line(sprintf('%s a validé une demande de remboursement acheteur.', $this->tenant->name))
            ->line(sprintf('Commande : %s', $this->order->reference))
            ->line(sprintf('Transaction : %s', $this->transaction?->transaction_reference ?? $this->order->transaction_reference))
            ->line(sprintf('Montant : %s %s', number_format((int) $this->order->total_amount, 0, ',', ' '), $this->order->currency_code))
            ->action('Traiter le remboursement', url('/platform/refunds/create'))
            ->line('La plateforme doit maintenant créer et synchroniser le remboursement gateway.');
    }

    public function toArray(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Remboursement validé par l’organisateur')
            ->body(sprintf(
                '%s a validé la demande de %s pour la commande %s. Transaction : %s.',
                $this->tenant->name,
                $this->order->buyer_name ?: $this->order->buyer_email ?: 'un acheteur',
                $this->order->reference,
                $this->transaction?->transaction_reference ?? $this->order->transaction_reference,
            ))
            ->icon('heroicon-o-arrow-uturn-left')
            ->getDatabaseMessage();
    }
}
