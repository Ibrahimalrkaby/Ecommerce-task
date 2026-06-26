<?php

namespace App\Services\PaymentGateways;

class CreditCardGateway implements PaymentGatewayInterface
{
    public function getName(): string
    {
        return 'credit_card';
    }

    public function process(array $paymentData): array
    {
        // ── Simulate Credit Card Processing ───────────────────────────────────
        // In production: integrate with Stripe, Braintree, etc.

        $amount = $paymentData['amount'];

        // Simulate: amounts ending in .99 always fail (for testing)
        $simulateFail = str_ends_with((string) $amount, '.99');

        if ($simulateFail) {
            return [
                'success'        => false,
                'transaction_id' => null,
                'message'        => 'Credit card declined: insufficient funds.',
                'raw'            => [
                    'gateway'    => 'credit_card',
                    'error_code' => 'INSUFFICIENT_FUNDS',
                    'amount'     => $amount,
                ],
            ];
        }

        $transactionId = 'CC-' . strtoupper(uniqid());

        return [
            'success'        => true,
            'transaction_id' => $transactionId,
            'message'        => 'Credit card payment processed successfully.',
            'raw'            => [
                'gateway'        => 'credit_card',
                'transaction_id' => $transactionId,
                'amount'         => $amount,
                'currency'       => $paymentData['currency'] ?? 'USD',
                'authorized_at'  => now()->toISOString(),
            ],
        ];
    }
}
