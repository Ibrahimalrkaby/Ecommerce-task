<?php

namespace App\Services\PaymentGateways;

interface PaymentGatewayInterface
{
    /**
     * Process the payment.
     *
     * @param  array  $paymentData  ['amount', 'currency', 'order_id', ...]
     * @return array  ['success' => bool, 'transaction_id' => string|null, 'message' => string, 'raw' => array]
     */
    public function process(array $paymentData): array;

    /**
     * Return the gateway identifier (matches payment_method column value).
     */
    public function getName(): string;
}
