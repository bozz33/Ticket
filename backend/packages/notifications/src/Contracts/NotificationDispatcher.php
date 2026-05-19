<?php

namespace Ticket\Notifications\Contracts;

use Illuminate\Notifications\Notification;

interface NotificationDispatcher
{
    public function send(object $notifiable, Notification $notification): void;

    public function sendMany(iterable $notifiables, Notification $notification): void;
}
