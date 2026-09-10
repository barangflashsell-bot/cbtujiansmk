<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;

class HealthController extends ApiController
{
    /**
     * Check REST API health and basic application status.
     * Unauthenticated public endpoint returning safe, non-sensitive metadata.
     */
    public function index(): JsonResponse
    {
        return $this->successResponse([
            'status' => 'healthy',
            'api_version' => 'v1.0.0',
            'timestamp' => now()->toIso8601String(),
        ], 'CBT REST API is active and healthy');
    }
}
