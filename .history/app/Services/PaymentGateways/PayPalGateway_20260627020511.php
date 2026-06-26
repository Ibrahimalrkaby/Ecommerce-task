<?php

namespace App\Services\PaymentGateways;

class PayPalGateway implements PaymentGatewayInterface
{
    public function getName(): string
    {
        return 'paypal';
    }

    public function process(array $paymentData): array
    {
        // ── Simulate PayPal Processing ─────────────────────────────────────────
        // In production: use PayPal SDK / REST API

        $amount = $paymentData['amount'];

        // Simulate: amounts above 10,000 require manual review (fail for now)
        if ($amount > 10000) {
            return [
                'success'        => false,
                'transaction_id' => null,
                'message'        => 'PayPal: transaction exceeds limit. Manual review required.',
                'raw'            => [
                    'gateway'    => 'paypal',
                    'error_code' => 'AMOUNT_EXCEEDS_LIMIT',
                    'amount'     => $amount,
                ],
            ];
        }

        $transactionId = 'PP-' . strtoupper(uniqid());

        return [
            'success'        => true,
            'transaction_id' => $transactionId,
            'message'        => 'PayPal payment processed successfully.',
            'raw'            => [
                'gateway'        => 'paypal',
                'transaction_id' => $transactionId,
                'payer_email'    => $paymentData['email'] ?? 'customer@example.com',
                'amount'         => $amount,
                'currency'       => $paymentData['currency'] ?? 'USD',
                'authorized_at'  => now()->toISOString(),
            ],
        ];
    }
}
