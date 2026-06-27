<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payment_id'     => 'PAY-' . strtoupper(Str::random(10)),
            'order_id'       => Order::factory(),
            'status'         => $this->faker->randomElement(['pending', 'successful', 'failed']),
            'payment_method' => $this->faker->randomElement(['credit_card', 'paypal', 'stripe', 'cash']),
            'amount'         => $this->faker->randomFloat(2, 10, 5000),
            'transaction_id' => 'TXN-' . strtoupper(Str::random(8)),
            'gateway_response' => [
                'gateway'       => 'test',
                'authorized_at' => now()->toISOString(),
            ],
            'processed_at' => now(),
        ];
    }

    public function successful(): static
    {
        return $this->state(['status' => 'successful']);
    }

    public function failed(): static
    {
        return $this->state([
            'status'         => 'failed',
            'transaction_id' => null,
            'failure_reason' => 'Card declined.',
        ]);
    }

    public function pending(): static
    {
        return $this->state([
            'status'         => 'pending',
            'transaction_id' => null,
            'processed_at'   => null,
        ]);
    }
}
