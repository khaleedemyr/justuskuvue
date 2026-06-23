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

    'ymsofterp' => [
        'api_base_url' => env('YMSOFTERP_API_URL', 'http://127.0.0.1:8000/api'),
        'cache_enabled' => env('YMSOFTERP_CACHE_ENABLED', env('APP_ENV') === 'production'),
        'cache_ttl_seconds' => (int) env('YMSOFTERP_CACHE_TTL', 600),
        'cache_ttl_short_seconds' => (int) env('YMSOFTERP_CACHE_TTL_SHORT', 300),
        'cache_short_paths' => [
            'mobile/member/whats-on',
            'api/job-vacancies',
        ],
        /** E.164 digits for wa.me (same as Next PUBLIC_RESERVATION_WA / CALL_CENTER_WA) */
        'reservation_call_center_wa' => env('RESERVATION_CALL_CENTER_WA', env('CALL_CENTER_WA', '')),
        'reservation_maintenance_enabled' => env('RESERVATION_MAINTENANCE_ENABLED', true),
        'reservation_maintenance_api_message' => env(
            'RESERVATION_MAINTENANCE_API_MESSAGE',
            'Our online reservation service is temporarily unavailable. Please try again later.'
        ),
        /**
         * Comma-separated allowlist for CMS-driven external links (CTA/menu).
         * Example: justus.co.id,www.justus.co.id,staging.justus.co.id
         */
        'allowed_external_hosts' => array_values(array_filter(array_map(
            static fn ($h) => strtolower(trim((string) $h)),
            explode(',', (string) env('ALLOWED_EXTERNAL_HOSTS', 'justus.co.id,www.justus.co.id,staging.justus.co.id,play.google.com,apps.apple.com'))
        ))),
    ],

    'recaptcha' => [
        // Default uses Google reCAPTCHA test keys; replace in .env for production.
        'site_key' => env('RECAPTCHA_SITE_KEY', '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY', '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'),
        // Adaptive threshold:
        // - score < hard_block_score => block
        // - hard_block_score <= score < medium_risk_score => allow but mark as medium risk
        'hard_block_score' => (float) env('RECAPTCHA_HARD_BLOCK_SCORE', 0.5),
        'medium_risk_score' => (float) env('RECAPTCHA_MEDIUM_RISK_SCORE', 0.7),
    ],

];
