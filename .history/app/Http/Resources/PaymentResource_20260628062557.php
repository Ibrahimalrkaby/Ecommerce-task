<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'payment_id'       => $this->payment_id,
            'order_id'         => $this->order_id,
            'status'           => $this->status,
            'payment_method'   => $this->payment_method,
            'amount'           => $this->amount,
            'transaction_id'   => $this->transaction_id,
            'failure_reason'   => $this->failure_reason,
            'gateway_response' => $this->gateway_response,
            'processed_at'     => $this->processed_at,
            'created_at'       => $this->created_at,
        ];
    }
}
