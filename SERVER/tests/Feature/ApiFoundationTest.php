<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiFoundationTest extends TestCase
{
    /**
     * Test GET /api/v1/health returns HTTP 200 with standard JSON structure.
     */
    public function test_health_endpoint_returns_200_and_valid_json_structure(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'healthy',
                    'api_version' => 'v1.0.0',
                ],
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'status',
                    'api_version',
                    'timestamp',
                ],
            ]);
    }

    /**
     * Test unknown API endpoint returns HTTP 404 with standard error JSON.
     */
    public function test_non_existent_endpoint_returns_404_standard_json(): void
    {
        $response = $this->getJson('/api/v1/unknown-endpoint');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Resource or endpoint not found',
                'errors' => null,
            ]);
    }

    /**
     * Test health endpoint does not leak any secret, key, or sensitive server info.
     */
    public function test_health_endpoint_does_not_leak_sensitive_configuration(): void
    {
        $response = $this->getJson('/api/v1/health');
        $content = $response->getContent();

        $this->assertStringNotContainsString(config('app.key'), $content);
        $this->assertStringNotContainsString('DB_', $content);
        $this->assertStringNotContainsString('password', strtolower($content));
        $this->assertStringNotContainsString('secret', strtolower($content));
        $this->assertStringNotContainsString('cbt_v1_dev', $content);
        $this->assertStringNotContainsString('127.0.0.1', $content);
    }
}
