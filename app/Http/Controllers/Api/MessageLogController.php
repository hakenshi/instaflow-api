<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MessageLogIndexRequest;
use App\Models\MessageLog;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class MessageLogController extends Controller
{
    private const INDEX_RESPONSE_TYPE = 'array{current_page:int,data:list<\App\Models\MessageLog>,first_page_url:string,from:int|null,last_page:int,last_page_url:string,links:list<array{url:string|null,label:string,active:bool}>,next_page_url:string|null,path:string,per_page:int,prev_page_url:string|null,to:int|null,total:int}';

    private const STATS_RESPONSE_TYPE = 'array{today:int,week:int,month:int,by_status:array<string,int>}';

    #[ScrambleResponse(status: 200, type: self::INDEX_RESPONSE_TYPE)]
    public function index(MessageLogIndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', MessageLog::class);

        $perPage = (int) ($request->validated()['per_page'] ?? 20);

        /** @var LengthAwarePaginator<MessageLog> $logs */
        $logs = MessageLog::query()
            ->with(['trigger:id,name'])
            ->where('workspace_id', (int) $request->user()->workspace_id)
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json($logs);
    }

    public function show(MessageLog $messageLog): JsonResponse
    {
        $this->authorize('view', $messageLog);

        $messageLog->loadMissing(['trigger:id,name']);

        return response()->json($messageLog);
    }

    #[ScrambleResponse(status: 200, type: self::STATS_RESPONSE_TYPE)]
    public function stats(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MessageLog::class);

        $stats = MessageLog::getStats((int) $request->user()->workspace_id);

        return response()->json($stats);
    }
}
