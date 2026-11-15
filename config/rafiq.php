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
    | User Journeys (Module-02 §13)
    |--------------------------------------------------------------------------
    |
    | Controls which journeys (J1–J4) are visible on selection cards and
    | reachable by URL. Disabling a journey hides its card and returns 404.
    |
    */

    'journeys' => [
        'enabled' => [
            'learn' => (bool) env('RAFIQ_JOURNEY_LEARN_ENABLED', true),
            'concern' => (bool) env('RAFIQ_JOURNEY_CONCERN_ENABLED', true),
            'conception' => (bool) env('RAFIQ_JOURNEY_CONCEPTION_ENABLED', true),
            'doctor_prep' => (bool) env('RAFIQ_JOURNEY_DOCTOR_PREP_ENABLED', true),
        ],
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
    | Quick Exit (FR-005, OQ-033)
    |--------------------------------------------------------------------------
    |
    | Neutral page the Quick Exit button replaces Rafiq with. Pending a final
    | choice by the product owner.
    |
    */

    'quick_exit_url' => env('RAFIQ_QUICK_EXIT_URL', 'https://www.wikipedia.org/'),

    /*
    |--------------------------------------------------------------------------
    | Seconds before the welcome screen moves on by itself (FR-001)
    |--------------------------------------------------------------------------
    */

    'welcome_auto_advance_seconds' => 4,

    /*
    |--------------------------------------------------------------------------
    | Last update of the privacy and terms pages (PRIV-019)
    |--------------------------------------------------------------------------
    */

    'legal_updated_at' => '2026-11-15',

    /*
    |--------------------------------------------------------------------------
    | Reference sources shown on the public Sources page
    |--------------------------------------------------------------------------
    |
    | Mirrors docs/04-clinical/Medical-Sources-Register.md. These are the
    | references Rafiq's draft content is checked against; listing them does
    | not mean the content has been medically approved.
    |
    */

    'sources' => [
        ['id' => 'SRC-001', 'title' => 'Testicle pain', 'publisher' => 'NHS (UK)', 'url' => 'https://www.nhs.uk/conditions/testicle-pain/', 'date' => '2025-06-26'],
        ['id' => 'SRC-002', 'title' => 'Testicle lumps and swellings', 'publisher' => 'NHS (UK)', 'url' => 'https://www.nhs.uk/symptoms/testicle-lumps-and-swellings/', 'date' => '2023-07-18'],
        ['id' => 'SRC-003', 'title' => 'EAU Guidelines on Sexual and Reproductive Health — Male Infertility', 'publisher' => 'European Association of Urology', 'url' => 'https://uroweb.org/guidelines/sexual-and-reproductive-health/chapter/male-infertility', 'date' => '2026'],
        ['id' => 'SRC-004', 'title' => 'Diagnosis and Treatment of Infertility in Men: AUA/ASRM Guideline (2024)', 'publisher' => 'American Urological Association / ASRM', 'url' => 'https://www.auanet.org/guidelines-and-quality/guidelines/male-infertility', 'date' => '2024'],
        ['id' => 'SRC-005', 'title' => 'Guideline for the prevention, diagnosis and treatment of infertility', 'publisher' => 'World Health Organization', 'url' => 'https://www.who.int/news/item/28-11-2025-who-issues-first-global-guideline-on-infertility', 'date' => '2025-11-28'],
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
        'journeys_selected',
        // Inertia's one-shot flags (e.g. clear_history after "Clear everything").
        'inertia',
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
