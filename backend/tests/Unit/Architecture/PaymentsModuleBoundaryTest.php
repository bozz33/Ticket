<?php

namespace Tests\Unit\Architecture;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;
use Ticket\Payments\Contracts\CheckoutManager;
use Ticket\Payments\Contracts\PaymentWebhookReceiver;
use Ticket\Payments\Contracts\PayoutManager;
use Ticket\Payments\Contracts\PricingEngine;
use Ticket\Payments\Contracts\RefundManager;
use Ticket\Payments\Contracts\SettlementWorkflow;
use Ticket\Payments\Contracts\TenantRefundManager;

class PaymentsModuleBoundaryTest extends TestCase
{
    public function test_payments_contracts_are_bound_in_the_container(): void
    {
        foreach ([
            CheckoutManager::class,
            PaymentWebhookReceiver::class,
            PricingEngine::class,
            RefundManager::class,
            PayoutManager::class,
            SettlementWorkflow::class,
            TenantRefundManager::class,
        ] as $contract) {
            $this->assertInstanceOf($contract, app($contract));
        }
    }

    public function test_application_consumers_depend_on_payments_contracts_not_concrete_services(): void
    {
        $forbidden = [
            'App\\Services\\Payments\\PublicPaymentService',
            'App\\Services\\Payments\\PaymentWebhookService',
            'App\\Services\\Payments\\RefundService',
            'App\\Services\\Payments\\PayoutPolicyService',
            'App\\Services\\Payments\\SettlementWorkflowService',
            'App\\Services\\Payments\\TenantRefundService',
        ];

        $violations = [];

        foreach ($this->consumerFiles() as $file) {
            $contents = (string) file_get_contents($file->getPathname());

            foreach ($forbidden as $dependency) {
                if (str_contains($contents, $dependency)) {
                    $violations[] = sprintf('%s imports %s', $file->getPathname(), $dependency);
                }
            }
        }

        $this->assertSame([], $violations);
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function consumerFiles(): iterable
    {
        foreach ([
            app_path('Http'),
            app_path('Filament'),
            app_path('Services/Tenancy'),
        ] as $directory) {
            if (! is_dir($directory)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

            foreach ($iterator as $file) {
                if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                    yield $file;
                }
            }
        }
    }
}
