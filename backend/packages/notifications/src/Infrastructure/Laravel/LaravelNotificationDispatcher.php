<?php

namespace Ticket\Notifications\Infrastructure\Laravel;

use Illuminate\Notifications\Notification;
use Ticket\Notifications\Contracts\NotificationDispatcher;

class LaravelNotificationDispatcher implements NotificationDispatcher
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (method_exists($notifiable, 'notify')) {
            $notifiable->notify($notification);
        }
    }

    public function sendMany(iterable $notifiables, Notification $notification): void
    {
        foreach ($notifiables as $notifiable) {
            if (is_object($notifiable)) {
                $this->send($notifiable, $notification);
            }
        }
    }
}
