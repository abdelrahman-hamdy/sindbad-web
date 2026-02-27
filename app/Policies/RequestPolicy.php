<?php

namespace App\Policies;

use App\Models\Request;
use App\Models\User;

class RequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Request $request): bool
    {
        return $user->isAdmin()
            || $request->user_id === $user->id
            || $request->technician_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Request $request): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, ?Request $request = null): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, ?Request $request = null): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, ?Request $request = null): bool
    {
        return $user->isAdmin();
    }
}
