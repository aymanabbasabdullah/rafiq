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
    | Demo ruleset (M06-FR-03)
    |--------------------------------------------------------------------------
    |
    | The draft ruleset release that may run outside production while demo
    | mode is on. Production only ever runs a published, approved release.
    |
    */

    'demo_ruleset' => env('RAFIQ_DEMO_RULESET', 'RS-2026.1'),

    /*
    |--------------------------------------------------------------------------
    | Platform default language (ADR-015)
    |--------------------------------------------------------------------------
    |
    | Kept separately from `app.locale`, which Laravel overwrites on every
    | request when App::setLocale() runs. Used for redirects and hreflang x-default.
    |
    */

    'default_locale' => env('APP_LOCALE', 'ar'),

    /*
    |--------------------------------------------------------------------------
    | Kill switches (Security-Architecture §4)
    |--------------------------------------------------------------------------
    */

    'assessment_enabled' => (bool) env('RAFIQ_ASSESSMENT_ENABLED', true),

    'ai' => [
        'enabled' => (bool) env('RAFIQ_AI_ENABLED', false),

        /*
         * Which text generator answers (explicit, never inferred): "gemini" calls Google; "fake" quotes
         * approved content without any network call and is refused in production. A failed Gemini request
         * shows the "unavailable" reply — it never silently falls back to the fake.
         */
        'provider' => env('RAFIQ_AI_PROVIDER', 'gemini'),

        /*
         * Development-only end-to-end test route (POST /{locale}/dev/ai/messages). Registered only in the
         * local and testing environments, answers 404 unless this is true, keeps the age gate, AI notice,
         * CSRF and rate limits, always uses the real Gemini provider, and does not switch the assistant on
         * for visitors. Never enable it on a shared or public server.
         */
        'dev_test_route' => (bool) env('RAFIQ_AI_DEV_TEST_ROUTE', false),

        /*
         * Upper limits (AI-013). The active admin configuration may lower them, never raise them (BR-09-5).
         */
        'limits' => [
            'message_max_chars' => 500,
            'history_max_items' => 6,
            'grounding_chunks' => 4,
            'max_output_tokens' => 600,
            'answer_max_chars' => 1500,
            'per_session_per_minute' => 6,
            'per_ip_per_hour' => 40,
        ],

        'daily_budget_usd' => (float) env('RAFIQ_AI_DAILY_BUDGET_USD', 1.0),

        /*
         * Price per million tokens in USD, copied by hand from the provider's official pricing page
         * when the model is chosen (Gemini-Integration §5). Zero means "not set": cost is then unknown.
         */
        'pricing' => [
            'input_per_million_usd' => (float) env('RAFIQ_AI_PRICE_INPUT', 0),
            'output_per_million_usd' => (float) env('RAFIQ_AI_PRICE_OUTPUT', 0),
        ],
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
