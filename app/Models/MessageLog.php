<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageLog extends Model
{
    protected $table = 'message_log';

    protected $fillable = [
        'workspace_id',
        'trigger_id',
        'sender_ig_id',
        'sender_username',
        'event_type',
        'incoming_text',
        'response_text',
        'status',
        'error_message',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function trigger(): BelongsTo
    {
        return $this->belongsTo(Trigger::class);
    }

    public static function log(array $data): int
    {
        $log = static::query()->create($data);

        return (int) $log->id;
    }

    /**
     * @return array{today: int, week: int, month: int, by_status: array<string, int>}
     */
    public static function getStats(int $workspaceId): array
    {
        $baseQuery = static::query()->where('workspace_id', $workspaceId);

        $today = (clone $baseQuery)->whereDate('created_at', today())->count();
        $week = (clone $baseQuery)->where('created_at', '>=', now()->subDays(7))->count();
        $month = (clone $baseQuery)->where('created_at', '>=', now()->subDays(30))->count();

        $byStatus = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(static fn ($total): int => (int) $total)
            ->toArray();

        return [
            'today' => $today,
            'week' => $week,
            'month' => $month,
            'by_status' => $byStatus,
        ];
    }

    public static function cleanup(int $keepDays = 30): int
    {
        return static::query()
            ->where('created_at', '<', now()->subDays($keepDays))
            ->delete();
    }
}
