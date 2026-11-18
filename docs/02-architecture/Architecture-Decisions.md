# سجل القرارات المعمارية (Architecture Decision Records)

> آخر تحديث: 2026-11-15 · الصيغة: MADR مختصرة · الحالات: `Accepted` (قرار من المالك أو إلزامي)، `Proposed` (توصية بانتظار الموافقة)، `Superseded`، `Rejected`.
> أي قرار `Proposed` يُعامل كافتراض عمل للتوثيق ويجب تأكيده قبل بدء الـ Module المعني.

| ADR | العنوان | الحالة | Module |
|---|---|---|---|
| ADR-001 | Monolith: Laravel 13 + Inertia + React + TypeScript | Accepted | M00 |
| ADR-002 | قاعدة البيانات: MySQL 8.4 LTS | Proposed | M00 |
| ADR-003 | التقييم Stateless: لا تخزين لإجابات التقييم | Accepted (من الـ Brief) | M05–M07 |
| ADR-004 | جلسات بدون IP/User Agent على مستوى التطبيق | Proposed | M00, M03 |
| ADR-005 | منطق القواعد ملفات تعريف مُصدَّرة في Git + اعتماد في Admin | Proposed | M06, M11 |
| ADR-006 | Ruleset Releases + وضع Demo محصور خارج Production | Proposed | M06 |
| ADR-007 | Gemini عبر Laravel HTTP Client خلف Contract، Paid tier، دون Search grounding | Proposed | M09 |
| ADR-008 | Grounding باسترجاع نصي من مقاطع المحتوى المعتمد (بدون Embeddings في الـ MVP) | Proposed | M08, M09 |
| ADR-009 | تحضير زيارة الطبيب دون إرسال نص المستخدم لأي خادم | Proposed (يعدّل طلباً في الـ Brief) | M10 |
| ADR-010 | المنصة 18+ مع تأكيد ذاتي للعمر | Proposed | M03 |
| ADR-011 | الترجمة: بادئة URL، ملفات lang للواجهة، جداول ترجمة للمحتوى، لا بديل صامت للمحتوى الطبي | Proposed | M00, M08 |
| ADR-012 | مصادقة الأدمن: Laravel Fortify مع 2FA إلزامي، بلا تسجيل | Proposed | M11 |
| ADR-013 | RBAC داخلي (roles + pivot + Policies) دون حزمة خارجية | Proposed | M11 |
| ADR-014 | إحصاءات داخلية مجمعة يومياً دون أدوات طرف ثالث | Proposed | M11 |
| ADR-015 | اللغة الافتراضية العربية | Proposed | M00 |
| ADR-016 | واجهة: Tailwind CSS 4 + مكونات Headless قابلة للوصول | Proposed | M00 |
| ADR-017 | المحتوى Markdown مُعقّم، لا HTML خام | Proposed | M08 |
| ADR-018 | تثبيت Laravel Boost في بداية M00 | Proposed | M00 |
| ADR-019 | تقييم المحرك عبر JSON POST وعرض النتيجة في نفس صفحة Inertia | Proposed | M05–M07 |
| ADR-020 | فصل نطاقات الكود: Clinical لا يعتمد على Ai والعكس | Accepted (من الـ Brief) | M06, M09 |

---

## ADR-001 — Monolith: Laravel 13 + Inertia + React + TypeScript
- **الحالة:** Accepted (قرار المالك في الـ Brief).
- **السياق:** المطلوب مشروع واحد، وفريق صغير، وموعد مسابقة.
- **القرار:** تطبيق Laravel واحد يخدم واجهتين (User و Admin) عبر Inertia.js مع React + TypeScript، بنية Vite موجودة مسبقاً.
- **العواقب:** (+) مصادقة/CSRF/جلسات Laravel الأصلية، نشر واحد. (−) يجب تثبيت `inertiajs/inertia-laravel` و`@inertiajs/react` و`react` و`react-dom` و`typescript` و`@vitejs/plugin-react` (Technical Gap — غير مثبتة حالياً). الإصدارات تُحدَّد عند التنفيذ بعد التحقق من التوافق مع Laravel 13.

