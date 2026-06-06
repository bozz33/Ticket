<?php

namespace App\Notifications;

use App\Models\FormDefinition;
use App\Models\FormSubmission;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FormSubmissionReceivedNotification extends Notification
{
    public function __construct(
        private readonly FormDefinition $formDefinition,
        private readonly FormSubmission $submission,
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $formTitle = $this->formDefinition->title ?? $this->formDefinition->name ?? 'Formulaire';
        $submittedAt = $this->submission->submitted_at?->toDateTimeString() ?? now()->toDateTimeString();

        return (new MailMessage)
            ->subject(sprintf('Nouvelle soumission — %s', $formTitle))
            ->greeting('Bonjour,')
            ->line(sprintf('Une nouvelle réponse a été soumise pour le formulaire "%s".', $formTitle))
            ->line(sprintf('Soumis le : %s', $submittedAt))
            ->line(sprintf('Identifiant de soumission : %s', $this->submission->public_id))
            ->action('Voir la soumission', url('/'))
            ->line('Connectez-vous à votre panneau organisateur pour consulter les détails et les pièces jointes.');
    }
}
