<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MetaOAuthService
{
    public function authorizationUrl(string $state): string
    {
        $query = http_build_query([
            'client_id' => config('services.instagram.app_id'),
            'redirect_uri' => route('auth.meta.callback'),
            'response_type' => 'code',
            'scope' => implode(',', config('services.instagram.oauth_scopes', [])),
            'state' => $state,
        ]);

        return 'https://www.facebook.com/'.config('services.instagram.graph_api_version', 'v21.0').'/dialog/oauth?'.$query;
    }

    /**
     * @return array{access_token: string, token_type?: string, expires_in?: int}
     */
    public function exchangeCodeForUserToken(string $code): array
    {
        $response = Http::asForm()
            ->timeout(30)
            ->post('https://graph.facebook.com/'.config('services.instagram.graph_api_version', 'v21.0').'/oauth/access_token', [
                'client_id' => config('services.instagram.app_id'),
                'client_secret' => config('services.instagram.app_secret'),
                'redirect_uri' => route('auth.meta.callback'),
                'code' => $code,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Falha ao trocar código OAuth por token.');
        }

        /** @var array{access_token: string, token_type?: string, expires_in?: int} $payload */
        $payload = $response->json();

        if (! isset($payload['access_token'])) {
            throw new RuntimeException('Token OAuth inválido retornado pelo Meta.');
        }

        return $payload;
    }

    /**
     * @return array{access_token: string, token_type?: string, expires_in?: int}
     */
    public function exchangeForLongLivedUserToken(string $shortLivedToken): array
    {
        $response = Http::asForm()
            ->timeout(30)
            ->get('https://graph.facebook.com/'.config('services.instagram.graph_api_version', 'v21.0').'/oauth/access_token', [
                'grant_type' => 'fb_exchange_token',
                'client_id' => config('services.instagram.app_id'),
                'client_secret' => config('services.instagram.app_secret'),
                'fb_exchange_token' => $shortLivedToken,
            ]);

        if (! $response->successful()) {
            return ['access_token' => $shortLivedToken];
        }

        /** @var array{access_token: string, token_type?: string, expires_in?: int} $payload */
        $payload = $response->json();

        if (! isset($payload['access_token'])) {
            return ['access_token' => $shortLivedToken];
        }

        return $payload;
    }

    /**
     * @return array{id: string, name: string, picture?: string}
     */
    public function fetchMetaUser(string $userToken): array
    {
        $response = Http::withToken($userToken)
            ->timeout(30)
            ->get('https://graph.facebook.com/'.config('services.instagram.graph_api_version', 'v21.0').'/me', [
                'fields' => 'id,name,picture{url}',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Não foi possível obter dados do usuário Meta.');
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->json();

        return [
            'id' => (string) Arr::get($payload, 'id'),
            'name' => (string) Arr::get($payload, 'name', 'Usuário Meta'),
            'picture' => (string) Arr::get($payload, 'picture.data.url', ''),
        ];
    }

    /**
     * @return array{
     *     page_id: string,
     *     page_name: string,
     *     page_access_token: string,
     *     instagram_account_id: string,
     *     instagram_username: string
     * }
     */
    public function fetchInstagramBusinessConnection(string $userToken): array
    {
        $pagesResponse = Http::withToken($userToken)
            ->timeout(30)
            ->get('https://graph.facebook.com/'.config('services.instagram.graph_api_version', 'v21.0').'/me/accounts', [
                'fields' => 'id,name,access_token',
                'limit' => 100,
            ]);

        if (! $pagesResponse->successful()) {
            throw new RuntimeException('Não foi possível listar páginas vinculadas.');
        }

        $pages = $pagesResponse->json('data', []);

        foreach ($pages as $page) {
            $pageId = (string) ($page['id'] ?? '');
            $pageToken = (string) ($page['access_token'] ?? '');

            if ($pageId === '' || $pageToken === '') {
                continue;
            }

            $pageDetailResponse = Http::withToken($pageToken)
                ->timeout(30)
                ->get('https://graph.facebook.com/'.config('services.instagram.graph_api_version', 'v21.0')."/{$pageId}", [
                    'fields' => 'id,name,instagram_business_account{id,username}',
                ]);

            if (! $pageDetailResponse->successful()) {
                continue;
            }

            $pageDetail = $pageDetailResponse->json();
            $igAccountId = (string) Arr::get($pageDetail, 'instagram_business_account.id', '');

            if ($igAccountId === '') {
                continue;
            }

            return [
                'page_id' => $pageId,
                'page_name' => (string) Arr::get($pageDetail, 'name', $page['name'] ?? 'Página'),
                'page_access_token' => $pageToken,
                'instagram_account_id' => $igAccountId,
                'instagram_username' => (string) Arr::get($pageDetail, 'instagram_business_account.username', ''),
            ];
        }

        throw new RuntimeException('Nenhuma conta Instagram Business/Creator vinculada foi encontrada.');
    }

    public function subscribePageToWebhook(string $pageId, string $pageAccessToken): void
    {
        $subscribedFields = implode(',', config('services.instagram.subscribed_fields', []));

        $response = Http::asForm()
            ->timeout(30)
            ->post('https://graph.facebook.com/'.config('services.instagram.graph_api_version', 'v21.0')."/{$pageId}/subscribed_apps", [
                'subscribed_fields' => $subscribedFields,
                'access_token' => $pageAccessToken,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Falha ao registrar assinatura de webhook da pagina.');
        }
    }
}
