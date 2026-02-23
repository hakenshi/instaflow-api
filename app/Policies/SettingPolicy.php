<?php

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;

class SettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->workspace_id !== null;
    }

    public function view(User $user, Setting $setting): bool
    {
        return $user->is_active && (int) $setting->workspace_id === (int) $user->workspace_id;
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->workspace_id !== null;
    }

    public function update(User $user, Setting $setting): bool
    {
        return $this->view($user, $setting);
    }

    public function delete(User $user, Setting $setting): bool
    {
        return $this->view($user, $setting);
    }

    public function restore(User $user, Setting $setting): bool
    {
        return false;
    }

    public function forceDelete(User $user, Setting $setting): bool
    {
        return false;
    }
}
