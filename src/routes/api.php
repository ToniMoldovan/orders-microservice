<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders/{order_id}', [OrderController::class, 'show']);
Route::get('/health', [HealthController::class, 'check']);
