<?php

namespace App\Notifications;

use App\Models\FormDefinition;
use App\Models\FormSubmission;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FormSubmissionConfirmationNotification extends Notification
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
        $successMessage = $this->formDefinition->success_message
            ?? 'Votre réponse a bien été enregistrée. Nous reviendrons vers vous prochainement.';

        return (new MailMessage)
            ->subject(sprintf('Confirmation — %s', $formTitle))
            ->greeting('Bonjour,')
            ->line($successMessage)
            ->line(sprintf('Référence de votre soumission : %s', $this->submission->public_id))
            ->line('Merci de votre participation.');
    }
}
