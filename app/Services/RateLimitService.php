<?php

namespace App\Services;

use App\Models\RateLimit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RateLimitService
{
    private int $dmRateLimit;

    public function __construct()
    {
        $this->dmRateLimit = (int) config('services.instagram.dm_rate_limit', 190);
    }

    /**
     * @return array<string, bool|int|string>
     */
    public function reserve(int $workspaceId): array
    {
        $hourKey = Carbon::now()->format('Y-m-d-H');

        try {
            DB::beginTransaction();

            RateLimit::query()->updateOrCreate(
                ['workspace_id' => $workspaceId, 'hour_key' => $hourKey],
                []
            );

            $record = RateLimit::query()
                ->where('workspace_id', $workspaceId)
                ->where('hour_key', $hourKey)
                ->lockForUpdate()
                ->first();

            $currentCount = $record?->count ?? 0;

            if ($currentCount >= $this->dmRateLimit) {
                DB::rollBack();

                return [
                    'success' => false,
                    'rate_limited' => true,
                    'workspace_id' => $workspaceId,
                    'hour_key' => $hourKey,
                    'count' => $currentCount,
                    'remaining' => 0,
                    'error' => 'Rate limit atingido ('.$this->dmRateLimit.'/hora).',
                ];
            }

            RateLimit::query()
                ->where('workspace_id', $workspaceId)
                ->where('hour_key', $hourKey)
                ->increment('count');

            $newCount = $currentCount + 1;
            DB::commit();

            return [
                'success' => true,
                'workspace_id' => $workspaceId,
                'hour_key' => $hourKey,
                'count' => $newCount,
                'remaining' => max(0, $this->dmRateLimit - $newCount),
            ];
        } catch (\Throwable $exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error('InstaFlow rate limit reserve error: '.$exception->getMessage());

            return [
                'success' => false,
                'rate_limited' => false,
                'workspace_id' => $workspaceId,
                'hour_key' => $hourKey,
                'error' => 'Falha ao reservar slot de rate limit.',
            ];
        }
    }

    public function release(int $workspaceId, string $hourKey): void
    {
        if ($hourKey === '') {
            return;
        }

        try {
            RateLimit::query()
                ->where('workspace_id', $workspaceId)
                ->where('hour_key', $hourKey)
                ->where('count', '>', 0)
                ->decrement('count');
        } catch (\Throwable $exception) {
            Log::error('InstaFlow rate limit release error: '.$exception->getMessage());
        }
    }

    public function getRemaining(int $workspaceId): int
    {
        $hourKey = Carbon::now()->format('Y-m-d-H');

        $currentCount = (int) RateLimit::query()
            ->where('workspace_id', $workspaceId)
            ->where('hour_key', $hourKey)
            ->value('count');

        return max(0, $this->dmRateLimit - $currentCount);
    }

    public function cleanup(int $keepDays = 7): int
    {
        return RateLimit::query()
            ->where('updated_at', '<', now()->subDays(max(1, $keepDays)))
            ->delete();
    }
}
