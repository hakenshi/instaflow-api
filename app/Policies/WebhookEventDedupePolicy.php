<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WebhookEventDedupe;

class WebhookEventDedupePolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, WebhookEventDedupe $webhookEventDedupe): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, WebhookEventDedupe $webhookEventDedupe): bool
    {
        return false;
    }

    public function delete(User $user, WebhookEventDedupe $webhookEventDedupe): bool
    {
        return false;
    }

    public function restore(User $user, WebhookEventDedupe $webhookEventDedupe): bool
    {
        return false;
    }

    public function forceDelete(User $user, WebhookEventDedupe $webhookEventDedupe): bool
    {
        return false;
    }
}
