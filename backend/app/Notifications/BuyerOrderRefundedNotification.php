<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Refund;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BuyerOrderRefundedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly Refund $refund,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre remboursement est traité')
            ->greeting('Bonjour')
            ->line(sprintf('Le remboursement de la commande %s a été traité.', $this->order->reference))
            ->line(sprintf('Montant remboursé : %s %s', number_format((int) $this->refund->amount_refunded_to_buyer, 0, ',', ' '), $this->refund->currency_code))
            ->action('Voir mon compte', url('/compte'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Remboursement traité',
            'body' => sprintf('Votre commande %s a été remboursée.', $this->order->reference),
            'action_url' => '/compte',
            'icon' => 'refund',
        ];
    }
}
