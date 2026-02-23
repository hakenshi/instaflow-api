<?php

use App\Models\InstagramConnection;
use App\Models\User;
use App\Services\MetaOAuthService;
use Illuminate\Support\Str;

it('redirects to frontend callback with oauth error when state is invalid', function () {
    config()->set('app.frontend_url', 'http://frontend.test');

    $response = $this
        ->withSession(['meta_oauth_state' => 'expected-state'])
        ->get(route('auth.meta.callback', [
            'state' => 'invalid-state',
            'code' => 'oauth-code',
        ]));

    $response->assertRedirectContains('status=error');
    $response->assertRedirectContains('error_code=invalid_state');
});

it('finishes oauth callback and returns a sanctum bearer token', function () {
    config()->set('app.frontend_url', 'http://frontend.test');

    $metaOAuth = \Mockery::mock(MetaOAuthService::class);
    $metaOAuth->shouldReceive('exchangeCodeForUserToken')
        ->once()
        ->with('oauth-code')
        ->andReturn(['access_token' => 'short-token']);
    $metaOAuth->shouldReceive('exchangeForLongLivedUserToken')
        ->once()
        ->with('short-token')
        ->andReturn(['access_token' => 'long-token', 'expires_in' => 3600]);
    $metaOAuth->shouldReceive('fetchMetaUser')
        ->once()
        ->with('long-token')
        ->andReturn([
            'id' => 'meta-123',
            'name' => 'Meta User',
            'picture' => 'https://example.com/avatar.jpg',
        ]);
    $metaOAuth->shouldReceive('fetchInstagramBusinessConnection')
        ->once()
        ->with('long-token')
        ->andReturn([
            'page_id' => 'page-1',
            'page_name' => 'Page Name',
            'page_access_token' => 'page-token',
            'instagram_account_id' => 'ig-1',
            'instagram_username' => 'insta_user',
        ]);
    $metaOAuth->shouldReceive('subscribePageToWebhook')
        ->once()
        ->with('page-1', 'page-token')
        ->andReturnNull();

    $this->app->instance(MetaOAuthService::class, $metaOAuth);

    $response = $this
        ->withSession(['meta_oauth_state' => 'state-123'])
        ->get(route('auth.meta.callback', [
            'state' => 'state-123',
            'code' => 'oauth-code',
        ]));

    $response->assertRedirectContains('status=success');
    $response->assertRedirectContains('token=');

    $location = (string) $response->headers->get('Location');
    parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

    $token = (string) ($query['token'] ?? '');

    expect($token)->not->toBe('');

    $meResponse = $this
        ->withToken($token)
        ->getJson('/api/v1/auth/me');

    $meResponse
        ->assertSuccessful()
        ->assertJsonPath('authenticated', true)
        ->assertJsonPath('user.meta_user_id', 'meta-123');

    $user = User::query()->where('meta_user_id', 'meta-123')->first();

    expect($user)->not->toBeNull();
    expect($user?->workspace_id)->not->toBeNull();
    expect(Str::contains($token, '|'))->toBeTrue();

    $connection = InstagramConnection::query()
        ->where('workspace_id', (int) $user?->workspace_id)
        ->first();

    expect($connection)->not->toBeNull();
});

it('revokes bearer token on logout', function () {
    $user = User::factory()->create([
        'workspace_id' => null,
        'is_active' => true,
        'meta_user_id' => 'meta-logout',
    ]);

    $token = $user->createToken('frontend')->plainTextToken;

    $logoutResponse = $this
        ->withToken($token)
        ->postJson('/api/v1/auth/logout');

    $logoutResponse->assertNoContent();

    $this->assertDatabaseCount('personal_access_tokens', 0);
    app('auth')->forgetGuards();

    $meResponse = $this
        ->withToken($token)
        ->getJson('/api/v1/auth/me');

    $meResponse->assertUnauthorized();
});
