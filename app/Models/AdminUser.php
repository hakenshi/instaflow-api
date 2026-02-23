<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class AdminUser extends Model
{
    protected $fillable = [
        'username',
        'display_name',
        'password_hash',
        'is_active',
        'last_login_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    protected $hidden = [
        'password_hash',
    ];

    public static function verifyCredentials(string $username, string $password): ?array
    {
        $user = static::where('username', $username)->where('is_active', true)->first();

        if (! $user) {
            return null;
        }

        if (! Hash::check($password, $user->password_hash)) {
            return null;
        }

        return [
            'id' => $user->id,
            'username' => $user->username,
            'display_name' => $user->display_name,
            'source' => 'db',
        ];
    }

    public static function isAvailable(): bool
    {
        return static::count() > 0 || ! empty(config('services.admin.username'));
    }

    public static function ensureDefaultAdminSeeded(): void
    {
        $adminConfig = config('services.admin');

        if (static::count() === 0 && ! empty($adminConfig['username'])) {
            $passwordHash = ! empty($adminConfig['password_hash'])
                ? $adminConfig['password_hash']
                : Hash::make($adminConfig['password']);

            static::create([
                'username' => $adminConfig['username'],
                'display_name' => 'Administrador',
                'password_hash' => $passwordHash,
                'is_active' => true,
            ]);
        }
    }

    public function touchLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    public static function countActive(): int
    {
        return static::where('is_active', true)->count();
    }
}
