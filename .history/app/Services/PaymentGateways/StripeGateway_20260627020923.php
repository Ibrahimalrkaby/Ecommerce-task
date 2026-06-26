<?php

namespace App\Services\PaymentGateways;

class StripeGateway implements PaymentGatewayInterface
{
    public function getName(): string
    {
        return 'stripe';
    }

    public function process(array $paymentData): array
    {
        // ── Simulate Stripe Processing ─────────────────────────────────────────
        // In production: use stripe-php SDK
        // composer require stripe/stripe-php
        // \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        $amount = $paymentData['amount'];

        $transactionId = 'STR-' . strtoupper(uniqid());

        return [
            'success'        => true,
            'transaction_id' => $transactionId,
            'message'        => 'Stripe payment processed successfully.',
            'raw'            => [
                'gateway'        => 'stripe',
                'charge_id'      => $transactionId,
                'amount_cents'   => (int) ($amount * 100), // Stripe uses cents
                'currency'       => strtolower($paymentData['currency'] ?? 'usd'),
                'authorized_at'  => now()->toISOString(),
            ],
        ];
    }
}
