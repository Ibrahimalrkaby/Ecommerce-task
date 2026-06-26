<?php

namespace App\Payments;

use App\Models\Order;

interface PaymentGatewayInterface
{
    public function process(Order $order): array;
}
