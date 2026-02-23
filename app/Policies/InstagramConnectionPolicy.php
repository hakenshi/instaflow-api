<?php

namespace App\Policies;

use App\Models\InstagramConnection;
use App\Models\User;

class InstagramConnectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->workspace_id !== null;
    }

    public function view(User $user, InstagramConnection $instagramConnection): bool
    {
        return $user->is_active && (int) $instagramConnection->workspace_id === (int) $user->workspace_id;
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->workspace_id !== null;
    }

    public function update(User $user, InstagramConnection $instagramConnection): bool
    {
        return $this->view($user, $instagramConnection);
    }

    public function delete(User $user, InstagramConnection $instagramConnection): bool
    {
        return $this->view($user, $instagramConnection);
    }

    public function restore(User $user, InstagramConnection $instagramConnection): bool
    {
        return false;
    }

    public function forceDelete(User $user, InstagramConnection $instagramConnection): bool
    {
        return false;
    }
}
