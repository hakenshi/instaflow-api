<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InstagramConnection;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InstagramConnectionController extends Controller
{
    private const SHOW_RESPONSE_TYPE = 'array{connected:true,connection:array{id:int,workspace_id:int,meta_user_id:string,meta_user_name:string|null,page_id:string,page_name:string|null,instagram_account_id:string,instagram_username:string|null,scopes:list<string>|null,token_expires_at:string|null,connected_at:string|null,last_synced_at:string|null}}|array{connected:false}';

    #[ScrambleResponse(status: 200, type: self::SHOW_RESPONSE_TYPE)]
    public function show(Request $request): JsonResponse
    {
        $connection = InstagramConnection::query()
            ->where('workspace_id', (int) $request->user()->workspace_id)
            ->first();

        if (! $connection) {
            return response()->json([
                'connected' => false,
            ]);
        }

        $this->authorize('view', $connection);

        return response()->json([
            'connected' => true,
            'connection' => $this->serializeConnection($connection),
        ]);
    }

    public function destroy(Request $request): Response
    {
        $connection = InstagramConnection::query()
            ->where('workspace_id', (int) $request->user()->workspace_id)
            ->first();

        if (! $connection) {
            return response()->noContent();
        }

        $this->authorize('delete', $connection);

        $connection->delete();

        return response()->noContent();
    }

    /**
     * @return array{
     *     id: int,
     *     workspace_id: int,
     *     meta_user_id: string,
     *     meta_user_name: string|null,
     *     page_id: string,
     *     page_name: string|null,
     *     instagram_account_id: string,
     *     instagram_username: string|null,
     *     scopes: array<int, string>|null,
     *     token_expires_at: string|null,
     *     connected_at: string|null,
     *     last_synced_at: string|null
     * }
     */
    private function serializeConnection(InstagramConnection $connection): array
    {
        return [
            'id' => $connection->id,
            'workspace_id' => $connection->workspace_id,
            'meta_user_id' => $connection->meta_user_id,
            'meta_user_name' => $connection->meta_user_name,
            'page_id' => $connection->page_id,
            'page_name' => $connection->page_name,
            'instagram_account_id' => $connection->instagram_account_id,
            'instagram_username' => $connection->instagram_username,
            'scopes' => $connection->scopes ?? [],
            'token_expires_at' => optional($connection->token_expires_at)->toIso8601String(),
            'connected_at' => optional($connection->connected_at)->toIso8601String(),
            'last_synced_at' => optional($connection->last_synced_at)->toIso8601String(),
        ];
    }
}
