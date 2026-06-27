<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PaymentGateways\StripeGateway;
use App\Services\PaymentGateways\PaymentGatewayInterface;

class PaymentGatewayTest extends TestCase
{
    // ─── Stripe ───────────────────────────────────────────────────────────────

    /** @test */
    public function stripe_gateway_implements_interface(): void
    {
        $gateway = new StripeGateway();

        $this->assertInstanceOf(PaymentGatewayInterface::class, $gateway);
    }

    /** @test */
    public function stripe_gateway_returns_correct_name(): void
    {
        $gateway = new StripeGateway();

        $this->assertEquals('stripe', $gateway->getName());
    }

    /** @test */
    public function stripe_gateway_processes_payment_successfully(): void
    {
        $gateway = new StripeGateway();

        $result = $gateway->process([
            'amount'   => 100.00,
            'currency' => 'usd',
            'order_id' => 1,
        ]);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['transaction_id']);
        $this->assertStringStartsWith('STR-', $result['transaction_id']);
        $this->assertEquals('Stripe payment processed successfully.', $result['message']);
    }

    /** @test */
    public function stripe_gateway_raw_contains_correct_data(): void
    {
        $gateway = new StripeGateway();

        $result = $gateway->process([
            'amount'   => 50.00,
            'currency' => 'usd',
            'order_id' => 1,
        ]);

        $this->assertArrayHasKey('raw', $result);
        $this->assertEquals('stripe', $result['raw']['gateway']);
        $this->assertEquals(5000, $result['raw']['amount_cents']); // 50.00 * 100
        $this->assertEquals('usd', $result['raw']['currency']);
        $this->assertArrayHasKey('authorized_at', $result['raw']);
    }

    /** @test */
    public function stripe_gateway_converts_amount_to_cents_correctly(): void
    {
        $gateway = new StripeGateway();

        $result = $gateway->process([
            'amount'   => 99.99,
            'currency' => 'usd',
            'order_id' => 1,
        ]);

        $this->assertEquals(9999, $result['raw']['amount_cents']);
    }

    /** @test */
    public function stripe_gateway_defaults_currency_to_usd(): void
    {
        $gateway = new StripeGateway();

        // بدون currency في الـ payload
        $result = $gateway->process([
            'amount'   => 100.00,
            'order_id' => 1,
        ]);

        $this->assertEquals('usd', $result['raw']['currency']);
    }

    /** @test */
    public function stripe_gateway_generates_unique_transaction_ids(): void
    {
        $gateway = new StripeGateway();

        $result1 = $gateway->process(['amount' => 100.00, 'order_id' => 1]);
        $result2 = $gateway->process(['amount' => 100.00, 'order_id' => 2]);

        $this->assertNotEquals($result1['transaction_id'], $result2['transaction_id']);
    }

    /** @test */
    public function stripe_gateway_currency_is_stored_lowercase(): void
    {
        $gateway = new StripeGateway();

        $result = $gateway->process([
            'amount'   => 100.00,
            'currency' => 'USD', // uppercase input
            'order_id' => 1,
        ]);

        $this->assertEquals('usd', $result['raw']['currency']);
    }
}
