<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HealthController extends Controller
{
    /**
     * Check the health of the API, database, and schema.
     */
    public function check(): JsonResponse
    {
        try {
            // Test database connection
            DB::select('select 1');
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Database connection failed',
            ], 503);
        }

        // Check if orders table exists (schema check)
        if (!Schema::hasTable('orders')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Schema not applied - orders table missing',
            ], 503);
        }

        return response()->json([
            'status' => 'ok',
            'api' => 'ok',
            'db' => 'ok',
            'schema' => 'ok',
        ], 200);
    }
}
