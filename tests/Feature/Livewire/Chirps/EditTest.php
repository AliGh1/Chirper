<?php

namespace Livewire\Chirps;

use App\Models\Chirp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;
use Tests\TestCase;

class EditTest extends TestCase
{
    use RefreshDatabase;
    public function test_it_can_render(): void
    {
        $chirp = Chirp::factory()->create();

        Volt::test('chirps.edit', ['chirp' => $chirp])
            ->assertOk()
            ->assertViewHas('chirp', $chirp)
            ->assertViewHas('message', $chirp->message);
    }

    public function test_only_authorized_user_can_edit_chirp(): void
    {
        $chirp = Chirp::factory()->create();
        $owner = $chirp->user;

        $otherUser = User::factory()->create();

        $this->actingAs($otherUser);
        Volt::test('chirps.edit', ['chirp' => $chirp])
            ->call('update')
            ->assertForbidden();

        $this->actingAs($owner);
        Volt::test('chirps.edit', ['chirp' => $chirp])
            ->call('update')
            ->assertDispatched('chirp-updated')
            ->assertOk();
    }

    public function test_message_validation_works(): void
    {
        $chirp = Chirp::factory()->create();
        $this->actingAs($chirp->user);

        Volt::test('chirps.edit', ['chirp' => $chirp])
            ->set('message', '')
            ->call('update')
            ->assertHasErrors(['message' => 'required']);

        Volt::test('chirps.edit', ['chirp' => $chirp])
            ->set('message', Str::random(256))
            ->call('update')
            ->assertHasErrors(['message' => 'max']);
    }

    public function test_cancel_action_dispatches_chirp_edit_canceled_event(): void
    {
        $chirp = Chirp::factory()->create();

        Volt::test('chirps.edit', ['chirp' => $chirp])
            ->call('cancel')
            ->assertDispatched('chirp-edit-canceled')
            ->assertOk();
    }
}
