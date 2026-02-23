<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trigger extends Model
{
    protected $fillable = [
        'workspace_id',
        'name',
        'type',
        'keywords',
        'response_text',
        'response_media_url',
        'is_active',
        'match_exact',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'match_exact' => 'boolean',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function messageLogs(): HasMany
    {
        return $this->hasMany(MessageLog::class);
    }

    public static function findMatch(int $workspaceId, string $text, string $eventType): ?self
    {
        $triggers = static::query()
            ->where('workspace_id', $workspaceId)
            ->where('type', $eventType)
            ->where('is_active', true)
            ->orderByDesc('match_exact')
            ->orderBy('id')
            ->get();

        $textLower = mb_strtolower(trim($text), 'UTF-8');

        foreach ($triggers as $trigger) {
            $keywords = array_map(
                fn ($keyword) => mb_strtolower(trim($keyword), 'UTF-8'),
                explode(',', $trigger->keywords)
            );

            foreach ($keywords as $keyword) {
                if ($keyword === '') {
                    continue;
                }

                if ($trigger->match_exact) {
                    if ($textLower === $keyword) {
                        return $trigger;
                    }

                    continue;
                }

                if (mb_strpos($textLower, $keyword) !== false) {
                    return $trigger;
                }
            }
        }

        return null;
    }

    public static function countByType(int $workspaceId): array
    {
        return static::query()
            ->selectRaw('type, COUNT(*) as total')
            ->where('workspace_id', $workspaceId)
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();
    }
}
