<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiIndexTest extends TestCase
{
    public function test_api_root_lists_the_available_resources(): void
    {
        $this->getJson('/api')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure(['resources' => ['hotels', 'rooms', 'reserves', 'restaurants', 'city-graph']]);
    }
}
