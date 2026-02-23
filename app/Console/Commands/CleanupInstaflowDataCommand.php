<?php

namespace App\Console\Commands;

use App\Models\MessageLog;
use App\Models\MessageQueue;
use App\Models\WebhookEventDedupe;
use App\Services\RateLimitService;
use Illuminate\Console\Command;

class CleanupInstaflowDataCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'instaflow:cleanup {--days=30 : Dias de retenção de logs e filas finalizadas}';

    /**
     * @var string
     */
    protected $description = 'Limpa dados antigos do InstaFlow (logs, fila, rate limit e dedupe)';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));

        $deletedLogs = MessageLog::cleanup($days);

        $deletedQueue = MessageQueue::query()
            ->whereIn('status', ['sent', 'failed'])
            ->where('updated_at', '<', now()->subDays($days))
            ->delete();

        $deletedDedupe = WebhookEventDedupe::query()
            ->where('created_at', '<', now()->subDays(14))
            ->delete();

        $deletedRateLimits = app(RateLimitService::class)->cleanup(7);

        $this->info("Limpeza concluída. logs={$deletedLogs}, queue={$deletedQueue}, dedupe={$deletedDedupe}, rate_limits={$deletedRateLimits}");

        return self::SUCCESS;
    }
}
