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

    public function test_chirps_edit_visibility(): void
    {
        $chirp = Chirp::factory()->create();
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Volt::test('chirps.list');

        // Initial state: chirps.edit should not be visible
        $component->assertDontSeeVolt('chirps.edit');

        // Edit state: chirps.edit should be visible
        $component->call('edit', $chirp)
            ->assertSeeVolt('chirps.edit');

        // After cancel: chirps.edit should not be visible
        $component->dispatch('chirp-edit-canceled')
            ->assertDontSeeVolt('chirps.edit');

        // Edit state again: chirps.edit should be visible
        $component->call('edit', $chirp)
            ->assertSeeVolt('chirps.edit');

        // After update: chirps.edit should not be visible
        $component->dispatch('chirp-updated')
            ->assertDontSeeVolt('chirps.edit');
    }

    public function test_user_can_follow_and_unfollow_another_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user);
        $component = Volt::test('chirps.list');

        $component->call('follow', $otherUser)
            ->assertOk();

        $this->assertTrue($user->hasFollowing($otherUser));

        $component->call('unfollow', $otherUser)
            ->assertOk();

        $this->assertFalse($user->hasFollowing($otherUser));
    }

    public function test_toggle_current_chirps_mode_and_fetch_chirps(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Chirp::factory(2)->for($user)->create();
        Chirp::factory(3)->for($otherUser)->create();
        Chirp::factory(4)->create();

        $this->actingAs($user);
        $user->follow($otherUser);

        $component = Volt::test('chirps.list');

        // Initial state
        $this->assertFalse($component->get('isFollowingMode'));

        // Toggle to following mode and assert
        $component->call('toggleCurrentChirpsMode')
            ->assertSet('isFollowingMode', true)
            ->assertCount('chirps', 5);

        // Toggle back to everyone mode and assert
        $component->call('toggleCurrentChirpsMode')
            ->assertSet('isFollowingMode', false)
            ->assertCount('chirps', 9);
    }
}
