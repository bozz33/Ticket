<?php

namespace App\Notifications;

use App\Models\Settlement;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SettlementRequestedPlatformNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Settlement $settlement,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tenantName = $this->settlement->tenant?->name ?? 'Organisateur';

        return (new MailMessage)
            ->subject(sprintf('Nouvelle demande de reversement %s', $this->settlement->reference))
            ->greeting('Bonjour,')
            ->line(sprintf('%s a initié une demande de reversement.', $tenantName))
            ->line(sprintf(
                'Référence: %s | Montant: %s %s',
                $this->settlement->reference,
                number_format((int) $this->settlement->gross_amount, 0, ',', ' '),
                $this->settlement->currency_code,
            ))
            ->action('Consulter la demande', $this->settlementUrl())
            ->line('Merci de la valider ou de la rejeter dans le panel plateforme.');
    }

    public function toArray(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Nouvelle demande de reversement')
            ->body(sprintf(
                '%s a demandé %s %s (%s).',
                $this->settlement->tenant?->name ?? 'Un organisateur',
                number_format((int) $this->settlement->gross_amount, 0, ',', ' '),
                $this->settlement->currency_code,
                $this->settlement->reference,
            ))
            ->icon('heroicon-o-building-library')
            ->getDatabaseMessage();
    }

    private function settlementUrl(): string
    {
        return url('/platform/settlements');
    }
}
