<?php

use Illuminate\Support\Facades\Broadcast;

// Public channel for technician locations — admin panel guards the page
Broadcast::channel('technician-locations', fn() => true);

// Private per-user channel for real-time notifications
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Filament's DatabaseNotificationsSent event broadcasts on this channel
// (derived from str_replace('\\', '.', User::class) . '.' . $user->id)
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
