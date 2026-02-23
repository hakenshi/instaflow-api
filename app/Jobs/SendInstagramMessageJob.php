<?php

namespace App\Jobs;

use App\Models\InstagramConnection;
use App\Models\MessageLog;
use App\Models\MessageQueue;
use App\Services\InstagramApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendInstagramMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 10;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 120, 240, 480, 900];

    public function __construct(
        public int $workspaceId,
        public int $logId,
        public int $triggerId,
        public string $recipientId,
        public string $eventType,
        public ?string $incomingText,
        public string $responseText,
        public ?string $responseMediaUrl = null
    ) {}

    public function handle(InstagramApiService $api): void
    {
        $log = MessageLog::query()->find($this->logId);

        if (! $log) {
            return;
        }

        $connection = InstagramConnection::query()
            ->where('workspace_id', $this->workspaceId)
            ->first();

        if (! $connection) {
            $log->update([
                'status' => 'failed',
                'error_message' => 'Conexão Instagram não encontrada para o workspace.',
            ]);

            return;
        }

        if (! empty($this->responseMediaUrl)) {
            $result = $api->sendMediaMessage(
                $connection,
                $this->recipientId,
                $this->responseMediaUrl,
                $this->responseText
            );
        } else {
            $result = $api->sendTextMessage($connection, $this->recipientId, $this->responseText);
        }

        if (($result['success'] ?? false) === true) {
            $log->update(['status' => 'sent', 'error_message' => null]);

            return;
        }

        if (! empty($result['rate_limited'])) {
            $log->update([
                'status' => 'rate_limited',
                'error_message' => $result['error'] ?? 'Rate limit atingido.',
            ]);

            MessageQueue::query()->updateOrCreate(
                ['source_log_id' => $this->logId],
                [
                    'workspace_id' => $this->workspaceId,
                    'trigger_id' => $this->triggerId,
                    'recipient_id' => $this->recipientId,
                    'sender_username' => null,
                    'event_type' => $this->eventType,
                    'incoming_text' => $this->incomingText,
                    'response_text' => $this->responseText,
                    'response_media_url' => $this->responseMediaUrl,
                    'status' => 'pending',
                    'attempts' => 0,
                    'max_attempts' => 10,
                    'next_attempt_at' => now()->addMinutes(5),
                    'last_error' => $result['error'] ?? 'Rate limit atingido.',
                ]
            );

            return;
        }

        $log->update([
            'status' => 'failed',
            'error_message' => $result['error'] ?? 'Erro desconhecido no envio.',
        ]);

        $this->fail(new \RuntimeException((string) ($result['error'] ?? 'Falha no envio de DM.')));
    }

    public function failed(\Throwable $exception): void
    {
        $log = MessageLog::query()->find($this->logId);

        if ($log) {
            $log->update(['status' => 'failed', 'error_message' => $exception->getMessage()]);
        }

        Log::error('SendInstagramMessageJob failed: '.$exception->getMessage());
    }
}
