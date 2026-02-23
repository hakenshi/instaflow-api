<?php

use App\Http\Controllers\Api\AuthTokenController;
use App\Http\Controllers\Api\InstagramConnectionController;
use App\Http\Controllers\Api\MessageLogController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\TriggerController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', function (): array {
        return [
            'ok' => true,
            'app' => config('app.name'),
            'timestamp' => now()->toIso8601String(),
        ];
    })->name('api.v1.health');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/auth/me', [AuthTokenController::class, 'me'])->name('api.v1.auth.me');
        Route::post('/auth/logout', [AuthTokenController::class, 'logout'])->name('api.v1.auth.logout');

        Route::apiResource('triggers', TriggerController::class);

        Route::get('/settings', [SettingController::class, 'index'])->name('api.v1.settings.index');
        Route::put('/settings/{key}', [SettingController::class, 'upsert'])
            ->where('key', '[a-zA-Z0-9._-]+')
            ->name('api.v1.settings.upsert');

        Route::get('/message-logs', [MessageLogController::class, 'index'])->name('api.v1.message-logs.index');
        Route::get('/message-logs/stats', [MessageLogController::class, 'stats'])->name('api.v1.message-logs.stats');
        Route::get('/message-logs/{messageLog}', [MessageLogController::class, 'show'])->name('api.v1.message-logs.show');

        Route::get('/instagram-connection', [InstagramConnectionController::class, 'show'])->name('api.v1.instagram-connection.show');
        Route::delete('/instagram-connection', [InstagramConnectionController::class, 'destroy'])->name('api.v1.instagram-connection.destroy');
    });
});
