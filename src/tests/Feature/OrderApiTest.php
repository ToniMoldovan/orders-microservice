<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test payload for creating orders.
     *
     * @return array<string, mixed>
     */
    private function validOrderPayload(): array
    {
        return [
            'order_id' => 'ORD-TEST-001',
            'customer_email' => 'test@example.com',
            'total_amount' => 99.99,
            'currency' => 'EUR',
            'created_at' => '2026-02-05T12:00:00Z',
        ];
    }

    /**
     * Test POST /api/orders with valid payload returns 201.
     */
    public function test_store_order_returns_201(): void
    {
        $payload = $this->validOrderPayload();

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'order_id',
                'customer_email',
                'total_amount',
                'currency',
                'created_at',
            ])
            ->assertJson([
                'order_id' => 'ORD-TEST-001',
                'customer_email' => 'test@example.com',
                'total_amount' => '99.99',
                'currency' => 'EUR',
            ]);
    }

    /**
     * Test POST /api/orders with same payload twice returns 200 on second call (idempotency).
     */
    public function test_store_duplicate_order_same_payload_returns_200(): void
    {
        $payload = $this->validOrderPayload();

        // First POST should return 201
        $firstResponse = $this->postJson('/api/orders', $payload);
        $firstResponse->assertStatus(201);

        // Second POST with same payload should return 200 (idempotent)
        $secondResponse = $this->postJson('/api/orders', $payload);
        $secondResponse->assertStatus(200)
            ->assertJson([
                'order_id' => 'ORD-TEST-001',
                'customer_email' => 'test@example.com',
                'total_amount' => '99.99',
                'currency' => 'EUR',
            ]);
    }

    /**
     * Test POST /api/orders with same order_id but different payload returns 409 (conflict).
     */
    public function test_store_duplicate_order_different_payload_returns_409(): void
    {
        $payload = $this->validOrderPayload();

        // First POST should return 201
        $firstResponse = $this->postJson('/api/orders', $payload);
        $firstResponse->assertStatus(201);

        // Second POST with same order_id but different total_amount should return 409
        $conflictingPayload = array_merge($payload, [
            'total_amount' => 199.99, // Different amount
        ]);

        $conflictResponse = $this->postJson('/api/orders', $conflictingPayload);
        $conflictResponse->assertStatus(409);
    }

    /**
     * Test GET /api/orders/{order_id} with existing order returns 200.
     */
    public function test_show_existing_order_returns_200(): void
    {
        $payload = $this->validOrderPayload();

        // Create an order first
        $createResponse = $this->postJson('/api/orders', $payload);
        $createResponse->assertStatus(201);

        // Retrieve the order
        $getResponse = $this->getJson('/api/orders/ORD-TEST-001');

        $getResponse->assertStatus(200)
            ->assertJsonStructure([
                'order_id',
                'customer_email',
                'total_amount',
                'currency',
                'created_at',
            ])
            ->assertJson([
                'order_id' => 'ORD-TEST-001',
                'customer_email' => 'test@example.com',
                'total_amount' => '99.99',
                'currency' => 'EUR',
            ]);
    }

    /**
     * Test GET /api/orders/{order_id} with non-existent order returns 404.
     */
    public function test_show_missing_order_returns_404(): void
    {
        $response = $this->getJson('/api/orders/NON-EXISTENT-ORDER-ID');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Order not found',
            ]);
    }

    /**
     * Test GET /api/health returns 200.
     */
    public function test_health_endpoint_returns_200(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'api',
                'db',
                'schema',
            ])
            ->assertJson([
                'status' => 'ok',
                'api' => 'ok',
                'db' => 'ok',
                'schema' => 'ok',
            ]);
    }
}
