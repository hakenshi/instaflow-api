<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RateLimit extends Model
{
    protected $table = 'rate_limit';

    protected $fillable = [
        'workspace_id',
        'hour_key',
        'count',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
