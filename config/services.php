<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'instagram' => [
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'webhook_verify_token' => env('WEBHOOK_VERIFY_TOKEN'),
        'graph_api_version' => env('GRAPH_API_VERSION', 'v21.0'),
        'dm_rate_limit' => (int) env('DM_RATE_LIMIT', 190),
        'subscribed_fields' => array_values(array_filter(array_map('trim', explode(',', (string) env(
            'META_SUBSCRIBED_FIELDS',
            'messages,messaging_postbacks,feed'
        ))))),
        'oauth_scopes' => array_values(array_filter(array_map('trim', explode(',', (string) env(
            'META_OAUTH_SCOPES',
            'public_profile,pages_show_list,pages_manage_metadata,pages_messaging,instagram_basic,instagram_manage_messages'
        ))))),
    ],

    'instaflow' => [
        'whatsapp_cta_url' => env('WHATSAPP_CTA_URL', ''),
    ],

];
