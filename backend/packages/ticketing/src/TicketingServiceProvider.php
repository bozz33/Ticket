<?php

namespace Ticket\Ticketing;

use Illuminate\Support\ServiceProvider;
use Ticket\Payments\Contracts\CheckoutItemResolver;
use Ticket\Payments\Infrastructure\Laravel\OfferCheckoutItemResolver;
use Ticket\Tenancy\Application\TenantPublicProfileService;
use Ticket\Ticketing\Application\DocumentService;
use Ticket\Ticketing\Application\EventService;
use Ticket\Ticketing\Application\EventTicketInventoryService;
use Ticket\Ticketing\Application\EventTicketOfferBridgeService;
use Ticket\Ticketing\Contracts\AccessPassCatalog;
use Ticket\Ticketing\Contracts\AccessPassCheckin;
use Ticket\Ticketing\Contracts\BuyerRefundRequests;
use Ticket\Ticketing\Contracts\DocumentCatalog;
use Ticket\Ticketing\Contracts\EventCatalog;
use Ticket\Ticketing\Contracts\EventEngagement;
use Ticket\Ticketing\Contracts\EventTicketInventory;
use Ticket\Ticketing\Contracts\EventTicketOfferBridge;
use Ticket\Ticketing\Contracts\OrderCatalog;
use Ticket\Ticketing\Contracts\OrganizationAudience;
use Ticket\Ticketing\Contracts\ReceiptCatalog;
use Ticket\Ticketing\Infrastructure\Laravel\LaravelAccessPassCatalog;
use Ticket\Ticketing\Infrastructure\Laravel\LaravelAccessPassCheckin;
use Ticket\Ticketing\Infrastructure\Laravel\LaravelBuyerRefundRequests;
use Ticket\Ticketing\Infrastructure\Laravel\LaravelDocumentCatalog;
use Ticket\Ticketing\Infrastructure\Laravel\LaravelEventCatalog;
use Ticket\Ticketing\Infrastructure\Laravel\LaravelEventEngagement;
use Ticket\Ticketing\Infrastructure\Laravel\LaravelOrderCatalog;
use Ticket\Ticketing\Infrastructure\Laravel\LaravelOrganizationAudience;
use Ticket\Ticketing\Infrastructure\Laravel\LaravelReceiptCatalog;
use Ticket\Ticketing\Infrastructure\Laravel\TicketingCheckoutItemResolver;

class TicketingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DocumentService::class, fn (): DocumentService => new DocumentService(
            $this->app->make(TenantPublicProfileService::class),
        ));
        $this->app->singleton(EventService::class, fn (): EventService => new EventService(
            $this->app->make(TenantPublicProfileService::class),
        ));

        $this->app->bind(EventCatalog::class, LaravelEventCatalog::class);
        $this->app->bind(EventTicketInventory::class, EventTicketInventoryService::class);
        $this->app->bind(EventTicketOfferBridge::class, EventTicketOfferBridgeService::class);
        $this->app->bind(CheckoutItemResolver::class, fn (): TicketingCheckoutItemResolver => new TicketingCheckoutItemResolver(
            $this->app->make(EventTicketInventory::class),
            $this->app->make(OfferCheckoutItemResolver::class),
        ));
        $this->app->bind(DocumentCatalog::class, LaravelDocumentCatalog::class);
        $this->app->bind(OrderCatalog::class, LaravelOrderCatalog::class);
        $this->app->bind(ReceiptCatalog::class, LaravelReceiptCatalog::class);
        $this->app->bind(AccessPassCatalog::class, LaravelAccessPassCatalog::class);
        $this->app->bind(AccessPassCheckin::class, LaravelAccessPassCheckin::class);
        $this->app->bind(EventEngagement::class, LaravelEventEngagement::class);
        $this->app->bind(OrganizationAudience::class, LaravelOrganizationAudience::class);
        $this->app->bind(BuyerRefundRequests::class, LaravelBuyerRefundRequests::class);
    }
}
