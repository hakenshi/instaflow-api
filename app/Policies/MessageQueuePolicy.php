<?php

namespace App\Policies;

use App\Models\MessageQueue;
use App\Models\User;

class MessageQueuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->workspace_id !== null;
    }

    public function view(User $user, MessageQueue $messageQueue): bool
    {
        return $user->is_active && (int) $messageQueue->workspace_id === (int) $user->workspace_id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, MessageQueue $messageQueue): bool
    {
        return false;
    }

    public function delete(User $user, MessageQueue $messageQueue): bool
    {
        return false;
    }

    public function restore(User $user, MessageQueue $messageQueue): bool
    {
        return false;
    }

    public function forceDelete(User $user, MessageQueue $messageQueue): bool
    {
        return false;
    }
}
