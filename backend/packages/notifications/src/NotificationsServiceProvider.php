<?php

namespace Ticket\Notifications;

use Illuminate\Support\ServiceProvider;
use Ticket\Notifications\Contracts\DomainEventPublisher;
use Ticket\Notifications\Contracts\NotificationDispatcher;
use Ticket\Notifications\Contracts\OutboxDispatcher;
use Ticket\Notifications\Infrastructure\Laravel\LaravelDomainEventPublisher;
use Ticket\Notifications\Infrastructure\Laravel\LaravelNotificationDispatcher;
use Ticket\Notifications\Infrastructure\Laravel\LaravelOutboxDispatcher;

class NotificationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(NotificationDispatcher::class, LaravelNotificationDispatcher::class);
        $this->app->bind(DomainEventPublisher::class, LaravelDomainEventPublisher::class);
        $this->app->bind(OutboxDispatcher::class, LaravelOutboxDispatcher::class);
    }
}
