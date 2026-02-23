<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessInstagramWebhookEventJob;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $mode = (string) $request->query('hub_mode', '');
        $token = (string) $request->query('hub_verify_token', '');
        $challenge = (string) $request->query('hub_challenge', '');

        if ($mode === 'subscribe' && hash_equals((string) config('services.instagram.webhook_verify_token'), $token)) {
            return response($challenge, 200);
        }

        return response('Token inválido.', 403);
    }

    public function ingest(Request $request): Response
    {
        $payload = $request->json()->all();

        if (($payload['object'] ?? null) !== 'instagram') {
            return response('Evento inválido.', 422);
        }

        ProcessInstagramWebhookEventJob::dispatch($payload);

        return response('EVENT_RECEIVED', 200);
    }
}