## ADR-002 — قاعدة البيانات: MySQL 8.4 LTS
- **الحالة:** Proposed.
- **السياق:** الإعداد الحالي `DB_CONNECTION=sqlite` (ملف `database/database.sqlite` موجود). Laragon يوفر MySQL 8.4.3. الاختبارات في `phpunit.xml` تستخدم SQLite `:memory:`.
- **الخيارات:**

| الخيار | (+) | (−) |
|---|---|---|
| SQLite | صفر إعداد، موجود الآن | كتابة متزامنة محدودة؛ FULLTEXT غير متاح بنفس الشكل؛ أقل شيوعاً للاستضافة المدارة |
| **MySQL 8.4 LTS** | متوفر في Laragon؛ مدعوم على أغلب الاستضافات وLaravel Cloud؛ FULLTEXT مع ngram parser للعربية | يحتاج إعداداً |
| PostgreSQL | قوي، Full-text متقدم | غير مثبت في Laragon؛ إعداد إضافي |

- **القرار المقترح:** MySQL 8.4 للتطوير والإنتاج؛ الإبقاء على SQLite `:memory:` لسرعة اختبارات الوحدة/الـ Feature، مع **Job في CI يشغّل الاختبارات على MySQL** لاكتشاف فروقات اللهجة (JSON، FULLTEXT). اختبارات البحث النصي تُعلَّم بمجموعة `mysql`.
- **العواقب:** تحديث `.env` المحلي (ليس الآن)؛ ترميز `utf8mb4` و`utf8mb4_unicode_ci` (أو `utf8mb4_0900_ai_ci`) لدعم العربية.

## ADR-003 — التقييم Stateless
- **الحالة:** Accepted (يطبق مبادئ الـ Brief §6).
- **القرار:** إجابات Safety Screening وAssessment تُحفظ في ذاكرة React فقط، تُرسل في جسم POST، ويقيّمها المحرك ويعيد النتيجة دون كتابة أي شيء في قاعدة البيانات أو الجلسة أو السجلات أو الـ cache.
- **البدائل المرفوضة:** جلسة خادم مشفرة مع TTL (تخزين صحي غير ضروري)؛ تخزين في localStorage افتراضياً (خطر الأجهزة المشتركة).
- **العواقب:** (+) لا بيانات صحية مخزنة، سطح هجوم أصغر، لا حاجة لآلية حذف للإجابات. (−) التحديث يفقد التقدم (يُعالج بتنبيه `beforeunload`)؛ لا يمكن "العودة لاحقاً".

## ADR-004 — جلسات بلا IP/User Agent
- **الحالة:** Proposed.
- **السياق:** `SESSION_DRIVER=database` حالياً، وجدول `sessions` في الـ migration الافتراضية يحتوي `ip_address` و`user_agent`، ومعالج Laravel لقاعدة البيانات يملؤهما تلقائياً. `SESSION_ENCRYPT=false`.
- **القرار المقترح:** `SESSION_DRIVER=redis` في الإنتاج (Laragon يوفر Redis للتطوير) أو `file` محلياً، مع `SESSION_ENCRYPT=true`، `SESSION_SECURE_COOKIE=true` في الإنتاج، `same_site=lax`، `http_only=true`، `SESSION_LIFETIME=30`. الجلسة تحمل فقط: `locale`، `age_confirmed`، `assessment_consent`، `ai_notice_ack`، CSRF token، وحالة دخول الأدمن.
- **البديل:** الإبقاء على `database` مع معالج جلسات مخصص لا يكتب IP/UA وحذف العمودين من الـ migration — يتطلب كوداً مخصصاً.
- **العواقب:** جلسات الأدمن على نفس المحرك؛ إبطال جلسات الأدمن يتم عبر تدوير `remember_token` وتسجيل الخروج من كل الأجهزة. حدود المعدل تستخدم IP **كمفتاح مُجزّأ (hashed) قصير العمر في الـ cache** — يوثَّق كمعرّف تقني (PRIV-007).

