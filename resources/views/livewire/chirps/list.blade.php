<?php

use App\Models\Chirp;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component {
    public Collection $chirps;
    public ?Chirp $editing = null;
    public bool $isFollowingMode = false;

    public function mount(): void
    {
        $this->getChirps();
    }

    #[On('chirp-created')]
    #[On('echo:chirps,ChirpCreated')]
    public function getChirps(): void
    {
        $followingIds = $this->isFollowingMode ?
            auth()->user()->following()->pluck('id')->push(auth()->id())->toArray() :
            [];

        $this->chirps = Chirp::with('user')
            ->when($followingIds, fn($query) => $query->whereIn('user_id', $followingIds))
            ->latest()
            ->get();
    }

    public function edit(Chirp $chirp): void
    {
        $this->editing = $chirp;
        $this->getChirps();
    }

    #[On('chirp-edit-canceled')]
    #[On('chirp-updated')]
    public function disableEditing(): void
    {
        $this->editing = null;
        $this->getChirps();
    }

    public function delete(Chirp $chirp): void
    {
        $this->authorize('delete', $chirp);
        $chirp->delete();
        $this->getChirps();
    }

    public function follow(User $user): void
    {
        auth()->user()->follow($user);
    }

    public function unfollow(User $user): void
    {
        auth()->user()->unfollow($user);
    }

    public function toggleCurrentChirpsMode(): void
    {
        $this->isFollowingMode = !$this->isFollowingMode;
        $this->getChirps();
    }
}; ?>

<div class="mt-6 bg-white shadow-sm rounded-lg divide-y">
    <div class="flex">
        <x-tab-item class="basis-1/2 py-2" :active="!$isFollowingMode" :disabled="!$isFollowingMode"
                    wire:click="toggleCurrentChirpsMode" wire:loading.attr="disabled">{{ __('Everyone') }}</x-tab-item>
        <x-tab-item class="basis-1/2 py-2" :active="$isFollowingMode" :disabled="$isFollowingMode"
                    wire:click="toggleCurrentChirpsMode" wire:loading.attr="disabled">{{ __('Following') }}</x-tab-item>
    </div>

    @foreach ($chirps as $chirp)
        <div class="p-6 flex space-x-2" wire:key="{{ $chirp->id }}"
             wire:loading.class="opacity-60 transition duration-150 ease-in-out" wire:target="toggleCurrentChirpsMode">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600 -scale-x-100" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            <div class="flex-1">
                <div class="flex justify-between items-center">
                    <div>
                        <span class="text-gray-800">{{ $chirp->user->name }}</span>
                        <small
                            class="ml-2 text-sm text-gray-600">{{ $chirp->created_at->format('j M Y, g:i a') }}</small>
                        @unless ($chirp->created_at->eq($chirp->updated_at))
                            <small class="text-sm text-gray-600"> &middot; {{ __('edited') }}</small>
                        @endunless
                    </div>
                    @if ($chirp->user->is(auth()->user()))
                        <x-dropdown>
                            <x-slot name="trigger">
                                <button>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400"
                                         viewBox="0 0 20 20" fill="currentColor">
                                        <path
                                            d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z"/>
                                    </svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link wire:click="edit({{ $chirp->id }})">
                                    {{ __('Edit') }}
                                </x-dropdown-link>
                                <x-dropdown-link wire:click="delete({{ $chirp->id }})"
                                                 wire:confirm="Are you sure to delete this chirp?">
                                    {{ __('Delete') }}
                                </x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    @else
                        @unless(auth()->user()->hasFollowing($chirp->user))
                            <x-outline-success-button wire:click="follow({{ $chirp->user->id }})" wire:key="follow">
                                {{ __('Follow') }}
                            </x-outline-success-button>
                        @else
                            <x-outline-danger-button wire:click="unfollow({{ $chirp->user->id }})"
                                                     wire:confirm="Are you sure to unfollow this user?"
                                                     wire:key="unfollow">
                                {{ __('Unfollow') }}
                            </x-outline-danger-button>
                        @endunless
                    @endif
                </div>
                @if ($chirp->is($editing))
                    <livewire:chirps.edit :chirp="$chirp" :key="$chirp->id"/>
                @else
                    <p class="mt-4 text-lg text-gray-900">{{ $chirp->message }}</p>
                @endif
            </div>
        </div>
    @endforeach
</div>
