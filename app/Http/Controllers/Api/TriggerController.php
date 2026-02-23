<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TriggerIndexRequest;
use App\Http\Requests\Api\StoreTriggerRequest;
use App\Http\Requests\Api\UpdateTriggerRequest;
use App\Models\Trigger;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\Response;

class TriggerController extends Controller
{
    private const INDEX_RESPONSE_TYPE = 'array{current_page:int,data:list<\App\Models\Trigger>,first_page_url:string,from:int|null,last_page:int,last_page_url:string,links:list<array{url:string|null,label:string,active:bool}>,next_page_url:string|null,path:string,per_page:int,prev_page_url:string|null,to:int|null,total:int}';

    /**
     * Display a listing of the resource.
     */
    #[ScrambleResponse(status: 200, type: self::INDEX_RESPONSE_TYPE)]
    public function index(TriggerIndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Trigger::class);

        $perPage = (int) ($request->validated()['per_page'] ?? 15);

        /** @var LengthAwarePaginator<Trigger> $triggers */
        $triggers = Trigger::query()
            ->where('workspace_id', (int) $request->user()->workspace_id)
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json($triggers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTriggerRequest $request): JsonResponse
    {
        $this->authorize('create', Trigger::class);

        $trigger = Trigger::query()->create([
            ...$request->validated(),
            'workspace_id' => (int) $request->user()->workspace_id,
            'is_active' => (bool) $request->boolean('is_active', true),
            'match_exact' => (bool) $request->boolean('match_exact', false),
        ]);

        return response()->json($trigger, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Trigger $trigger): JsonResponse
    {
        $this->authorize('view', $trigger);

        return response()->json($trigger);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTriggerRequest $request, Trigger $trigger): JsonResponse
    {
        $this->authorize('update', $trigger);

        $trigger->fill($request->validated());
        $trigger->save();

        return response()->json($trigger->fresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Trigger $trigger): Response
    {
        $this->authorize('delete', $trigger);

        $trigger->delete();

        return response()->noContent();
    }
}
