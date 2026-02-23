<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Workspace extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'owner_user_id',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function instagramConnection(): HasOne
    {
        return $this->hasOne(InstagramConnection::class);
    }

    public function triggers(): HasMany
    {
        return $this->hasMany(Trigger::class);
    }

    public function messageLogs(): HasMany
    {
        return $this->hasMany(MessageLog::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }

    public function rateLimits(): HasMany
    {
        return $this->hasMany(RateLimit::class);
    }

    public function dedupeEvents(): HasMany
    {
        return $this->hasMany(WebhookEventDedupe::class);
    }

    public function messageQueueItems(): HasMany
    {
        return $this->hasMany(MessageQueue::class);
    }
}
