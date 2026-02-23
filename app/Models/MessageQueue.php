<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageQueue extends Model
{
    protected $table = 'message_queue';

    protected $fillable = [
        'workspace_id',
        'source_log_id',
        'trigger_id',
        'recipient_id',
        'sender_username',
        'event_type',
        'incoming_text',
        'response_text',
        'response_media_url',
        'status',
        'attempts',
        'max_attempts',
        'next_attempt_at',
        'locked_at',
        'last_error',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'next_attempt_at' => 'datetime',
            'locked_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function trigger(): BelongsTo
    {
        return $this->belongsTo(Trigger::class);
    }

    public function sourceLog(): BelongsTo
    {
        return $this->belongsTo(MessageLog::class, 'source_log_id');
    }
}