## ADR-005 — منطق القواعد ملفات تعريف مُصدَّرة في Git
- **الحالة:** Proposed.
- **السياق:** المطلوب قواعد قابلة للاختبار والمراجعة، ولوحة إدارة تعرض الإصدارات والاعتمادات.
- **القرار المقترح:** تعريف كل قاعدة بـ JSON تصريحي في `resources/clinical/rulesets/{version}/rules/CR-xxx.json` (conditions + priority + guidance keys + references)، مع ملف أسئلة `questions.json`. أمر Artisan `rafiq:clinical:import {version}` يستوردها إلى جداول `clinical_rule_versions` بحالة `Draft`. الاعتماد والنشر في لوحة الإدارة. لا تحرير للمنطق من الواجهة.
- **السبب:** تغييرات المنطق تمر بمراجعة كود + اختبارات CI قبل أن تصل للمراجع الطبي؛ الواجهة تبقى سطح اعتماد لا سطح كتابة.
- **العواقب:** المراجع الطبي يحتاج عرضاً مقروءاً للشرط (Admin يعرض الشروط كجمل مقروءة مولّدة من JSON). نصوص الإرشاد (`clinical_guidance_texts`) قابلة للتحرير في الواجهة وتعود إلى Draft.

## ADR-006 — Ruleset Releases ووضع Demo
- **الحالة:** Proposed.
- **القرار المقترح:** المحرك يحمّل **Ruleset Release** واحداً نشطاً (مثل `RS-2026.1`). شروط النشر في Production: كل القواعد والأسئلة ونصوص الإرشاد في الإصدار `Approved` لكل اللغات + تقرير اختبار ناجح + اعتماد Medical Reviewer + تأكيد Super Admin. `RAFIQ_DEMO_MODE=true` يسمح بتشغيل إصدار `Draft` **فقط** إذا `APP_ENV != production` مع شريط تحذير دائم؛ أي تركيبة أخرى تفشل عند الإقلاع.
- **العواقب:** عرض المسابقة يعمل قبل الاعتماد الطبي بشفافية.

## ADR-007 — تكامل Gemini
- **الحالة:** Proposed.
- **القرار المقترح:**
  1. واجهة `App\Services\Ai\Contracts\AiTextGenerator` وتنفيذ `GeminiTextGenerator` عبر Laravel HTTP Client (REST `generateContent`) مع `connectTimeout(3)` و`timeout(15)` وإعادة محاولة واحدة لأخطاء 429/5xx فقط. لا اعتماد على SDK غير رسمي لـ PHP.
  2. **Paid Services فقط** (AI-015).
  3. لا Google Search/Maps grounding، لا Files API، لا explicit caching (AI-016).
  4. النموذج من الإعداد `services.gemini.model` — يُختار نموذج Flash/Flash-Lite مستقر عند التنفيذ بعد التحقق من صفحة النماذج الرسمية (قائمة النماذج في مهارة `gemini-api-dev` المحلية تحتاج تحققاً).
  5. Structured output (JSON schema) لإجبار حقل `cited_chunk_ids` و`out_of_scope`.
- **البديل المستقبلي:** Vertex AI (يُشار إليه في صفحة ZDR كخيار لضمانات ZDR واتفاقيات معالجة البيانات) — OQ-006.

## ADR-008 — Grounding باسترجاع نصي
- **الحالة:** Proposed.
- **القرار المقترح:** عند نشر ترجمة مقال، يُقسَّم نصها إلى مقاطع (~120–250 كلمة) في `content_chunks`. الاسترجاع: في Explain mode المقطع/المقاطع من المقال الحالي فقط؛ في Platform Guide من "دليل المنصة"؛ وفي الأسئلة العامة بحث FULLTEXT (MySQL ngram) ضمن التصنيفات المسموح بها للـ AI، أعلى 4 مقاطع. لا Embeddings في الـ MVP.
- **العواقب:** دقة استرجاع أقل من الدلالي لكنها كافية لمحتوى صغير؛ إعادة النظر عند DF-03.

