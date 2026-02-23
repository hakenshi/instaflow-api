<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookEventDedupe extends Model
{
    protected $table = 'webhook_event_dedupe';

    protected $fillable = [
        'workspace_id',
        'event_key',
        'event_scope',
        'event_ref',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
