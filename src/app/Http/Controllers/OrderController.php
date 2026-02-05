<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Services\OrderIdempotencyService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    /**
     * Store a new order.
     */
    public function store(StoreOrderRequest $request, OrderIdempotencyService $service): JsonResponse
    {
        $result = $service->processOrder($request->validated());

        $order = $result['order'];

        return response()->json([
            'order_id' => $order->order_id,
            'customer_email' => $order->customer_email,
            'total_amount' => $order->total_amount,
            'currency' => $order->currency,
            'created_at' => $order->order_created_at->toIso8601String(),
        ], $result['status']);
    }
}
