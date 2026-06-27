<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user  = User::factory()->create();
        $this->token = JWTAuth::fromUser($this->user);
    }

    private function authHeader(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    // ─── index ────────────────────────────────────────────────────────────────

    /** @test */
    public function user_can_list_their_orders(): void
    {
        Order::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/orders', $this->authHeader());

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'current_page', 'total']);
    }

    /** @test */
    public function user_cannot_see_other_users_orders(): void
    {
        $otherUser = User::factory()->create();
        Order::factory()->count(2)->create(['user_id' => $otherUser->id]);

        $response = $this->getJson('/api/orders', $this->authHeader());

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('total'));
    }

    /** @test */
    public function user_can_filter_orders_by_status(): void
    {
        Order::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);
        Order::factory()->create(['user_id' => $this->user->id, 'status' => 'confirmed']);

        $response = $this->getJson('/api/orders?status=pending', $this->authHeader());

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('total'));
        $this->assertEquals('pending', $response->json('data.0.status'));
    }

    /** @test */
    public function filter_orders_with_invalid_status_returns_422(): void
    {
        $response = $this->getJson('/api/orders?status=invalid', $this->authHeader());

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['status']]);
    }

    /** @test */
    public function orders_list_is_paginated(): void
    {
        Order::factory()->count(15)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/orders?per_page=5', $this->authHeader());

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
        $this->assertEquals(15, $response->json('total'));
    }

    // ─── store ────────────────────────────────────────────────────────────────

    /** @test */
    public function user_can_create_an_order(): void
    {
        $product = Product::factory()->create(['price' => 100, 'stock' => 10]);

        $response = $this->postJson('/api/orders', [
            'products' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ], $this->authHeader());

        $response->assertStatus(201)
            ->assertJsonPath('order.status', 'pending')
            ->assertJsonPath('order.total_amount', '200.00');
    }

    /** @test */
    public function order_creation_decrements_stock(): void
    {
        $product = Product::factory()->create(['stock' => 10]);

        $this->postJson('/api/orders', [
            'products' => [['product_id' => $product->id, 'quantity' => 3]],
        ], $this->authHeader());

        $this->assertEquals(7, $product->fresh()->stock);
    }

    /** @test */
    public function order_creation_fails_when_stock_is_insufficient(): void
    {
        $product = Product::factory()->create(['stock' => 2]);

        $response = $this->postJson('/api/orders', [
            'products' => [['product_id' => $product->id, 'quantity' => 5]],
        ], $this->authHeader());

        $response->assertStatus(400)
            ->assertJsonStructure(['error']);
    }

    /** @test */
    public function order_creation_requires_products(): void
    {
        $response = $this->postJson('/api/orders', [], $this->authHeader());

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['products']]);
    }

    /** @test */
    public function order_creation_requires_valid_product_id(): void
    {
        $response = $this->postJson('/api/orders', [
            'products' => [['product_id' => 9999, 'quantity' => 1]],
        ], $this->authHeader());

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['products.0.product_id']]);
    }

    // ─── show ─────────────────────────────────────────────────────────────────

    /** @test */
    public function user_can_view_their_own_order(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/api/orders/{$order->id}", $this->authHeader());

        $response->assertStatus(200)
            ->assertJsonPath('id', $order->id);
    }

    /** @test */
    public function user_cannot_view_another_users_order(): void
    {
        $otherUser = User::factory()->create();
        $order     = Order::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson("/api/orders/{$order->id}", $this->authHeader());

        $response->assertStatus(403);
    }

    /** @test */
    public function show_returns_404_for_nonexistent_order(): void
    {
        $response = $this->getJson('/api/orders/9999', $this->authHeader());

        $response->assertStatus(404);
    }

    // ─── update ───────────────────────────────────────────────────────────────

    /** @test */
    public function user_can_update_order_status(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'pending',
        ]);

        $response = $this->putJson("/api/orders/{$order->id}", [
            'status' => 'confirmed',
        ], $this->authHeader());

        $response->assertStatus(200)
            ->assertJsonPath('order.status', 'confirmed');
    }

    /** @test */
    public function update_rejects_invalid_status(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $response = $this->putJson("/api/orders/{$order->id}", [
            'status' => 'shipped', // not allowed
        ], $this->authHeader());

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['status']]);
    }

    /** @test */
    public function user_cannot_update_another_users_order(): void
    {
        $otherUser = User::factory()->create();
        $order     = Order::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->putJson("/api/orders/{$order->id}", [
            'status' => 'confirmed',
        ], $this->authHeader());

        $response->assertStatus(403);
    }

    // ─── destroy ──────────────────────────────────────────────────────────────

    /** @test */
    public function user_can_delete_order_without_payments(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order   = Order::factory()->create(['user_id' => $this->user->id]);
        $order->products()->attach($product->id, ['quantity' => 2, 'price' => $product->price]);

        $response = $this->deleteJson("/api/orders/{$order->id}", [], $this->authHeader());

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Order deleted successfully and stock restored');

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertEquals(12, $product->fresh()->stock); // stock restored
    }

    /** @test */
    public function user_cannot_delete_order_with_payments(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        Payment::factory()->create(['order_id' => $order->id]);

        $response = $this->deleteJson("/api/orders/{$order->id}", [], $this->authHeader());

        $response->assertStatus(422)
            ->assertJsonPath('error', 'Cannot delete order because it has associated payments.');
    }

    /** @test */
    public function user_cannot_delete_another_users_order(): void
    {
        $otherUser = User::factory()->create();
        $order     = Order::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->deleteJson("/api/orders/{$order->id}", [], $this->authHeader());

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_orders(): void
    {
        $response = $this->getJson('/api/orders');

        $response->assertStatus(401);
    }
}
