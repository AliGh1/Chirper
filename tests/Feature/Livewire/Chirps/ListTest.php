<?php

namespace Livewire\Chirps;

use App\Models\Chirp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ListTest extends TestCase
{
    use RefreshDatabase;
    public function test_it_can_render(): void
    {
        Volt::test('chirps.list')
            ->assertOk();
    }

    public function test_displays_chirps(): void
    {
        Chirp::factory()->create(['message' => 'On bathing well']);
        Chirp::factory()->create(['message' => 'There\'s no time like bathtime']);

        $user = User::factory()->create();
        $this->actingAs($user);

        Volt::test('chirps.list')
            ->assertSee('On bathing well')
            ->assertSee('There\'s no time like bathtime');
    }

    public function test_displays_all_chirps(): void
    {
        Chirp::factory(7)->create();

        $user = User::factory()->create();

        $this->actingAs($user);

        Volt::test('chirps.list')
            ->assertCount('chirps', 7);
    }

    public function test_chirp_created_event_triggers_get_chirps_method(): void
    {
        Chirp::factory(2)->create();
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Volt::test('chirps.list');

        $component->assertCount('chirps', 2);

        Chirp::factory(3)->create();
        $component->dispatch('chirp-created');

        $component->assertCount('chirps', 5);
    }
}
