<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

trait ResolvesAuthenticatedUser
{
    #[Computed]
    public function actor(): User
    {
        $user = Auth::user();

        abort_if(! $user instanceof User, 403);

        return $user->loadMissing('roles');
    }
}
