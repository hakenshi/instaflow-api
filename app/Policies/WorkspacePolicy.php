<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Workspace $workspace): bool
    {
        return $user->is_active && (int) $user->workspace_id === (int) $workspace->id;
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $user->is_active
            && (int) $user->workspace_id === (int) $workspace->id
            && (int) $workspace->owner_user_id === (int) $user->id;
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return false;
    }

    public function restore(User $user, Workspace $workspace): bool
    {
        return false;
    }

    public function forceDelete(User $user, Workspace $workspace): bool
    {
        return false;
    }
}
