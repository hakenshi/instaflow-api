<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramConnection extends Model
{
    protected $fillable = [
        'workspace_id',
        'meta_user_id',
        'meta_user_name',
        'page_id',
        'page_name',
        'instagram_account_id',
        'instagram_username',
        'user_access_token',
        'page_access_token',
        'scopes',
        'token_expires_at',
        'connected_at',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'user_access_token' => 'encrypted',
            'page_access_token' => 'encrypted',
            'scopes' => 'encrypted:array',
            'token_expires_at' => 'datetime',
            'connected_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
