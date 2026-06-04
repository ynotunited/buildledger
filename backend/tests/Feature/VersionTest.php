<?php

namespace Tests\Feature;

use Tests\TestCase;

class VersionTest extends TestCase
{
    public function test_version_endpoint_exposes_current_build_version(): void
    {
        $this->getJson('/api/version')
            ->assertOk()
            ->assertJson([
                'name' => config('app.name'),
                'version' => config('app.version'),
                'environment' => config('app.env'),
            ]);
    }
}
