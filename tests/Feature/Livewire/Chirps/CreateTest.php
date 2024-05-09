<?php

namespace Tests\Feature\Livewire\Chirps;

use App\Models\Chirp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use RefreshDatabase;
    public function test_it_can_render(): void
    {
        $component = Volt::test('chirps.create');

        $component
            ->assertSee('What\'s on your mind?')
            ->assertOk();
    }

    public function test_chirp_can_be_created(): void
    {
        $chirp = Chirp::whereMessage('This is a message')->first();

        $this->assertNull($chirp);

        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('chirps.create')
            ->set('message', 'This is a message')
            ->call('store');

        $component
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $chirp = Chirp::whereMessage('This is a message')->first();

        $this->assertNotNull($chirp);
    }

    public function test_message_validation_works(): void
    {
        Volt::test('chirps.create')
            ->set('message', '')
            ->call('store')
            ->assertHasErrors(['message' => 'required']);

        Volt::test('chirps.create')
        ->set('message', Str::random(256))
        ->call('store')
        ->assertHasErrors(['message' => 'max']);
    }

    public function test_creating_a_chirp_dispatches_event(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Volt::test('chirps.create')
            ->set('message', 'This is a message')
            ->call('store')
            ->assertDispatched('chirp-created');
    }
}
