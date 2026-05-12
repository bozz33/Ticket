<?php

namespace App\Support\Payments;

class GatewayAmountConverter
{
    public function toGateway(int $amount, string $currencyCode, ?string $gatewayCode = null): int
    {
        $multiplier = $this->multiplier($gatewayCode, $currencyCode);

        return max(0, $amount) * $multiplier;
    }

    public function fromGateway(int $gatewayAmount, string $currencyCode, ?string $gatewayCode = null): int
    {
        $multiplier = $this->multiplier($gatewayCode, $currencyCode);

        if ($multiplier <= 1) {
            return max(0, $gatewayAmount);
        }

        return (int) round(max(0, $gatewayAmount) / $multiplier);
    }

    private function multiplier(?string $gatewayCode, string $currencyCode): int
    {
        $gatewayCode = strtolower(trim((string) $gatewayCode));
        $currencyCode = strtoupper(trim($currencyCode));

        if ($gatewayCode === 'paystack') {
            /**
             * Paystack attend toujours le plus petit sous-multiple de devise.
             * Même pour XOF / XAF / GHS, l'API demande une valeur multipliée par 100.
             */
            return 100;
        }

        return 1;
    }
}
