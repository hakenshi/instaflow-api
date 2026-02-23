<?php

namespace App\Policies;

use App\Models\MessageLog;
use App\Models\User;

class MessageLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->workspace_id !== null;
    }

    public function view(User $user, MessageLog $messageLog): bool
    {
        return $user->is_active && (int) $messageLog->workspace_id === (int) $user->workspace_id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, MessageLog $messageLog): bool
    {
        return false;
    }

    public function delete(User $user, MessageLog $messageLog): bool
    {
        return false;
    }

    public function restore(User $user, MessageLog $messageLog): bool
    {
        return false;
    }

    public function forceDelete(User $user, MessageLog $messageLog): bool
    {
        return false;
    }
}
