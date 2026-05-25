<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BuyerOrderConfirmedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Order $order) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre paiement est confirmé')
            ->greeting('Bonjour')
            ->line(sprintf('Votre paiement %s a bien été confirmé.', $this->order->reference))
            ->line(sprintf('Montant : %s %s', number_format((int) $this->order->total_amount, 0, ',', ' '), $this->order->currency_code))
            ->action('Voir mon compte', url('/compte'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Paiement confirmé',
            'body' => sprintf('Votre commande %s a été confirmée.', $this->order->reference),
            'action_url' => '/compte',
            'icon' => 'receipt',
        ];
    }
}
