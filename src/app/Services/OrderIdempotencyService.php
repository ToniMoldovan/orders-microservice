<?php

namespace App\Services;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class OrderIdempotencyService
{
    /**
     * Process an order with idempotency handling.
     *
     * @param array<string, mixed> $validated
     * @return array{status: int, order: Order}
     */
    public function processOrder(array $validated): array
    {
        // Normalize the input data
        $normalized = $this->normalize($validated);

        // Compute hash of normalized payload
        $payloadHash = $this->computeHash($normalized);

        // Wrap database operations in a transaction for atomicity
        return DB::transaction(function () use ($normalized, $payloadHash) {
            // Try to find existing order by order_id
            $existingOrder = Order::where('order_id', $normalized['order_id'])->first();

            if ($existingOrder) {
                // Compare stored hash with computed hash
                if ($existingOrder->payload_hash === $payloadHash) {
                    // Same payload - return existing order
                    return ['status' => 200, 'order' => $existingOrder];
                } else {
                    // Different payload - conflict
                    return ['status' => 409, 'order' => $existingOrder];
                }
            }

            // Order doesn't exist - attempt to create
            try {
                $order = Order::create([
                    'order_id' => $normalized['order_id'],
                    'customer_email' => $normalized['customer_email'],
                    'total_amount' => $normalized['total_amount'],
                    'currency' => $normalized['currency'],
                    'order_created_at' => Carbon::parse($normalized['created_at'])->utc(),
                    'payload_hash' => $payloadHash,
                ]);

                return ['status' => 201, 'order' => $order];
            } catch (QueryException $e) {
                // Check if it's a unique constraint violation (SQLSTATE 23000)
                if (isset($e->errorInfo[0]) && $e->errorInfo[0] === '23000') {
                    // Race condition: another request created the order
                    // Re-fetch and compare hashes
                    $existingOrder = Order::where('order_id', $normalized['order_id'])->firstOrFail();

                    if ($existingOrder->payload_hash === $payloadHash) {
                        // Same payload - return existing order
                        return ['status' => 200, 'order' => $existingOrder];
                    } else {
                        // Different payload - conflict
                        return ['status' => 409, 'order' => $existingOrder];
                    }
                }

                // Re-throw if it's not a unique constraint violation
                throw $e;
            }
        });
    }

    /**
     * Find an order by order_id.
     *
     * @param string $order_id
     * @return array{status: int, order: Order|null}
     */
    public function findOrderByOrderId(string $order_id): array
    {
        return DB::transaction(function () use ($order_id) {
            $order = Order::where('order_id', $order_id)->first();

            if (!$order) {
                return ['status' => 404, 'order' => null];
            }

            return ['status' => 200, 'order' => $order];
        });
    }

    /**
     * Normalize the input data deterministically.
     *
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    private function normalize(array $data): array
    {
        return [
            'order_id' => trim($data['order_id']),
            'customer_email' => strtolower(trim($data['customer_email'])),
            'currency' => strtoupper(trim($data['currency'])),
            'total_amount' => number_format((float) $data['total_amount'], 2, '.', ''),
            'created_at' => Carbon::parse($data['created_at'])->utc()->toIso8601String(),
        ];
    }

    /**
     * Compute SHA256 hash of normalized payload.
     *
     * @param array<string, string> $normalized
     * @return string
     */
    private function computeHash(array $normalized): string
    {
        // Create a deterministic JSON representation
        // Sort keys to ensure consistent ordering
        ksort($normalized);
        $json = json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash('sha256', $json);
    }
}
