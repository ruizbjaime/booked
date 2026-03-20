<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class RecordLastLogin
{
    public function handle(Login $event): void
    {
        $event->user->update(['last_login_at' => now()]);
    }
}
