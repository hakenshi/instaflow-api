<?php

use App\Models\Trigger;
use App\Models\User;
use App\Models\Workspace;

it('rejects trigger access across workspaces', function () {
    $workspaceA = Workspace::query()->create(['name' => 'Workspace A']);
    $workspaceB = Workspace::query()->create(['name' => 'Workspace B']);

    $userA = User::factory()->create([
        'workspace_id' => $workspaceA->id,
        'is_active' => true,
        'meta_user_id' => 'meta-a',
    ]);

    $triggerB = Trigger::query()->create([
        'workspace_id' => $workspaceB->id,
        'name' => 'Trigger B',
        'type' => 'dm_keyword',
        'keywords' => 'preco',
        'response_text' => 'Resposta B',
        'is_active' => true,
        'match_exact' => false,
    ]);

    $tokenA = $userA->createToken('frontend')->plainTextToken;

    $response = $this
        ->withToken($tokenA)
        ->getJson('/api/v1/triggers/'.$triggerB->id);

    $response->assertForbidden();
});

it('validates trigger payload with form request rules', function () {
    $workspace = Workspace::query()->create(['name' => 'Workspace Validation']);

    $user = User::factory()->create([
        'workspace_id' => $workspace->id,
        'is_active' => true,
        'meta_user_id' => 'meta-validation',
    ]);

    $token = $user->createToken('frontend')->plainTextToken;

    $response = $this
        ->withToken($token)
        ->postJson('/api/v1/triggers', [
            'type' => 'dm_keyword',
        ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'keywords']);
});

it('creates and lists only workspace triggers', function () {
    $workspaceA = Workspace::query()->create(['name' => 'Workspace A']);
    $workspaceB = Workspace::query()->create(['name' => 'Workspace B']);

    $userA = User::factory()->create([
        'workspace_id' => $workspaceA->id,
        'is_active' => true,
        'meta_user_id' => 'meta-user-a',
    ]);

    Trigger::query()->create([
        'workspace_id' => $workspaceB->id,
        'name' => 'Outro Trigger',
        'type' => 'dm_keyword',
        'keywords' => 'fora',
        'response_text' => 'fora workspace',
        'is_active' => true,
        'match_exact' => false,
    ]);

    $tokenA = $userA->createToken('frontend')->plainTextToken;

    $createResponse = $this
        ->withToken($tokenA)
        ->postJson('/api/v1/triggers', [
            'name' => 'Boas-vindas',
            'type' => 'dm_keyword',
            'keywords' => 'oi,ola',
            'response_text' => 'Olá! Como posso ajudar?',
            'is_active' => true,
            'match_exact' => false,
        ]);

    $createResponse
        ->assertCreated()
        ->assertJsonPath('workspace_id', $workspaceA->id);

    $indexResponse = $this
        ->withToken($tokenA)
        ->getJson('/api/v1/triggers?per_page=50');

    $indexResponse->assertSuccessful();

    $workspaceIds = collect($indexResponse->json('data'))
        ->pluck('workspace_id')
        ->unique()
        ->values()
        ->all();

    expect($workspaceIds)->toBe([$workspaceA->id]);
});
