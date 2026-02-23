<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyMetaWebhookSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('services.instagram.app_secret');

        if ($secret === '') {
            return response('Segredo do webhook não configurado.', 500);
        }

        $signature = (string) $request->header('X-Hub-Signature-256', '');

        if (! str_starts_with($signature, 'sha256=')) {
            return response('Acesso negado.', 403);
        }

        $providedHash = substr($signature, 7);
        $expectedHash = hash_hmac('sha256', (string) $request->getContent(), $secret);

        if ($providedHash === '' || ! hash_equals(strtolower($expectedHash), strtolower($providedHash))) {
            return response('Acesso negado.', 403);
        }

        return $next($request);
    }
}
