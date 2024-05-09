<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChirpsTest extends TestCase
{
    use RefreshDatabase;

    public function test_chirps_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/chirps');

        $response
            ->assertOk()
            ->assertSeeVolt('chirps.create')
            ->assertSeeVolt('chirps.list');
    }
}
