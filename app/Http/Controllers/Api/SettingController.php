<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpsertSettingRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Setting::class);

        $settings = Setting::query()
            ->where('workspace_id', (int) $request->user()->workspace_id)
            ->orderBy('key_name')
            ->get();

        return response()->json($settings);
    }

    public function upsert(UpsertSettingRequest $request, string $key): JsonResponse
    {
        $workspaceId = (int) $request->user()->workspace_id;

        $setting = Setting::query()
            ->where('workspace_id', $workspaceId)
            ->where('key_name', $key)
            ->first();

        if ($setting) {
            $this->authorize('update', $setting);
            $setting->value = (string) ($request->validated()['value'] ?? '');
            $setting->save();

            return response()->json($setting);
        }

        $this->authorize('create', Setting::class);

        $setting = Setting::query()->create([
            'workspace_id' => $workspaceId,
            'key_name' => $key,
            'value' => (string) ($request->validated()['value'] ?? ''),
        ]);

        return response()->json($setting, 201);
    }
}
