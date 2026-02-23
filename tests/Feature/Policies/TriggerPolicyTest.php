<?php

use App\Models\Trigger;
use App\Models\User;
use App\Models\Workspace;

it('allows access to triggers only from the same workspace', function () {
    $workspaceA = Workspace::query()->create(['name' => 'Workspace A']);
    $workspaceB = Workspace::query()->create(['name' => 'Workspace B']);

    $userA = User::factory()->create([
        'workspace_id' => $workspaceA->id,
        'is_active' => true,
        'meta_user_id' => 'meta-a',
    ]);

    $userB = User::factory()->create([
        'workspace_id' => $workspaceB->id,
        'is_active' => true,
        'meta_user_id' => 'meta-b',
    ]);

    $triggerA = Trigger::query()->create([
        'workspace_id' => $workspaceA->id,
        'name' => 'Trigger A',
        'type' => 'dm_keyword',
        'keywords' => 'preco',
        'response_text' => 'Resposta A',
        'is_active' => true,
        'match_exact' => false,
    ]);

    expect($userA->can('view', $triggerA))->toBeTrue();
    expect($userB->can('view', $triggerA))->toBeFalse();
    expect($userA->can('update', $triggerA))->toBeTrue();
    expect($userB->can('update', $triggerA))->toBeFalse();
});

it('blocks all trigger actions for inactive users', function () {
    $workspace = Workspace::query()->create(['name' => 'Workspace Inactive']);

    $user = User::factory()->create([
        'workspace_id' => $workspace->id,
        'is_active' => false,
        'meta_user_id' => 'meta-inactive',
    ]);

    $trigger = Trigger::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Trigger',
        'type' => 'dm_keyword',
        'keywords' => 'oi',
        'response_text' => 'Resposta',
        'is_active' => true,
        'match_exact' => false,
    ]);

    expect($user->can('viewAny', Trigger::class))->toBeFalse();
    expect($user->can('view', $trigger))->toBeFalse();
    expect($user->can('create', Trigger::class))->toBeFalse();
});
