<?php

use App\Jobs\ProcessInstagramWebhookEventJob;
use Illuminate\Support\Facades\Bus;

it('rejects instagram webhook requests with invalid signature', function () {
    config()->set('services.instagram.app_secret', 'test-secret');

    $response = $this->postJson(route('webhook.instagram.ingest'), [
        'object' => 'instagram',
        'entry' => [],
    ]);

    $response->assertForbidden();
});

it('accepts valid signature and dispatches processing job', function () {
    Bus::fake();

    config()->set('services.instagram.app_secret', 'test-secret');

    $payload = [
        'object' => 'instagram',
        'entry' => [
            [
                'id' => '123456',
                'messaging' => [],
            ],
        ],
    ];

    $rawBody = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $signature = 'sha256='.hash_hmac('sha256', (string) $rawBody, 'test-secret');

    $response = $this->call(
        'POST',
        route('webhook.instagram.ingest'),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ],
        (string) $rawBody
    );

    $response->assertSuccessful();

    Bus::assertDispatched(ProcessInstagramWebhookEventJob::class);
});
