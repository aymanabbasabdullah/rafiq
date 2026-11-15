# توثيق Rafiq | رفيق

> منصة توعية وإرشاد ثنائية اللغة (عربي/إنجليزي) في صحة الرجل الإنجابية — **ليست أداة تشخيص**.
> حالة التوثيق: **v0.1 Draft للمراجعة** · تاريخ الإعداد: 2026-11-15 · لم يُكتب أي كود للمنتج في هذه المرحلة.

> **DRAFT – PENDING QUALIFIED MEDICAL REVIEW** — كل القواعد السريرية والنصوص الطبية في هذا التوثيق مسودات غير معتمدة.

## 1. ابدأ من هنا (ترتيب القراءة المقترح)

| الدور | اقرأ أولاً |
|---|---|
| الجميع | [PRD](01-product/PRD.md) ⇒ [Open-Decisions](Open-Decisions.md) |
| المنتج/التصميم | [Product-Vision](01-product/Product-Vision.md) ⇒ [User-Personas](01-product/User-Personas.md) ⇒ [User-Journeys](01-product/User-Journeys.md) ⇒ [Scope-and-Non-Goals](01-product/Scope-and-Non-Goals.md) |
| المراجع الطبي | [Clinical-Safety-Framework](04-clinical/Clinical-Safety-Framework.md) ⇒ [Clinical-Rules-Catalogue](04-clinical/Clinical-Rules-Catalogue.md) ⇒ [Clinical-Test-Cases](04-clinical/Clinical-Test-Cases.md) ⇒ [Clinical-Review-Process](04-clinical/Clinical-Review-Process.md) ⇒ [Medical-Sources-Register](04-clinical/Medical-Sources-Register.md) |
| المطورون | [System-Architecture](02-architecture/System-Architecture.md) ⇒ [Architecture-Decisions](02-architecture/Architecture-Decisions.md) ⇒ [Application-Structure](02-architecture/Application-Structure.md) ⇒ [Database-Design](02-architecture/Database-Design.md) ⇒ [Implementation-Roadmap](07-project-plan/Implementation-Roadmap.md) ⇒ [Module-00](03-modules/Module-00-Foundation.md) |
| الخصوصية/الأمن/القانون | [Privacy-Requirements](05-privacy/Privacy-Requirements.md) ⇒ [Data-Inventory](05-privacy/Data-Inventory.md) ⇒ [Data-Lifecycle](05-privacy/Data-Lifecycle.md) ⇒ [Threat-Model](05-privacy/Threat-Model.md) ⇒ [Security-Architecture](02-architecture/Security-Architecture.md) ⇒ [Gemini-Integration](06-ai/Gemini-Integration.md) |
| الـ AI | [AI-Architecture](02-architecture/AI-Architecture.md) ⇒ [AI-Use-Cases](06-ai/AI-Use-Cases.md) ⇒ [AI-Safety-Guardrails](06-ai/AI-Safety-Guardrails.md) ⇒ [Knowledge-Grounding](06-ai/Knowledge-Grounding.md) ⇒ [AI-Evaluation-Plan](06-ai/AI-Evaluation-Plan.md) |

## 2. الهيكل

```text
docs/
├── README.md                     ← هذا الملف
├── Open-Decisions.md             ← [إضافة] القرارات المعتمدة + الافتراضات + الأسئلة المفتوحة
├── Glossary.md                   ← [إضافة] مسرد مصطلحات المشروع
├── 01-product/                   PRD · Product-Vision · User-Personas · User-Journeys · Scope-and-Non-Goals
├── 02-architecture/              System-Architecture · Database-Design · Application-Structure · Security-Architecture · AI-Architecture · Architecture-Decisions
├── 03-modules/                   Module-00 … Module-12
├── 04-clinical/                  Clinical-Safety-Framework · Clinical-Rules-Catalogue · Clinical-Review-Process · Clinical-Test-Cases · Medical-Sources-Register [إضافة]
├── 05-privacy/                   Privacy-Requirements · Data-Inventory · Data-Lifecycle · Threat-Model
├── 06-ai/                        AI-Use-Cases · AI-Safety-Guardrails · Gemini-Integration · Knowledge-Grounding · AI-Evaluation-Plan
└── 07-project-plan/              Implementation-Roadmap · Development-Backlog · Dependencies-and-Risks · Testing-Strategy · Definition-of-Done
```

### تعديلات على الهيكل المقترح في الـ Brief ومبرراتها

| التعديل | السبب |
|---|---|
| إضافة `Open-Decisions.md` | توحيد القرارات والافتراضات والأسئلة المفتوحة (الـ Brief يطلب قائمة واضحة) بمعرّفات تُستخدم في كل الوثائق. |
| إضافة `Glossary.md` | منع تضارب المصطلحات بين الفرق. |
| إضافة `04-clinical/Medical-Sources-Register.md` | معرّفات موحدة للمصادر (SRC-xxx) مع ما تحققنا منه فعلاً وقيود الاستخدام (مثل قيد AUA على أدوات AI). |
| **Module-11 مقسّم داخلياً إلى 11-A..11-E** (ملف واحد) | وحدات أخرى تعتمد على أجزاء منه (المصادقة قبل المحتوى؛ إدارة القواعد مع المحرك). |
| **ترتيب التنفيذ ≠ ترقيم الملفات** | الترقيم محفوظ كما في الـ Brief؛ الترتيب الفعلي في [Implementation-Roadmap](07-project-plan/Implementation-Roadmap.md) يحترم الاعتماديات (M03 قبل M02؛ M06 قبل M04؛ M09 بعد M08/M06). |

