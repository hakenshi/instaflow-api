<?php

namespace App\Policies;

use App\Models\AdminUser;
use App\Models\User;

class AdminUserPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, AdminUser $target): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AdminUser $target): bool
    {
        return false;
    }

    public function delete(User $user, AdminUser $target): bool
    {
        return false;
    }
}