## ADR-009 — تحضير زيارة الطبيب محلياً
- **الحالة:** Proposed — **يعدّل** طلب الـ Brief §4.C.
- **السياق:** الـ Brief يطلب AI يرتب ويصوغ أسئلة يكتبها المستخدم، ويطلب أيضاً عدم إرسال بيانات الأعراض لـ Gemini. أسئلة المستخدم لطبيبه تحتوي بطبيعتها أعراضاً وتاريخاً صحياً. كذلك تمنع شروط Gemini استخدامه "لتقديم نصيحة طبية" [SRC-006].
- **القرار المقترح:** في الـ MVP: بنك أسئلة معتمد + نص المستخدم محلي فقط + ترتيب يدوي (سحب/أزرار) + AI يشرح مصطلحات بنك الأسئلة فقط. الصياغة بالـ AI مؤجلة (DF-02).
- **البدائل المدروسة:** (أ) إرسال النص بعد مرشح PII — مرفوض: إزالة الهوية لا تجعل البيانات الصحية آمنة (AI-009). (ب) نموذج محلي في المتصفح — ممكن مستقبلاً (on-device) لكن غير ناضج للعربية والأجهزة الضعيفة. (ج) قوالب صياغة حتمية ("ما الأسباب المحتملة لـ…؟") — **مقترح كتحسين Should في M10**.
- **يتطلب:** موافقة المالك (OQ-005).

## ADR-010 — المنصة 18+
- **الحالة:** Proposed.
- **السياق:** شروط Gemini: يجب أن يكون المستخدم 18+ ولا يجوز استخدام الخدمة في تطبيق "موجَّه إلى أو يُرجَّح أن يصل إليه من هم دون 18" [SRC-006]. وCR-001 في المسودة يغطي أهلية العمر.
- **القرار المقترح:** Age Gate بنقرة واحدة قبل كل المسارات وقبل الـ AI؛ لا تاريخ ميلاد؛ المحتوى والتسويق موجهان للبالغين. صفحة Under-18 آمنة.
- **المخاطر المتبقية:** التأكيد الذاتي لا يمنع الكذب؛ يُوثَّق كخطر R-03؛ OQ-004 لرأي قانوني حول كفاية ذلك.

## ADR-011 — الترجمة
- **الحالة:** Proposed.
- **القرار المقترح:** مسارات `/{locale}` (`ar|en`) مع Middleware `SetLocale`؛ نصوص الواجهة في `lang/ar.json` و`lang/en.json` (مُصدَّرة في Git وتمر بمراجعة كود) وتُمرر للـ React كـ shared prop لكل مجموعة صفحات؛ المحتوى في جداول `*_translations` بحالة مستقلة لكل لغة. لا بديل صامت للمحتوى الطبي.
- **ملاحظة على المهارات:** مهارة `rtl-ltr-localization` المحلية تقترح جدول `translations` في قاعدة البيانات لنصوص الواجهة — تلك توصية مشروع آخر (portfolio). نختار ملفات lang لأن نصوص الواجهة في رفيق تتضمن عبارات سلامة يجب أن تمر بمراجعة كود واختبارات، وليس تحريراً حراً من لوحة الإدارة. نصوص السلامة السريرية تأتي من `clinical_guidance_texts` المعتمدة.

## ADR-012 — مصادقة الأدمن بـ Fortify
- **الحالة:** Proposed.
- **القرار المقترح:** `laravel/fortify` (headless) لتسجيل الدخول، 2FA (TOTP) **إلزامي** لكل الأدمن، استرداد كلمة المرور، تأكيد كلمة المرور للعمليات الحساسة. تعطيل التسجيل. البادئة `/admin`. إعادة استخدام جدول `users` لحسابات الأدمن فقط (لا مستخدمون عامّون).
- **البديل:** Laravel React Starter Kit — يتطلب إعادة هيكلة المشروع؛ يمكن الاستئناس بمكوناته.

