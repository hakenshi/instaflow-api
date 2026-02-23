<?php

namespace App\Services;

use App\Models\InstagramConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramApiService
{
    private string $graphApiUrl;

    private int $rateLimit;

    public function __construct()
    {
        $this->graphApiUrl = 'https://graph.facebook.com/'.config('services.instagram.graph_api_version', 'v21.0');
        $this->rateLimit = (int) config('services.instagram.dm_rate_limit', 190);
    }

    /**
     * @return array<string, mixed>
     */
    public function sendTextMessage(InstagramConnection $connection, string $recipientId, string $text): array
    {
        $reservation = app(RateLimitService::class)->reserve((int) $connection->workspace_id);

        if (! ($reservation['success'] ?? false)) {
            return [
                'success' => false,
                'error' => $reservation['error'] ?? ('Rate limit atingido ('.$this->rateLimit.'/hora).'),
                'rate_limited' => (bool) ($reservation['rate_limited'] ?? false),
            ];
        }

        $url = $this->graphApiUrl."/{$connection->page_id}/messages";

        $payload = [
            'recipient' => json_encode(['id' => $recipientId]),
            'message' => json_encode(['text' => $this->processText($text)]),
            'messaging_type' => 'RESPONSE',
            'access_token' => $connection->page_access_token,
        ];

        $response = $this->makeRequest('POST', $url, $payload);

        if (! ($response['success'] ?? false)) {
            app(RateLimitService::class)->release((int) $connection->workspace_id, (string) ($reservation['hour_key'] ?? ''));
        }

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    public function sendMediaMessage(InstagramConnection $connection, string $recipientId, string $mediaUrl, string $text = ''): array
    {
        $reservation = app(RateLimitService::class)->reserve((int) $connection->workspace_id);

        if (! ($reservation['success'] ?? false)) {
            return [
                'success' => false,
                'error' => $reservation['error'] ?? 'Rate limit atingido.',
                'rate_limited' => (bool) ($reservation['rate_limited'] ?? false),
            ];
        }

        $url = $this->graphApiUrl."/{$connection->page_id}/messages";

        $payload = [
            'recipient' => json_encode(['id' => $recipientId]),
            'message' => json_encode([
                'attachment' => [
                    'type' => 'image',
                    'payload' => ['url' => $mediaUrl],
                ],
            ]),
            'messaging_type' => 'RESPONSE',
            'access_token' => $connection->page_access_token,
        ];

        $response = $this->makeRequest('POST', $url, $payload);

        if (($response['success'] ?? false) && $text !== '') {
            $textResponse = $this->sendTextMessage($connection, $recipientId, $text);

            if (! ($textResponse['success'] ?? false)) {
                $textResponse['media_already_sent'] = true;
            }

            return $textResponse;
        }

        if (! ($response['success'] ?? false)) {
            app(RateLimitService::class)->release((int) $connection->workspace_id, (string) ($reservation['hour_key'] ?? ''));
        }

        return $response;
    }

    private function processText(string $text): string
    {
        $replacements = [
            '{{hora}}' => now()->format('H:i'),
            '{{data}}' => now()->format('d/m/Y'),
            '{{dia_semana}}' => $this->weekDayInPortuguese(),
            '{{saudacao}}' => $this->greetingInPortuguese(),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }

    private function greetingInPortuguese(): string
    {
        $hour = (int) now()->format('H');

        if ($hour < 12) {
            return 'Bom dia';
        }

        if ($hour < 18) {
            return 'Boa tarde';
        }

        return 'Boa noite';
    }

    private function weekDayInPortuguese(): string
    {
        $days = ['domingo', 'segunda-feira', 'terca-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sabado'];

        return $days[(int) now()->format('w')];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function makeRequest(string $method, string $url, array $data = []): array
    {
        try {
            $response = Http::timeout(30)
                ->withOptions(['verify' => true])
                ->{$method}($url, $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'http_code' => $response->status(),
                    'data' => $response->json(),
                ];
            }

            $decoded = $response->json();
            $errorMessage = $decoded['error']['message'] ?? 'Erro desconhecido';

            Log::error("InstaFlow API error [{$response->status()}]: {$errorMessage}");

            return [
                'success' => false,
                'error' => $errorMessage,
                'http_code' => $response->status(),
                'data' => $decoded,
            ];
        } catch (\Throwable $exception) {
            Log::error('InstaFlow HTTP client error: '.$exception->getMessage());

            return [
                'success' => false,
                'error' => $exception->getMessage(),
                'http_code' => 0,
            ];
        }
    }
}
