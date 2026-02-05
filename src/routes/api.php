<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

// POST endpoints - stricter limit (20 requests/minute)
Route::post('/orders', [OrderController::class, 'store'])
    ->middleware('throttle:20,1');

// GET endpoints - higher limit (60 requests/minute)
Route::get('/orders/{order_id}', [OrderController::class, 'show'])
    ->middleware('throttle:60,1');

Route::get('/health', [HealthController::class, 'check'])
    ->middleware('throttle:60,1');
