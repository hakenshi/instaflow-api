<?php

use App\Http\Controllers\Auth\MetaAuthController;
use App\Http\Controllers\WebhookController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', function (): JsonResponse {
    return response()->json([
        'name' => config('app.name'),
        'message' => 'Backend API ativo.',
        'frontend_url' => config('app.frontend_url'),
    ]);
})->name('landing');

Route::prefix('/api/v1/auth/instagram')->group(function (): void {
    Route::get('/redirect', [MetaAuthController::class, 'redirect'])->name('auth.meta.redirect');
    Route::get('/callback', [MetaAuthController::class, 'callback'])->name('auth.meta.callback');
});

Route::get('/webhook/instagram', [WebhookController::class, 'verify'])->name('webhook.instagram.verify');
Route::post('/webhook/instagram', [WebhookController::class, 'ingest'])
    ->middleware('meta.webhook')
    ->name('webhook.instagram.ingest');
