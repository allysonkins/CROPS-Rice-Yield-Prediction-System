<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;

class LogSuccessfulLogout
{
    public function handle(Logout $event)
    {
        if ($event->user) {
            log_activity('logout', 'User logged out', $event->user);
        }
    }
}