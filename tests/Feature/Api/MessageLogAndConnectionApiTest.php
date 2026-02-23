<?php

use App\Models\MessageLog;
use App\Models\User;
use App\Models\Workspace;

it('returns numeric stats payload for message logs', function () {
    $workspace = Workspace::query()->create(['name' => 'Workspace Stats']);

    $user = User::factory()->create([
        'workspace_id' => $workspace->id,
        'is_active' => true,
        'meta_user_id' => 'meta-stats-user',
    ]);

    MessageLog::query()->create([
        'workspace_id' => $workspace->id,
        'trigger_id' => null,
        'sender_ig_id' => 'sender-1',
        'sender_username' => 'sender_one',
        'event_type' => 'dm_keyword',
        'incoming_text' => 'preco',
        'response_text' => 'Oi! Segue o valor.',
        'status' => 'sent',
        'error_message' => null,
    ]);

    MessageLog::query()->create([
        'workspace_id' => $workspace->id,
        'trigger_id' => null,
        'sender_ig_id' => 'sender-2',
        'sender_username' => 'sender_two',
        'event_type' => 'comment',
        'incoming_text' => 'me chama',
        'response_text' => 'Te chamei no direct.',
        'status' => 'failed',
        'error_message' => 'Temporary error',
    ]);

    $token = $user->createToken('frontend')->plainTextToken;

    $response = $this
        ->withToken($token)
        ->getJson('/api/v1/message-logs/stats');

    $response->assertSuccessful();

    expect($response->json('today'))->toBeInt();
    expect($response->json('week'))->toBeInt();
    expect($response->json('month'))->toBeInt();

    $byStatus = $response->json('by_status');

    expect($byStatus)->toBeArray();
    expect($byStatus['sent'] ?? null)->toBeInt();
    expect($byStatus['failed'] ?? null)->toBeInt();
});

it('returns disconnected instagram payload when workspace has no connection', function () {
    $workspace = Workspace::query()->create(['name' => 'Workspace Without Connection']);

    $user = User::factory()->create([
        'workspace_id' => $workspace->id,
        'is_active' => true,
        'meta_user_id' => 'meta-no-connection',
    ]);

    $token = $user->createToken('frontend')->plainTextToken;

    $this
        ->withToken($token)
        ->getJson('/api/v1/instagram-connection')
        ->assertSuccessful()
        ->assertJson([
            'connected' => false,
        ]);
});
