<?php

namespace App\Providers;

use App\Models\InstagramConnection;
use App\Models\MessageLog;
use App\Models\MessageQueue;
use App\Models\Setting;
use App\Models\Trigger;
use App\Models\WebhookEventDedupe;
use App\Models\Workspace;
use App\Policies\InstagramConnectionPolicy;
use App\Policies\MessageLogPolicy;
use App\Policies\MessageQueuePolicy;
use App\Policies\SettingPolicy;
use App\Policies\TriggerPolicy;
use App\Policies\WebhookEventDedupePolicy;
use App\Policies\WorkspacePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Workspace::class, WorkspacePolicy::class);
        Gate::policy(InstagramConnection::class, InstagramConnectionPolicy::class);
        Gate::policy(Trigger::class, TriggerPolicy::class);
        Gate::policy(Setting::class, SettingPolicy::class);
        Gate::policy(MessageLog::class, MessageLogPolicy::class);
        Gate::policy(MessageQueue::class, MessageQueuePolicy::class);
        Gate::policy(WebhookEventDedupe::class, WebhookEventDedupePolicy::class);
    }
}