## ADR-013 — RBAC داخلي
- **الحالة:** Proposed.
- **القرار المقترح:** جداول `roles` و`role_user` (مع `granted_by`, `granted_at`, `credential_verified_at` للدور الطبي)؛ صلاحيات معرّفة في Enum/Config وتُطبق عبر Policies وGates. ثلاثة أدوار ثابتة. مبدأ العينين الأربع في Policy.
- **البديل:** `spatie/laravel-permission` — مقبول إذا تجاوز عدد الأدوار/الصلاحيات الحاجة الحالية.

## ADR-014 — إحصاءات داخلية مجمعة
- **الحالة:** Proposed.
- **القرار المقترح:** جدول `daily_metrics(date, metric, dimension, value)` بزيادة ذرية (`upsert` + increment). لا معرّف جلسة، لا IP، لا UA، لا مسار URL كامل. لا سكربتات Analytics خارجية؛ CSP يمنعها.
- **ما لا يُجمع:** أي إجابة، علامة خطر، أولوية ناتجة (OQ-010)، نص AI.

## ADR-015 — العربية لغة افتراضية
- **الحالة:** Proposed (يعتمد على AS-002 وOQ-007).
- **القرار:** `APP_LOCALE=ar`، `APP_FALLBACK_LOCALE=en` لنصوص الواجهة فقط؛ `x-default` يشير إلى `/ar`.

## ADR-016 — مكتبة الواجهة
- **الحالة:** Proposed.
- **القرار المقترح:** Tailwind CSS 4 (مثبت) مع مكونات headless قابلة للوصول (Radix UI primitives أو ما يعادلها، ويمكن اعتماد نمط shadcn/ui). أيقونات Lucide. لا مكتبات حركة ثقيلة في الـ MVP (المهارات المحلية لـ GSAP/Three.js غير مناسبة لهذا المنتج).

## ADR-017 — Markdown مُعقّم
- **الحالة:** Proposed.
- **القرار المقترح:** المحتوى يُكتب Markdown (CommonMark) ويُحوَّل على الخادم عبر `league/commonmark` (متوفر ضمن تبعيات Laravel — يُتحقق) مع `html_input: strip` و`allow_unsafe_links: false`، ويُخزّن الناتج المُعقّم مع المصدر. الـ React يعرض HTML المُعقّم من الخادم فقط.

## ADR-018 — Laravel Boost
- **الحالة:** Proposed.
- **السياق:** `CLAUDE.md`/`AGENTS.md` تطلب `composer require laravel/boost --dev` و`php artisan boost:install` قبل تعديل التطبيق، و`.claude/mcp.json` يشير إلى `boost:mcp` الذي لن يعمل قبل التثبيت. هذه المرحلة توثيقية والـ Brief يمنع تثبيت الحزم.
- **القرار:** أول خطوة في M00 هي تثبيت Boost (بموافقة المالك)، ثم إعادة قراءة `AGENTS.md` المولّد.

## ADR-019 — تقييم المحرك عبر JSON
- **الحالة:** Proposed.
- **القرار:** صفحة Inertia واحدة لكل مسار تقييم؛ الخطوات تُدار في React؛ الإرسال عبر `fetch/axios` POST JSON إلى endpoint داخل مجموعة `web` (CSRF عبر `X-XSRF-TOKEN`) يعيد النتيجة JSON. **لا** `router.post` بـ Inertia لأن ذلك يضع بيانات الصفحة الناتجة في تاريخ المتصفح؛ ولا redirect إلى صفحة نتيجة لها URL.
- **العواقب:** النتيجة غير قابلة للوصول عبر URL أو زر الرجوع بعد مغادرة الصفحة.

## ADR-020 — فصل Clinical وAi
- **الحالة:** Accepted.
- **القرار:** `App\Services\Clinical\*` و`App\Services\Guidance\*` لا تستورد أي شيء من `App\Services\Ai\*` والعكس. يُفرض باختبار معماري (PHPUnit يفحص `use` statements أو أداة مثل `pestphp/pest-plugin-arch` إذا اعتُمد Pest لاحقاً — المشروع حالياً PHPUnit).
