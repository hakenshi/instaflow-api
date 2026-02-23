<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    protected $fillable = [
        'workspace_id',
        'key_name',
        'value',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public static function getValue(int $workspaceId, string $key, ?string $default = null): ?string
    {
        return static::query()
            ->where('workspace_id', $workspaceId)
            ->where('key_name', $key)
            ->value('value') ?? $default;
    }
}
