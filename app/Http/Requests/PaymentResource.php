<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'payment_id'       => $this->payment_id,
            'order_id'         => $this->order_id,
            'status'           => $this->status,
            'payment_method'   => $this->payment_method,
            'amount'           => (float) $this->amount,
            'transaction_id'   => $this->transaction_id,
            'failure_reason'   => $this->failure_reason,
            'processed_at'     => $this->processed_at?->toISOString(),
            'created_at'       => $this->created_at->toISOString(),

            // Include order summary only when loaded
            'order' => $this->whenLoaded('order', fn() => [
                'id'           => $this->order->id,
                'status'       => $this->order->status,
                'total_amount' => (float) $this->order->total_amount,
            ]),

            // Include gateway raw response only in detail view
            'gateway_response' => $this->when(
                $request->routeIs('payments.show'),
                $this->gateway_response
            ),
        ];
    }
}
