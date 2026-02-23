<?php

namespace App\Policies;

use App\Models\Trigger;
use App\Models\User;

class TriggerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->workspace_id !== null;
    }

    public function view(User $user, Trigger $trigger): bool
    {
        return $user->is_active && (int) $trigger->workspace_id === (int) $user->workspace_id;
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->workspace_id !== null;
    }

    public function update(User $user, Trigger $trigger): bool
    {
        return $this->view($user, $trigger);
    }

    public function delete(User $user, Trigger $trigger): bool
    {
        return $this->view($user, $trigger);
    }

    public function restore(User $user, Trigger $trigger): bool
    {
        return false;
    }

    public function forceDelete(User $user, Trigger $trigger): bool
    {
        return false;
    }
}
