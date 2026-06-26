<?php

namespace App\Services\PaymentGateways;

class CashGateway implements PaymentGatewayInterface
{
    public function getName(): string
    {
        return 'cash';
    }

    public function process(array $paymentData): array
    {
        // ── Cash on Delivery ───────────────────────────────────────────────────
        // No real processing — just mark as pending until delivery confirms.

        $transactionId = 'CASH-' . strtoupper(uniqid());

        return [
            'success'        => true,
            'transaction_id' => $transactionId,
            'message'        => 'Cash on delivery registered. Payment pending upon delivery.',
            'raw'            => [
                'gateway'       => 'cash',
                'reference'     => $transactionId,
                'amount'        => $paymentData['amount'],
                'registered_at' => now()->toISOString(),
            ],
        ];
    }
}
