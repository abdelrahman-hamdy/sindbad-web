<?php

use Illuminate\Support\Facades\Broadcast;

// Public channel for technician locations — admin panel guards the page
Broadcast::channel('technician-locations', fn() => true);

// Private per-user channel for real-time notifications
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
