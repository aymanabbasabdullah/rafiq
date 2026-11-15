# سجل العمل (Development Backlog)

> الحالة: Draft · آخر تحديث: 2026-11-15 · الصيغة: Epic ⇒ Items. التقدير بنقاط (1 ≈ نصف يوم). الأولوية MoSCoW. الترتيب = ترتيب التنفيذ في Roadmap.

| ID | Epic / Item | Module | المتطلبات | الأولوية | نقاط | يعتمد على |
|---|---|---|---|---|---|---|
| **E-00** | **Foundation** | M00 | | | | |
| BL-001 | تثبيت Laravel Boost وقراءة AGENTS.md المولد (بموافقة) | M00 | TC-05, ADR-018 | Must | 1 | — |
| BL-002 | MySQL 8.4 محلياً + utf8mb4 + `.env.example` | M00 | ADR-002 | Must | 1 | — |
| BL-003 | جلسات Redis مشفرة بلا IP/UA | M00 | ADR-004, PRIV-005 | Must | 2 | BL-002 |
| BL-004 | Inertia server + root view + HandleInertiaRequests | M00 | ADR-001 | Must | 2 | BL-001 |
| BL-005 | React + TS + Vite React plugin + tsconfig strict | M00 | ADR-001 | Must | 2 | BL-004 |
| BL-006 | Enums + config/rafiq.php + boot checks | M00 | SEC-015, FR-053 | Must | 2 | BL-004 |
| BL-007 | SetLocale + /{locale} + redirect + lang files + useT + DirectionProvider | M00 | L10N-001..004 | Must | 4 | BL-005 |
| BL-008 | PublicLayout هيكلي + DemoBanner + مبدّل اللغة | M00 | L10N-003 | Must | 2 | BL-007 |
| BL-009 | SecurityHeaders + NoStoreCache | M00 | SEC-002/003 | Must | 2 | BL-004 |
| BL-010 | صفحات الأخطاء ثنائية اللغة | M00 | FR-101 | Must | 2 | BL-007 |
| BL-011 | معالج السجلات الحذّاف + dontFlash | M00 | SEC-013 | Must | 1 | — |
| BL-012 | daily_metrics + MetricsRecorder | M00 | ADR-014 | Must | 1 | BL-002 |
| BL-013 | ESLint/Prettier + فحوص lang keys والأصناف الفيزيائية | M00 | L10N-005/010 | Must | 2 | BL-005 |
| BL-014 | CI (Pint, PHPUnit SQLite+MySQL, tsc, eslint, build, audits) | M00 | NFR-008 | Must | 3 | BL-013 |
| **E-01** | **Welcome & Landing** | M01 | | | | |
| BL-020 | الهوية البصرية الأولية + الخطوط المستضافة ذاتياً | M01 | GAP-10, PRIV-006 | Must | 3 | E-00 |
| BL-021 | Header/Footer + Quick Exit | M01 | FR-005, FR-100 | Must | 3 | BL-020 |
| BL-022 | Welcome screen | M01 | FR-001 | Must | 2 | BL-021 |
| BL-023 | Landing (6 أقسام + المسارات + شريط الطوارئ) | M01 | FR-002..004 | Must | 4 | BL-021 |
| BL-024 | SEO + hreflang + العدادات | M01 | L10N-013 | Should | 1 | BL-023 |
| **E-03** | **Privacy, Age & Consent** | M03 | | | | |
| BL-030 | Age Gate + EnsureAgeConfirmed + Under-18 | M03 | FR-020/023 | Must | 3 | E-01 |
| BL-031 | Assessment consent + middleware | M03 | FR-021 | Must | 2 | BL-030 |
| BL-032 | AI notice endpoint + middleware + Dialog | M03 | FR-022 | Must | 2 | BL-030 |
| BL-033 | Clear everything + encryptHistory | M03 | PRIV-008/011 | Must | 2 | BL-030 |
| BL-034 | صفحات Privacy/Terms/Limits أولية | M03 | FR-024 | Must | 2 | — |
| **E-02** | **Journey Selection** | M02 | | | | |
| BL-040 | Journeys page + نقاط الدخول + مصفوفة البوابات + العداد | M02 | FR-010..014 | Must | 4 | E-03 |
| **E-11A** | **Admin Foundation** | M11-A | | | | |
| BL-050 | Fortify + 2FA إلزامي + views Inertia | M11-A | ADM-001, SEC-007 | Must | 4 | E-00 |
| BL-051 | roles/role_user/audit_logs + rafiq:admin:create | M11-A | ADM-002..004 | Must | 2 | BL-050 |
| BL-052 | AdminLayout + Dashboard هيكلي (RTL) | M11-A | — | Must | 2 | BL-050 |
| BL-053 | إدارة الحسابات والأدوار + Policies + مصفوفة tests | M11-A | ADM-040, SEC-009 | Must | 3 | BL-051 |
| BL-054 | شاشة سجل التدقيق | M11-A | ADM-004 | Must | 1 | BL-051 |
| **E-08** | **Knowledge Center + Content Admin** | M08/M11-B | | | | |
| BL-060 | Migrations/Models المحتوى + Factories | M08 | DB §3.2 | Must | 3 | E-11A |
| BL-061 | MarkdownRenderer + XSS tests | M08 | ADR-017, SEC-010 | Must | 2 | BL-060 |
| BL-062 | ArticlePublisher + ChunkBuilder + Job | M08 | ADR-008 | Must | 3 | BL-061 |
| BL-063 | صفحات العرض العامة + ReviewStamp + Sources + Unavailable | M08 | FR-070..072 | Must | 4 | BL-062 |
| BL-064 | ArabicNormalizer + ContentSearch + POST search | M08 | FR-070, PRIV-003 | Must | 3 | BL-062 |
| BL-065 | المسرد (عام + إدارة) | M08/M11-B | FR-073 | Should | 2 | BL-063 |
| BL-066 | Admin: CRUD التصنيفات/المصادر/المقالات + محرر ثنائي اللغة + معاينة | M11-B | ADM-010..012 | Must | 5 | BL-060 |
| BL-067 | Admin: سير العمل + شاشة المراجعة + Diff + Policies | M11-B | ADM-010, SAFE-003 | Must | 4 | BL-066 |
| BL-068 | بنك أسئلة الطبيب (Admin + models) | M11-B | FR-091 | Must | 2 | BL-067 |
| BL-069 | Seeders Demo اصطناعية | M08 | PRIV-010 | Must | 2 | BL-062 |
| **E-06** | **Clinical Rules Engine + Admin** | M06/M11-C | | | | |
| BL-070 | JSON Schemas + Migrations/Models القواعد | M06 | ADR-005 | Must | 3 | E-11A |
| BL-071 | ConditionEvaluator (TDD، 100% فروع) | M06 | FR-050 | Must | 3 | BL-070 |
| BL-072 | ClinicalRulesEngine + DTOs | M06 | FR-050/051 | Must | 3 | BL-071 |
| BL-073 | كتابة RS-2026.1 JSON + نصوص مسودة ar/en | M06 | Catalogue | Must | 3 | BL-070 |
| BL-074 | RulesetImporter + CLI | M06 | ADR-005 | Must | 2 | BL-073 |
| BL-075 | ActiveRulesetResolver + Demo/Prod + boot checks | M06 | ADR-006 | Must | 2 | BL-072 |
| BL-076 | TC-01..37 JSON + RulesetTestRunner + CLI + PHPUnit | M06 | SAFE-008 | Must | 3 | BL-072 |
| BL-077 | ForbiddenPhraseChecker + red_flag_lexicon + SafetyLexiconProvider | M06 | SAFE-010, AI-012 | Must | 2 | BL-073 |
| BL-078 | Admin: قوائم/تفاصيل/ReadableCondition/انتقالات | M11-C | ADM-020/021 | Must | 4 | BL-074 |
| BL-079 | Admin: Releases + run-tests + approve + publish | M11-C | ADM-021, SAFE-008 | Must | 3 | BL-078 |
| BL-080 | Admin: تحرير نصوص الإرشاد/الأسئلة | M11-C | ADM-022 | Should | 2 | BL-078 |
| **E-09** | **AI Companion + AI Config** | M09/M11-D | | | | |
| BL-090 | Contract + Fake + Gemini generator + config | M09 | ADR-007 | Must | 3 | E-08 |
| BL-091 | AiMessageRequest + controller + gates + rate limits + budget | M09 | AI-013 | Must | 3 | BL-090 |
| BL-092 | RedFlagInterceptor + InputPreFilter (+ جداول أمثلة) | M09 | AI-010/012 | Must | 4 | BL-077 |
| BL-093 | GroundingRetriever + CorePrompt + PromptBuilder | M09 | AI-002, AI-018 | Must | 3 | BL-064 |
| BL-094 | OutputPostFilter + citation validation | M09 | AI-011 | Must | 2 | BL-093 |
| BL-095 | UsageLogger + metrics | M09 | AI-017 | Must | 1 | BL-091 |
| BL-096 | واجهة الدردشة + الاستشهادات + الاقتراحات | M09 | FR-080..083 | Must | 4 | BL-091 |
| BL-097 | مجموعات التقييم + rafiq:ai:eval | M09 | AI-020 | Must | 4 | BL-094 |
| BL-098 | Admin: إعدادات AI + التحقق + التفعيل + الاستخدام | M11-D | ADM-030/031 | Must | 3 | BL-095 |
| **E-04/05/07** | **Clinical Journey UI** | M04/M05/M07 | | | | |
| BL-100 | ScreenAssessmentRequest + /screen + TCs | M04 | FR-030..034 | Must | 3 | E-06 |
| BL-101 | آلة الحالات + Safety UI + P0/P1 screens | M04 | FR-032/033 | Must | 4 | BL-100 |
| BL-102 | QuestionSetPresenter + /evaluate + TCs | M05 | FR-040..043 | Must | 3 | BL-101 |
| BL-103 | QuestionRenderer + التقدم/المراجعة/البدء من جديد + تبديل اللغة | M05 | FR-044/045, L10N-003 | Must | 4 | BL-102 |
| BL-104 | GuidanceService + ربط /evaluate | M07 | FR-060 | Must | 2 | BL-102 |
| BL-105 | ReportView + الطباعة + txt + النقل لـ J4 | M07 | FR-061/062 | Must | 4 | BL-104 |
| **E-10** | **Doctor Visit Prep** | M10 | | | | |
| BL-110 | DoctorPrep page + الموضوعات + البنك + المحلي + الطباعة | M10 | FR-090..094 | Must | 5 | BL-068, BL-105 |
| BL-111 | قوالب الصياغة المنظمة | M10 | ADR-009 | Should | 1 | BL-110 |
| BL-112 | localStorage opt-in | M10 | FR-095 | Could | 1 | BL-110 |
| **E-11E** | **Analytics** | M11-E | | | | |
| BL-120 | شاشة الإحصاءات + إخفاء < 5 | M11-E | ADM-050/051 | Must | 3 | E-11A |
| **E-12** | **Testing & Launch** | M12 | | | | |
| BL-130 | rafiq:prune + review reminders + schedule | M12 | PRIV-011, SAFE-013 | Must | 2 | — |
| BL-131 | staging/demo + Seeders نهائية | M12 | FR-053 | Must | 3 | الكل |
| BL-132 | Regression + وصول + أداء + أمن + خصوصية | M12 | NFR-* | Must | 5 | BL-131 |
| BL-133 | تقييم AI نهائي + قابلية الاستخدام + سكربت العرض | M12 | SM-8/9/11 | Must | 3 | BL-132 |

**تقريبي:** ≈ 190 نقطة ≈ 95 نصف يوم ≈ 48–60 يوم عمل (يتسق مع Roadmap 58–76 مع الاحتياط).
