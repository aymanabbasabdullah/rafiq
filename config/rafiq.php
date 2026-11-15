<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Demo Mode (FR-053, ADR-006)
    |--------------------------------------------------------------------------
    |
    | Allows unapproved (Draft) clinical rules to run with a permanent warning
    | banner. It must never be enabled in production; the application refuses
    | to boot if it is.
    |
    */

    'demo_mode' => (bool) env('RAFIQ_DEMO_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | Kill switches (Security-Architecture §4)
    |--------------------------------------------------------------------------
    */

    'assessment_enabled' => (bool) env('RAFIQ_ASSESSMENT_ENABLED', true),

    'ai' => [
        'enabled' => (bool) env('RAFIQ_AI_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Emergency contact (OQ-008)
    |--------------------------------------------------------------------------
    |
    | The emergency number shown to users depends on the target country, which
    | has not been decided yet. While the number is null, the interface tells
    | users to call their local emergency number instead of showing one.
    |
    */

    'emergency' => [
        'country' => env('RAFIQ_EMERGENCY_COUNTRY', 'default'),
        'numbers' => [
            'default' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Public session keys (PRIV-005)
    |--------------------------------------------------------------------------
    |
    | The only keys a public visitor's session may contain. Anything else in a
    | public session is a privacy defect and is caught by the test suite.
    |
    */

    'public_session_keys' => [
        '_token',
        '_previous',
        '_flash',
        'locale',
        'welcomed',
        'age_confirmed',
        'assessment_consent',
        'ai_notice_ack',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensitive request keys (PRIV-009, SEC-013)
    |--------------------------------------------------------------------------
    |
    | Keys whose values must never be written to logs or flashed to the session.
    |
    */

    'sensitive_keys' => [
        'answers',
        'message',
        'history',
        'questions',
        'notes',
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'api_key',
        'secret',
    ],

    /*
    |--------------------------------------------------------------------------
    | Data retention in days (Data-Lifecycle §2)
    |--------------------------------------------------------------------------
    */

    'retention' => [
        'ai_usage_logs_days' => 90,
        'audit_logs_days' => 730,
    ],

];
