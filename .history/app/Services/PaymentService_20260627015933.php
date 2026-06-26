<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentGateways\PaymentGatewayInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    /**
     * Registered gateways: method_name => gateway instance
     *
     * @var array<string, PaymentGatewayInterface>
     */
    private array $gateways = [];

    // ─── Register Gateways ────────────────────────────────────────────────────

    public function registerGateway(PaymentGatewayInterface $gateway): void
    {
        $this->gateways[$gateway->getName()] = $gateway;
    }

    public function getAvailableGateways(): array
    {
        return array_keys($this->gateways);
    }

    // ─── Core: Process Payment ────────────────────────────────────────────────

    /**
     * @throws \Exception
     */
    public function processPayment(array $data): Payment
    {
        // 1. Load the order
        $order = Order::findOrFail($data['order_id']);

        // 2. Business Rule: Order must be confirmed
        if (! $order->isConfirmed()) {
            throw new \Exception(
                "Payment cannot be processed. Order #{$order->id} status is '{$order->status}'. Only confirmed orders can be paid."
            );
        }

        // 3. Resolve the gateway
        $method  = $data['payment_method'];
        $gateway = $this->resolveGateway($method);

        // 4. Wrap in a transaction
        return DB::transaction(function () use ($data, $order, $gateway) {

            // Create a PENDING payment record first
            $payment = Payment::create([
                'payment_id'     => 'PAY-' . strtoupper(Str::random(10)),
                'order_id'       => $order->id,
                'status'         => 'pending',
                'payment_method' => $data['payment_method'],
                'amount'         => $data['amount'] ?? $order->total_amount,
            ]);

            // Call the gateway
            $result = $gateway->process([
                'amount'   => $payment->amount,
                'currency' => $data['currency'] ?? 'USD',
                'order_id' => $order->id,
                'email'    => $data['email'] ?? null,
            ]);

            // Update payment based on gateway response
            $payment->update([
                'status'           => $result['success'] ? 'successful' : 'failed',
                'transaction_id'   => $result['transaction_id'],
                'gateway_response' => $result['raw'],
                'failure_reason'   => $result['success'] ? null : $result['message'],
                'processed_at'     => now(),
            ]);

            return $payment->fresh();
        });
    }

    // ─── Resolve Gateway ──────────────────────────────────────────────────────

    private function resolveGateway(string $method): PaymentGatewayInterface
    {
        if (! isset($this->gateways[$method])) {
            $available = implode(', ', $this->getAvailableGateways());
            throw new \InvalidArgumentException(
                "Payment gateway '{$method}' is not registered. Available: [{$available}]"
            );
        }

        return $this->gateways[$method];
    }

    // ─── Queries ──────────────────────────────────────────────────────────────

    public function getAllPayments(): \Illuminate\Database\Eloquent\Collection
    {
        return Payment::with('order')->latest()->get();
    }

    public function getPaymentsByOrder(int $orderId): \Illuminate\Database\Eloquent\Collection
    {
        // Ensure the order exists
        Order::findOrFail($orderId);

        return Payment::with('order')
            ->where('order_id', $orderId)
            ->latest()
            ->get();
    }

    public function getPaymentById(string $paymentId): Payment
    {
        return Payment::with('order')
            ->where('payment_id', $paymentId)
            ->firstOrFail();
    }
}
