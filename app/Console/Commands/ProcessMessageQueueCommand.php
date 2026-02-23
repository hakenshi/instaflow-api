<?php

namespace App\Console\Commands;

use App\Jobs\SendInstagramMessageJob;
use App\Models\MessageQueue;
use Illuminate\Console\Command;

class ProcessMessageQueueCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'instaflow:queue:process {--limit=50 : Quantidade máxima de itens a processar}';

    /**
     * @var string
     */
    protected $description = 'Processa fila de mensagens pendentes do InstaFlow';

    public function handle(): int
    {
        $limit = max(1, min(200, (int) $this->option('limit')));

        $items = MessageQueue::query()
            ->where('status', 'pending')
            ->where('next_attempt_at', '<=', now())
            ->orderBy('next_attempt_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($items->isEmpty()) {
            $this->info('Nenhum item pendente para processar.');

            return self::SUCCESS;
        }

        $processed = 0;

        foreach ($items as $item) {
            $item->update(['status' => 'processing', 'locked_at' => now()]);

            try {
                SendInstagramMessageJob::dispatchSync(
                    workspaceId: (int) $item->workspace_id,
                    logId: (int) ($item->source_log_id ?? 0),
                    triggerId: (int) ($item->trigger_id ?? 0),
                    recipientId: (string) $item->recipient_id,
                    eventType: (string) $item->event_type,
                    incomingText: $item->incoming_text,
                    responseText: (string) ($item->response_text ?? ''),
                    responseMediaUrl: $item->response_media_url,
                );
            } catch (\Throwable $exception) {
                $item->update([
                    'status' => 'pending',
                    'locked_at' => null,
                    'next_attempt_at' => now()->addMinutes(5),
                    'last_error' => $exception->getMessage(),
                ]);

                continue;
            }

            $item->refresh();
            $item->attempts = ((int) $item->attempts) + 1;

            $sourceLogStatus = $item->sourceLog?->status;

            if ($sourceLogStatus === 'sent') {
                $item->status = 'sent';
                $item->processed_at = now();
                $item->locked_at = null;
                $item->last_error = null;
                $item->save();
                $processed++;

                continue;
            }

            if ((int) $item->attempts >= (int) $item->max_attempts) {
                $item->status = 'failed';
                $item->processed_at = now();
                $item->locked_at = null;
                $item->next_attempt_at = now();
                $item->save();
                $processed++;

                continue;
            }

            $item->status = 'pending';
            $item->locked_at = null;
            $item->next_attempt_at = now()->addMinutes(5);
            $item->save();
            $processed++;
        }

        $this->info("Fila processada: {$processed} item(ns).");

        return self::SUCCESS;
    }
}
