# هيكل التطبيق (Application Structure)

> الحالة: Draft · آخر تحديث: 2026-11-15

## 1. الحالة الحالية للمشروع (تم فحصها في 2026-11-15)

| العنصر | الحالة الفعلية |
|---|---|
| Laravel | `laravel/framework` v13.42.0 (`^13.17`)، هيكل skeleton افتراضي |
| PHP | 8.3.16 (Laragon) — الامتدادات: `intl`, `mbstring`, `openssl`, `sodium`, `pdo_mysql`, `pdo_sqlite` متوفرة |
| Composer / Node / npm | 2.11.2 / 26.13.1 / 11.20.0 |
| حزم PHP | `laravel/tinker`؛ dev: `faker`, `pail`, `pao`, `pint`, `mockery`, `collision`, `phpunit ^12.5` · `league/commonmark` و`guzzlehttp/guzzle` متوفران كتبعيات |
| حزم JS | `vite ^8`, `laravel-vite-plugin ^3.1`, `tailwindcss ^4`, `@tailwindcss/vite`, `concurrently` — **لا React، لا Inertia، لا TypeScript** |
| `app/` | `Http/Controllers/Controller.php`, `Models/User.php`, `Providers/AppServiceProvider.php` فقط |
| `resources/` | `views/welcome.blade.php`, `js/app.js` (فارغ تقريباً), `css/app.css` (Tailwind 4 + `@theme` بخط Instrument Sans) |
| `routes/` | `web.php` (`/` ⇒ welcome view)، `console.php` |
| `database/` | migrations افتراضية: `users` + `password_reset_tokens` + `sessions` (مع `ip_address`, `user_agent`)، `cache`, `jobs`؛ `database.sqlite` موجود |
| الإعداد (قيم غير سرية) | `DB_CONNECTION=sqlite`, `SESSION_DRIVER=database`, `SESSION_ENCRYPT=false`, `SESSION_LIFETIME=120`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database`, `APP_LOCALE=en` |
| الاختبارات | PHPUnit؛ `phpunit.xml` يستخدم SQLite `:memory:`؛ أمثلة فقط |
| `CLAUDE.md`/`AGENTS.md` | متطابقان؛ يطلبان تثبيت Laravel Boost |
| `.claude/mcp.json` | يعرّف خادم `laravel-boost` (`php artisan boost:mcp`) — لن يعمل قبل تثبيت Boost |
| `.npmrc` | `ignore-scripts=true`, `audit=true` |
| Git | commit واحد "Initial commit" على `main`؛ مجلدات `.claude/*` غير متتبعة |

## 2. الفجوات التقنية (Technical Gaps)

لا يُثبَّت أي منها في هذه المرحلة. الإصدارات الدقيقة تُحدد عند التنفيذ بعد التحقق من توافقها مع Laravel 13.

| # | الفجوة | الحزمة/الإجراء المقترح | Module | الأولوية |
|---|---|---|---|---|
| GAP-01 | Laravel Boost غير مثبت (مطلوب في CLAUDE.md) | `laravel/boost` (dev) + `boost:install` | M00 | Must |
| GAP-02 | Inertia server adapter | `inertiajs/inertia-laravel` + `HandleInertiaRequests` middleware + root view `app.blade.php` | M00 | Must |
| GAP-03 | React + Inertia client | `react`, `react-dom`, `@inertiajs/react`, `@vitejs/plugin-react` | M00 | Must |
| GAP-04 | TypeScript | `typescript`, `@types/react`, `@types/react-dom`, `tsconfig.json` (strict) | M00 | Must |
| GAP-05 | Lint/format للواجهة | `eslint` + قواعد React/TS + قاعدة منع النصوص الحرفية + قاعدة منع الأصناف الفيزيائية؛ `prettier` | M00 | Must |
| GAP-06 | مصادقة الأدمن و2FA | `laravel/fortify` | M11-A | Must |
| GAP-07 | قاعدة بيانات الإنتاج | MySQL 8.4 (ADR-002) | M00 | Must |
| GAP-08 | جلسات بلا IP | Redis/file + تشفير (ADR-004) | M00 | Must |
| GAP-09 | مكونات واجهة قابلة للوصول | Radix primitives / shadcn-style، `lucide-react` | M00/M01 | Should |
| GAP-10 | خطوط عربية/لاتينية | خط عربي (مثلاً IBM Plex Sans Arabic أو Noto Kufi/Naskh Arabic) + لاتيني متناسق — يُختار في التصميم (OQ-015) | M01 | Must |
| GAP-11 | فحص الوصول | `@axe-core/*` أو Lighthouse CI | M12 | Must |
| GAP-12 | اختبار E2E (اختياري) | Playwright **أو** `laravel/dusk` — مهارة `testing-best-practices` تنص على عدم إضافة متصفح اختبار دون طلب المالك (OQ-027) | M12 | Should |
| GAP-13 | CI | GitHub Actions (أو ما يعادله): Pint، PHPUnit (SQLite + MySQL)، `tsc --noEmit`، ESLint، build، `composer audit`، `npm audit` | M00 | Must |
| GAP-14 | Gemini | لا حزمة مطلوبة (Laravel HTTP Client) | M09 | Must |
| GAP-15 | ترميز قاعدة البيانات العربية | `utf8mb4` + FULLTEXT ngram | M00/M08 | Must |

## 3. الهيكل المستهدف — Backend

```text
app/
├── Enums/
│   ├── Locale.php                 # ar, en
│   ├── Journey.php                # learn, concern, conception, doctor_prep
│   ├── Priority.php               # P0..P4 (+ compare/max)
│   ├── ReviewStatus.php           # draft, pending_medical_review, approved, retired
│   ├── PublishStatus.php          # draft, published, hidden, archived
│   ├── AdminRole.php              # super_admin, content_editor, medical_reviewer
│   └── AiOutcome.php              # answered, refused_out_of_scope, blocked_prefilter, blocked_redflag, blocked_postfilter, rate_limited, budget_exceeded, error
├── Http/
│   ├── Controllers/
│   │   ├── Public/                # WelcomeController, LandingController, JourneyController, GateController,
│   │   │                          # AssessmentController, KnowledgeController, GlossaryController,
│   │   │                          # DoctorPrepController, AiMessageController, LegalPageController, SessionController
│   │   └── Admin/                 # DashboardController, ArticleController, ArticleReviewController, SourceController,
│   │                              # GlossaryTermController, DoctorQuestionController, ClinicalRuleController,
│   │                              # RulesetReleaseController, GuidanceTextController, AiConfigurationController,
│   │                              # AdminUserController, AnalyticsController, AuditLogController
│   ├── Middleware/                # SetLocale, SecurityHeaders, NoStoreCache, EnsureAgeConfirmed,
│   │                              # EnsureAssessmentConsent, EnsureAiNoticeAcknowledged, HandleInertiaRequests
│   └── Requests/{Public,Admin}/   # Form Requests
├── Models/                        # User, Role, Article, ArticleTranslation, ContentCategory, Source, ContentChunk,
│                                  # GlossaryTerm, DoctorQuestionTemplate, ClinicalQuestion, ClinicalRule,
│                                  # ClinicalRuleVersion, GuidanceText, RulesetRelease, AiConfiguration,
│                                  # AiUsageLog, DailyMetric, AuditLog
├── Policies/                      # ArticlePolicy, ClinicalRuleVersionPolicy, RulesetReleasePolicy, AiConfigurationPolicy, UserPolicy
├── Services/
│   ├── Clinical/                  # ClinicalRulesEngine, ConditionEvaluator, ActiveRulesetResolver,
│   │                              # RulesetImporter, RulesetTestRunner, Data/{AssessmentInput, EvaluationResult, TriggeredRule}
│   ├── Guidance/                  # GuidanceService, Data/GuidanceReport
│   ├── Content/                   # ArticlePublisher, MarkdownRenderer, ChunkBuilder, ContentSearch
│   ├── Ai/                        # Contracts/AiTextGenerator, Gemini/GeminiTextGenerator, Fake/FakeAiTextGenerator,
│   │                              # CompanionService, Filters/{RedFlagInterceptor, InputPreFilter, OutputPostFilter},
│   │                              # GroundingRetriever, PromptBuilder, CorePrompt (versioned constant), BudgetGuard, UsageLogger
│   ├── Analytics/                 # MetricsRecorder
│   └── Audit/                     # AuditLogger
├── Console/Commands/              # rafiq:clinical:import, rafiq:clinical:test, rafiq:ai:eval, rafiq:prune
└── Providers/                     # AppServiceProvider (bindings, rate limiters, boot checks for Demo mode)

config/rafiq.php                   # locales, demo_mode, emergency numbers per country, retention days, AI limits
resources/clinical/rulesets/RS-2026.1/{questions.json, rules/CR-001.json ...}
resources/clinical/test-cases/*.json   # Clinical-Test-Cases قابلة للتشغيل الآلي
lang/{ar,en}.json                  # نصوص الواجهة
lang/{ar,en}/validation.php
```

**الالتزام بمهارة `laravel-best-practices`:** Controllers رفيعة؛ Form Requests للتحقق؛ Policies للتفويض؛ حقن التبعيات بالـ constructor؛ Contract عند حدود الخدمات الخارجية (Gemini)؛ `config()` لا `env()` في الكود؛ `mb_*`/`Str` للنص العربي؛ timeouts صريحة لـ HTTP؛ ترتيب حتمي في الاستعلامات.

## 4. الهيكل المستهدف — Frontend

```text
resources/js/
├── app.tsx                        # createInertiaApp + DirectionProvider
├── types/                         # index.d.ts (SharedProps, Locale, Priority, GuidanceReport, Article, ...)
├── lib/
│   ├── i18n.ts                    # useT() يقرأ shared translations
│   ├── direction.tsx              # DirectionProvider / useDirection
│   ├── http.ts                    # postJson مع XSRF، بدون تسجيل الأجسام
│   ├── quickExit.ts               # FR-005
│   └── print.ts                   # توليد طباعة/ملف نصي محلياً
├── layouts/
│   ├── PublicLayout.tsx           # Header (lang switch, quick exit), DemoBanner, Footer, EmergencyStrip
│   └── AdminLayout.tsx
├── components/
│   ├── ui/                        # Button, Card, Dialog, RadioCardGroup, Progress, Alert, Tabs ...
│   ├── public/                    # JourneyCard, PriorityBadge, SourceList, ReviewStamp ...
│   └── admin/                     # DataTable, StatusBadge, BilingualTabs, DiffViewer, MarkdownEditor ...
├── features/
│   ├── assessment/                # useAssessmentMachine (state machine), QuestionRenderer, SafetyScreen, EmergencyScreen
│   ├── guidance/                  # ReportView, PrintableReport
│   ├── doctor-prep/               # TopicPicker, QuestionBank, LocalQuestions, PrintableChecklist
│   └── ai-chat/                   # ChatPanel, AiNotice, Message, Citations
└── pages/
    ├── Public/                    # Welcome, Landing, Journeys, Under18, Concern, Conception, Learn/{Index,Article,Search},
    │                              # Glossary, DoctorPrep, Legal/{Privacy,Terms,Limits,Sources}, Errors/*
    └── Admin/                     # Dashboard, Articles/*, Sources/*, Glossary/*, DoctorQuestions/*, Clinical/*,
                                   # Releases/*, Ai/*, Users/*, Analytics, AuditLog, Auth/*
```

**قواعد الواجهة:**
- TypeScript `strict`, `noUncheckedIndexedAccess`؛ لا `any`.
- لا منطق سريري في الواجهة: أنواع الأسئلة وترتيبها تأتي من الخادم؛ الواجهة تعرض فقط وتطبّق التفرع المعلن (`show_if`) — والتفرع لا يغيّر الأولوية.
- لا تخزين في `localStorage`/`sessionStorage` إلا FR-095 الاختياري.
- كل النصوص عبر `useT()`؛ Logical CSS فقط (L10N-005).
- `router.post` من Inertia لا يُستخدم لإرسال الإجابات (ADR-019).

## 5. المهارات المحلية ذات الصلة بالتنفيذ

| المهارة | الاستخدام عند التنفيذ |
|---|---|
| `laravel-best-practices` | كل كود PHP |
| `testing-best-practices` | تصميم الاختبارات (PHPUnit) |
| `rtl-ltr-localization` | كل مكونات الواجهة (مع ملاحظة ADR-011) |
| `tailwindcss-development` | الأنماط |
| `ui-ux-pro-max-skill` | اتجاه التصميم/الألوان/الخطوط في M01 (أداة بحث Python) |
| `deploying-to-cloud` | فقط إذا اختيرت Laravel Cloud (OQ-011) |
| غير مناسبة لرفيق | `livewire-development` (نستخدم React)، `react-three-fiber-development`، `animation-engine-development`، `section-builder-development`، `media-library-management`، `theme-system-development`، `portfolio-*` (خاصة بمشروع Portfolio آخر) |
