<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReadinessTest extends TestCase
{
    public function test_readiness_endpoint_is_available(): void
    {
        $response = $this->getJson('/readyz');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'status',
                'checks' => [
                    'app_key',
                    'app_debug',
                    'https_urls',
                'database',
                'row_level_security',
                'redis_cache',
                'session_cookie',
                'api_gateway',
                    'logs',
                ],
            ]);
    }
}
