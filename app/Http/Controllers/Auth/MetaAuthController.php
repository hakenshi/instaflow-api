<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\MetaOAuthCallbackRequest;
use App\Models\InstagramConnection;
use App\Models\Setting;
use App\Models\User;
use App\Models\Workspace;
use App\Services\MetaOAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class MetaAuthController extends Controller
{
    public function redirect(Request $request, MetaOAuthService $metaOAuth): RedirectResponse
    {
        $state = Str::random(40);
        $request->session()->put('meta_oauth_state', $state);

        return redirect()->away($metaOAuth->authorizationUrl($state));
    }

    public function callback(MetaOAuthCallbackRequest $request, MetaOAuthService $metaOAuth): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $expectedState = (string) $request->session()->pull('meta_oauth_state', '');
        $incomingState = (string) ($validated['state'] ?? '');
        $code = (string) ($validated['code'] ?? '');

        if ($expectedState === '' || ! hash_equals($expectedState, $incomingState)) {
            return $this->oauthErrorResponse('invalid_state', 'Estado OAuth inválido.');
        }

        if ($code === '') {
            return $this->oauthErrorResponse('missing_code', 'Código OAuth não recebido.');
        }

        try {
            $shortLivedTokenData = $metaOAuth->exchangeCodeForUserToken($code);
            $longLivedTokenData = $metaOAuth->exchangeForLongLivedUserToken((string) $shortLivedTokenData['access_token']);

            $userToken = (string) $longLivedTokenData['access_token'];
            $tokenExpiresAt = isset($longLivedTokenData['expires_in'])
                ? now()->addSeconds((int) $longLivedTokenData['expires_in'])
                : null;

            $metaUser = $metaOAuth->fetchMetaUser($userToken);
            $instagramConnectionData = $metaOAuth->fetchInstagramBusinessConnection($userToken);
            $metaOAuth->subscribePageToWebhook(
                (string) $instagramConnectionData['page_id'],
                (string) $instagramConnectionData['page_access_token']
            );

            $user = DB::transaction(function () use ($metaUser, $userToken, $tokenExpiresAt, $instagramConnectionData): User {
                $metaUserId = (string) $metaUser['id'];
                $fallbackEmail = 'meta_'.$metaUserId.'@instaflow.local';

                $user = User::query()->firstOrNew(['meta_user_id' => $metaUserId]);
                $user->name = (string) $metaUser['name'];
                $user->meta_name = (string) $metaUser['name'];
                $user->avatar_url = (string) ($metaUser['picture'] ?? '');
                $user->email = $user->email !== '' && $user->email !== null ? $user->email : $fallbackEmail;
                $user->password = $user->password ?: Str::password(32, true, true, true, false);
                $user->is_active = true;
                $user->email_verified_at ??= now();
                $user->save();

                $workspace = $user->workspace;

                if (! $workspace) {
                    $workspace = Workspace::query()->create([
                        'name' => $user->name.' Workspace',
                        'slug' => Str::slug($user->name).'-'.Str::lower(Str::random(6)),
                        'owner_user_id' => $user->id,
                    ]);

                    $user->workspace_id = $workspace->id;
                    $user->save();
                } elseif (! $workspace->owner_user_id) {
                    $workspace->owner_user_id = $user->id;
                    $workspace->save();
                }

                InstagramConnection::query()->updateOrCreate(
                    ['workspace_id' => $workspace->id],
                    [
                        'meta_user_id' => $metaUserId,
                        'meta_user_name' => (string) $metaUser['name'],
                        'page_id' => (string) $instagramConnectionData['page_id'],
                        'page_name' => (string) $instagramConnectionData['page_name'],
                        'instagram_account_id' => (string) $instagramConnectionData['instagram_account_id'],
                        'instagram_username' => (string) $instagramConnectionData['instagram_username'],
                        'user_access_token' => $userToken,
                        'page_access_token' => (string) $instagramConnectionData['page_access_token'],
                        'scopes' => config('services.instagram.oauth_scopes', []),
                        'token_expires_at' => $tokenExpiresAt,
                        'connected_at' => now(),
                        'last_synced_at' => now(),
                    ]
                );

                $this->seedWorkspaceDefaults((int) $workspace->id);

                return $user;
            });
        } catch (RuntimeException $exception) {
            return $this->oauthErrorResponse('oauth_runtime', $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);

            return $this->oauthErrorResponse('oauth_failed', 'Falha ao autenticar com o Instagram.');
        }

        $user->tokens()->where('name', 'frontend')->delete();
        $plainTextToken = $user->createToken('frontend', ['*'])->plainTextToken;

        return $this->oauthSuccessResponse($user, $plainTextToken);
    }

    private function oauthSuccessResponse(User $user, string $plainTextToken): RedirectResponse|JsonResponse
    {
        $frontendCallbackUrl = $this->frontendCallbackUrl();

        if ($frontendCallbackUrl === null) {
            return response()->json([
                'status' => 'success',
                'token_type' => 'Bearer',
                'access_token' => $plainTextToken,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'workspace_id' => $user->workspace_id,
                ],
            ]);
        }

        $url = $this->appendQueryString($frontendCallbackUrl, [
            'status' => 'success',
            'token' => $plainTextToken,
        ]);

        return redirect()->away($url);
    }

    private function oauthErrorResponse(string $code, string $message): RedirectResponse|JsonResponse
    {
        $frontendCallbackUrl = $this->frontendCallbackUrl();

        if ($frontendCallbackUrl === null) {
            return response()->json([
                'status' => 'error',
                'error' => [
                    'code' => $code,
                    'message' => $message,
                ],
            ], 422);
        }

        $url = $this->appendQueryString($frontendCallbackUrl, [
            'status' => 'error',
            'error_code' => $code,
            'error_message' => $message,
        ]);

        return redirect()->away($url);
    }

    private function frontendCallbackUrl(): ?string
    {
        $frontendUrl = rtrim((string) config('app.frontend_url', ''), '/');

        if ($frontendUrl === '') {
            return null;
        }

        return $frontendUrl.'/auth/callback';
    }

    /**
     * @param array<string, string> $query
     */
    private function appendQueryString(string $url, array $query): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.http_build_query($query);
    }

    private function seedWorkspaceDefaults(int $workspaceId): void
    {
        $defaults = [
            'welcome_message' => 'Ola! Obrigado por entrar em contato. Como posso te ajudar?',
            'auto_reply_enabled' => '1',
            'log_retention_days' => '30',
        ];

        foreach ($defaults as $key => $value) {
            Setting::query()->updateOrCreate(
                ['workspace_id' => $workspaceId, 'key_name' => $key],
                ['value' => $value]
            );
        }
    }
}
