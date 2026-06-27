<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentGateways\StripeGateway;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user  = User::factory()->create();
        $this->token = JWTAuth::fromUser($this->user);

        // Register gateways in the service
        $service = app(PaymentService::class);
        $service->registerGateway(new StripeGateway());
    }

    private function authHeader(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    // ─── store ────────────────────────────────────────────────────────────────

    /** @test */
    public function can_process_payment_for_confirmed_order(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'confirmed',
            'total_amount' => 150.00,
        ]);

        $response = $this->postJson('/api/v1/payments', [
            'order_id'       => $order->id,
            'payment_method' => 'stripe',
        ], $this->authHeader());

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Payment processed successfully.')
            ->assertJsonStructure(['data' => ['payment_id', 'status', 'transaction_id']]);
    }

    /** @test */
    public function cannot_process_payment_for_pending_order(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'pending',
        ]);

        $response = $this->postJson('/api/v1/payments', [
            'order_id'       => $order->id,
            'payment_method' => 'stripe',
        ], $this->authHeader());

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertStringContainsString(
            'confirmed',
            $response->json('message')
        );
    }

    /** @test */
    public function cannot_process_payment_for_cancelled_order(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'cancelled',
        ]);

        $response = $this->postJson('/api/v1/payments', [
            'order_id'       => $order->id,
            'payment_method' => 'stripe',
        ], $this->authHeader());

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    /** @test */
    public function payment_is_stored_in_database(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'confirmed',
            'total_amount' => 200.00,
        ]);

        $this->postJson('/api/v1/payments', [
            'order_id'       => $order->id,
            'payment_method' => 'stripe',
        ], $this->authHeader());

        $this->assertDatabaseHas('payments', [
            'order_id'       => $order->id,
            'payment_method' => 'stripe',
            'status'         => 'successful',
        ]);
    }

    /** @test */
    public function payment_requires_valid_payment_method(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'confirmed',
        ]);

        $response = $this->postJson('/api/v1/payments', [
            'order_id'       => $order->id,
            'payment_method' => 'invalid_gateway',
        ], $this->authHeader());

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['payment_method']]);
    }

    /** @test */
    public function payment_requires_existing_order_id(): void
    {
        $response = $this->postJson('/api/v1/payments', [
            'order_id'       => 9999,
            'payment_method' => 'stripe',
        ], $this->authHeader());

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['order_id']]);
    }

    /** @test */
    public function payment_id_starts_with_pay_prefix(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'confirmed',
        ]);

        $response = $this->postJson('/api/v1/payments', [
            'order_id'       => $order->id,
            'payment_method' => 'stripe',
        ], $this->authHeader());

        $this->assertStringStartsWith('PAY-', $response->json('data.payment_id'));
    }

    // ─── index ────────────────────────────────────────────────────────────────

    /** @test */
    public function can_retrieve_all_payments(): void
    {
        Payment::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/payments', $this->authHeader());

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'meta' => ['total', 'successful', 'failed', 'pending']]);
    }

    /** @test */
    public function payments_meta_counts_are_correct(): void
    {
        $order = Order::factory()->create(['status' => 'confirmed']);
        Payment::factory()->create(['order_id' => $order->id, 'status' => 'successful']);
        Payment::factory()->create(['order_id' => $order->id, 'status' => 'failed']);
        Payment::factory()->create(['order_id' => $order->id, 'status' => 'pending']);

        $response = $this->getJson('/api/v1/payments', $this->authHeader());

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.successful'));
        $this->assertEquals(1, $response->json('meta.failed'));
        $this->assertEquals(1, $response->json('meta.pending'));
    }

    // ─── show ─────────────────────────────────────────────────────────────────

    /** @test */
    public function can_retrieve_payment_by_payment_id(): void
    {
        $payment = Payment::factory()->create(['payment_id' => 'PAY-TESTID123']);

        $response = $this->getJson('/api/v1/payments/PAY-TESTID123', $this->authHeader());

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.payment_id', 'PAY-TESTID123');
    }

    /** @test */
    public function show_returns_404_for_nonexistent_payment(): void
    {
        $response = $this->getJson('/api/v1/payments/PAY-NOTFOUND', $this->authHeader());

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    // ─── byOrder ──────────────────────────────────────────────────────────────

    /** @test */
    public function can_retrieve_payments_for_specific_order(): void
    {
        $order = Order::factory()->create(['status' => 'confirmed']);
        Payment::factory()->count(2)->create(['order_id' => $order->id]);

        $response = $this->getJson("/api/v1/orders/{$order->id}/payments", $this->authHeader());

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.order_id', $order->id)
            ->assertJsonPath('meta.total', 2);
    }

    /** @test */
    public function by_order_returns_404_for_nonexistent_order(): void
    {
        $response = $this->getJson('/api/v1/orders/9999/payments', $this->authHeader());

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    // ─── gateways ─────────────────────────────────────────────────────────────

    /** @test */
    public function can_list_available_gateways(): void
    {
        $response = $this->getJson('/api/v1/payments/gateways', $this->authHeader());

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data']);

        $this->assertContains('stripe', $response->json('data'));
    }

    // ─── Auth ─────────────────────────────────────────────────────────────────

    /** @test */
    public function unauthenticated_user_cannot_access_payments(): void
    {
        $response = $this->getJson('/api/v1/payments');

        $response->assertStatus(401);
    }
}