## 3. ملخص ما تم اكتشافه

- **المشروع:** Laravel 13.42 skeleton فارغ؛ PHP 8.3.16؛ SQLite؛ Tailwind 4 + Vite 8؛ **لا Inertia ولا React ولا TypeScript**؛ جدول `sessions` الافتراضي يخزن IP وUser Agent؛ `CLAUDE.md` يطلب تثبيت Laravel Boost (لم يُثبت — المرحلة توثيقية). التفاصيل والفجوات: [Application-Structure](02-architecture/Application-Structure.md).
- **شروط Gemini (تحقق 2026-11-15):** منع "النصيحة الطبية" والممارسة السريرية؛ 18+ ومنع التطبيقات المرجح وصول القاصرين إليها؛ الطبقة المجانية تُستخدم بياناتها للتحسين وقد يراجعها بشر؛ مراقبة الإساءة 55 يوماً؛ Search grounding يُخزن 30 يوماً. الأثر: [Gemini-Integration](06-ai/Gemini-Integration.md).
- **تعارض في الـ Brief:** "الـ AI يساعد في صياغة أسئلة يكتبها المستخدم" مقابل "لا تُرسل بيانات الأعراض لـ Gemini" ⇒ ADR-009 / OQ-005.
- **مسودة CR-001..CR-016 غير موجودة في المستودع** ⇒ أُعيد بناء الكتالوج من الـ Brief (OQ-019)، وأُضيف CR-017 مقترحاً من مصدر NHS.

## 4. المهارات (Skills) المستخدمة فعلياً

تم فحص `.claude/` ووجدنا 17 مهارة محلية + مكتبة `agentic-awesome-skills` (~2,350 مهارة). المستخدم فعلياً في إعداد هذا التوثيق:

| المهارة | الموقع | الدور |
|---|---|---|
| `laravel-best-practices` (+ rules: architecture, security, http-client, config) | `.claude/` | بنية Backend، Controllers/Requests/Policies، Contract للخدمات الخارجية، timeouts، الأسرار |
| `testing-best-practices` | `.claude/` | Testing-Strategy (PHPUnit، Feature أولاً، عدم إضافة أدوات دون موافقة) |
| `rtl-ltr-localization` | `.claude/` | متطلبات L10N (logical properties، `<bdi>`، الطباعة العربية، hreflang) — مع ملاحظة أن مراجع وثائقه تخص مشروعاً آخر |
| `portfolio-cms-development` (مرجع أنماط فقط) | `.claude/` | أنماط نماذج الإدارة ثنائية اللغة وحالات النشر — **ليست مواصفة لرفيق** (مهارة مشروع Portfolio آخر) |
| `privacy-by-design` | المكتبة | إطار Privacy-Requirements |
| `threat-modeling` (STRIDE) | المكتبة | Threat-Model |
| `prompt-injection-defense` | المكتبة | AI-Safety-Guardrails §5 |
| `architecture-decision-records` | المكتبة | صيغة ADR |
| `llm-evaluation` | المكتبة | AI-Evaluation-Plan |
| `gemini-api-dev` | المكتبة | أسماء SDK/النماذج (تحتاج تحققاً) — ليست مرجعاً للشروط |
| `i18n-localization` | المكتبة | مفاهيم i18n عامة |

**فُحصت ولم تُستخدم (غير مناسبة لهذه المرحلة أو لهذا المنتج):** `livewire-development` (نستخدم React)، `react-three-fiber-development`، `animation-engine-development`، `section-builder-development`، `media-library-management`، `theme-system-development`، `portfolio-architecture` (لمشروع آخر)، `deploying-to-cloud` (الاستضافة غير محددة)، `tailwindcss-development` و`ui-ux-pro-max-skill` (للتنفيذ/التصميم لاحقاً)، `infer-conventions` (لا تُستدعى إلا بطلب صريح ولا كود لاستنتاجه)، `to-prd` (مخصصة للنشر في issue tracker؛ قُرئت فقط).

## 5. الحالة والخطوة التالية

1. مراجعة هذا التوثيق من المالك وحسم الأسئلة 🔴 في [Open-Decisions](Open-Decisions.md) (OQ-001، OQ-002، OQ-003، OQ-018، OQ-024 للإطلاق).
2. الموافقة على ADRs المقترحة.
3. البدء بـ **Module-00 Foundation**.

## 6. اصطلاحات التوثيق

- اللغة: عربية مع الإبقاء على المصطلحات التقنية والطبية الإنجليزية.
- معرّفات: `FR`, `NFR`, `L10N`, `AI`, `SAFE`, `PRIV`, `SEC`, `ADM`, `DATA`, `M{nn}-FR`, `CR`, `TC`, `SRC`, `ADR`, `OQ`, `AS`, `RQ`, `R`, `T`, `GR`, `BL`.
- أي تغيير لقرار يُحدّث `Architecture-Decisions` أو `Open-Decisions` في نفس التعديل.
