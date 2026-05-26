<?php

namespace Ticket\Payments;

use App\Support\Payments\GatewayAmountConverter;
use Illuminate\Support\ServiceProvider;
use Ticket\FinanceAccounting\Contracts\FinancePolicyCatalog;
use Ticket\Payments\Application\OrderFulfillmentService;
use Ticket\Payments\Application\PaymentGatewayCredentialResolver;
use Ticket\Payments\Application\PaymentWebhookService;
use Ticket\Payments\Contracts\CheckoutItemResolver;
use Ticket\Payments\Contracts\CheckoutManager;
use Ticket\Payments\Contracts\PaymentWebhookReceiver;
use Ticket\Payments\Contracts\PayoutManager;
use Ticket\Payments\Contracts\PricingEngine;
use Ticket\Payments\Contracts\RefundManager;
use Ticket\Payments\Contracts\SettlementWorkflow;
use Ticket\Payments\Contracts\TenantRefundManager;
use Ticket\Payments\Infrastructure\Laravel\LaravelCheckoutManager;
use Ticket\Payments\Infrastructure\Laravel\LaravelPaymentWebhookReceiver;
use Ticket\Payments\Infrastructure\Laravel\LaravelPayoutManager;
use Ticket\Payments\Infrastructure\Laravel\LaravelPricingEngine;
use Ticket\Payments\Infrastructure\Laravel\LaravelRefundManager;
use Ticket\Payments\Infrastructure\Laravel\LaravelSettlementWorkflow;
use Ticket\Payments\Infrastructure\Laravel\LaravelTenantRefundManager;
use Ticket\Payments\Infrastructure\Laravel\OfferCheckoutItemResolver;

class PaymentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayCredentialResolver::class, fn (): PaymentGatewayCredentialResolver => new PaymentGatewayCredentialResolver);
        $this->app->singleton(PaymentWebhookService::class, fn (): PaymentWebhookService => new PaymentWebhookService(
            $this->app->make(OrderFulfillmentService::class),
            $this->app->make(PaymentGatewayCredentialResolver::class),
            $this->app->make(FinancePolicyCatalog::class),
            $this->app->make(GatewayAmountConverter::class),
        ));

        $this->app->bind(CheckoutManager::class, LaravelCheckoutManager::class);
        $this->app->bind(CheckoutItemResolver::class, OfferCheckoutItemResolver::class);
        $this->app->bind(PaymentWebhookReceiver::class, LaravelPaymentWebhookReceiver::class);
        $this->app->bind(PricingEngine::class, LaravelPricingEngine::class);
        $this->app->bind(RefundManager::class, LaravelRefundManager::class);
        $this->app->bind(PayoutManager::class, LaravelPayoutManager::class);
        $this->app->bind(SettlementWorkflow::class, LaravelSettlementWorkflow::class);
        $this->app->bind(TenantRefundManager::class, LaravelTenantRefundManager::class);
    }
}
