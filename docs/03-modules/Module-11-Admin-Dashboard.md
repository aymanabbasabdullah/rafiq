# Module-11 — Admin Dashboard (لوحة الإدارة — Rafiq Admin)

> الحالة: Planned · **مقسّمة إلى خمس وحدات فرعية تُنفذ في أوقات مختلفة** لأن وحدات أخرى تعتمد على أجزاء منها:
>
> | الفرعية | المحتوى | ترتيب التنفيذ | مع |
> |---|---|---|---|
> | **11-A** | المصادقة، 2FA، الأدوار، الحسابات، سجل التدقيق، هيكل اللوحة | **5** | — |
> | **11-B** | إدارة المحتوى (مقالات، تصنيفات، مصادر، مسرد، بنك أسئلة الطبيب) | **6** | M08 |
> | **11-C** | إدارة القواعد السريرية والإصدارات ونصوص الإرشاد | **7** | M06 |
> | **11-D** | إعدادات الـ AI | **8** | M09 |
> | **11-E** | الإحصاءات الآمنة للخصوصية | **13** | — |
>
> التقدير الإجمالي: 12–16 يوماً موزعة. **DRAFT – PENDING QUALIFIED MEDICAL REVIEW** ينطبق على أي قاعدة/محتوى معروض في 11-B/11-C.

## 1. Purpose
واجهة داخلية آمنة للمسؤولين المخوّلين لإدارة المحتوى والقواعد وإعدادات الـ AI والحسابات، مع سير عمل اعتماد طبي بالعينين الأربع، وإحصاءات مجمعة لا تكشف أي بيانات مستخدمين.

## 2. Scope
**يشمل:** ADM-001..060.
**لا يشمل:** أي عرض لبيانات مستخدمين أو جلساتهم (غير موجودة بالتصميم)، تحرير منطق القواعد (ADR-005)، تحرير CorePrompt، رفع ملفات وسائط (مؤجل).

## 3. Dependencies
11-A: M00. · 11-B: 11-A + M08 (نماذج). · 11-C: 11-A + M06. · 11-D: 11-A + M09. · 11-E: 11-A + عدادات الوحدات العامة.

## 4. User Stories
- US-11-1 (Super Admin): إنشاء حساب لمحرر وتعيين دوره وإلزامه بـ 2FA.
- US-11-2 (Super Admin): منح دور Medical Reviewer بعد تسجيل التحقق من أهليته.
- US-11-3 (Content Editor): كتابة مقال بالعربية والإنجليزية ومعاينته وإرساله للمراجعة.
- US-11-4 (Medical Reviewer): رؤية التغييرات فقط واعتماد/رفض كل ترجمة بتعليق.
- US-11-5 (Medical Reviewer): قراءة شرط القاعدة كجملة مفهومة ونتائج اختباراتها قبل اعتمادها.
- US-11-6 (Super Admin): نشر Ruleset Release بعد اعتماده طبياً وبتقرير اختبار ناجح.
- US-11-7 (Super Admin): تعديل رسائل الترحيب واقتراحات الـ AI وتفعيلها بعد نجاح التقييم.
- US-11-8 (أي أدمن): رؤية إحصاءات مجمعة دون أي بيانات فردية.

## 5. Functional Requirements

### 11-A — Auth, Roles, Accounts, Audit
| ID | المتطلب | PRD |
|---|---|---|
| M11A-FR-01 | Fortify: login، logout، forgot/reset password، 2FA (TOTP) إلزامي (إعداد عند أول دخول قبل أي صفحة)، recovery codes، password confirmation. التسجيل معطّل. | ADM-001, SEC-007 |
| M11A-FR-02 | `EnsureTwoFactorEnabled` و`EnsureAdminActive` على كل `/admin/*`. | SEC-007 |
| M11A-FR-03 | إدارة الحسابات (Super Admin): إنشاء (دعوة ببريد لتعيين كلمة المرور)، تعطيل/تفعيل، إعادة تعيين 2FA، الأدوار؛ لا تعطيل لآخر Super Admin؛ لا منح الذات. | ADM-040 |
| M11A-FR-04 | منح Medical Reviewer يتطلب `credential_verified_at` + `credential_reference`. | ADM-003 |
| M11A-FR-05 | `AuditLogger` + شاشة سجل التدقيق (فلترة حسب الفاعل/الإجراء/التاريخ؛ قراءة فقط). | ADM-004 |
| M11A-FR-06 | لوحة رئيسية: مهام معلقة حسب الدور (مراجعات بانتظاري، محتوى قارب موعد مراجعته، حالة الـ Ruleset النشط، حالة الـ AI). | — |
| M11A-FR-07 | لغة لوحة الإدارة حسب `preferred_locale` (ar/en) مع RTL. | L10N-001 |

### 11-B — Content Management
| ID | المتطلب | PRD |
|---|---|---|
| M11B-FR-01 | CRUD للتصنيفات (مع `ai_enabled`) والمقالات (مع `kind`, `ai_enabled`) والمسرد وبنك أسئلة الطبيب. | ADM-010 |
| M11B-FR-02 | محرر ثنائي اللغة بتبويبات EN | AR يحافظ على الحالة غير المحفوظة؛ Markdown + معاينة حية (عرض بنفس `MarkdownRenderer`). | ADM-012 |
| M11B-FR-03 | سير العمل لكل ترجمة: Draft ⇒ Submit ⇒ Approve/Reject (Medical Reviewer ≠ author) ⇒ Publish/Hide ⇒ Archive. | ADM-010, SAFE-003 |
| M11B-FR-04 | شاشة المراجعة الطبية: diff عن آخر إصدار معتمد، المصادر بجانب النص، checklist المراجعة، تعليق إلزامي عند الرفض. | Clinical-Review-Process |
| M11B-FR-05 | إدارة المصادر (حقول Database-Design) وربطها بالمقال مع `support_level` و`claim_note`. | ADM-011 |
| M11B-FR-06 | تنبيهات `review_due_at`. | SAFE-013 |
| M11B-FR-07 | فحص آلي قبل الإرسال: مصدر ≥ 1، لا عبارات محظورة، لا روابط غير آمنة، لا أنماط injection. | SAFE-010, T-07 |

### 11-C — Clinical Rules Management
| ID | المتطلب | PRD |
|---|---|---|
| M11C-FR-01 | قائمة القواعد: الرمز، العنوان، الأولوية، الإصدار، الحالة، الإصدار المنشور الحالي. | ADM-020 |
| M11C-FR-02 | تفاصيل الإصدار: الشرط كجملة مقروءة ar/en (مولد من JSON) + JSON، الأعلام، نصوص الإرشاد، المصادر، الفرق، TCs المتأثرة. | ADM-020 |
| M11C-FR-03 | انتقالات الحالة (Clinical-Review-Process §3). | ADM-021 |
| M11C-FR-04 | الإصدارات: إنشاء Release، "شغّل الاختبارات" (ينفذ `RulesetTestRunner` ويخزن التقرير)، اعتماد طبي، نشر (Super Admin + password.confirm)، عرض السابق. | ADM-021, SAFE-008 |
| M11C-FR-05 | تحرير نصوص الإرشاد والأسئلة (ترجمات فقط) ⇒ يعيدها Draft. | ADM-022 |
| M11C-FR-06 | لا تحرير للشروط/الأولويات من الواجهة. | ADM-022 |
| M11C-FR-07 | عرض حالة Kill switch (قراءة؛ التغيير عبر env). | — |

### 11-D — AI Configuration
| ID | المتطلب | PRD |
|---|---|---|
| M11D-FR-01 | إنشاء مسودة إعدادات من النشطة؛ تحرير الحقول الآمنة فقط (ADM-030). | ADM-030 |
| M11D-FR-02 | التحقق: نصوص ≤ حدود، قائمة محظورات (توسيع النطاق/injection)، الحدود ≤ config، التصنيفات من الموجودة فقط. | GR-15 |
| M11D-FR-03 | عرض CorePrompt (قراءة فقط) ونسخته. | AI-018 |
| M11D-FR-04 | إرفاق تقرير تقييم (رفع JSON ناتج `rafiq:ai:eval` أو تشغيله إن كان staging) — التفعيل ممنوع دونه. | AI-020 |
| M11D-FR-05 | تفعيل (Super Admin + password.confirm) ⇒ النشطة السابقة تُؤرشف؛ تعطيل فوري للـ AI. | ADM-031 |
| M11D-FR-06 | لوحة استخدام: الطلبات حسب outcome، الزمن، التكلفة اليومية مقابل السقف (من `ai_usage_logs` بلا محتوى). | AI-017 |

### 11-E — Privacy-Safe Analytics
| ID | المتطلب | PRD |
|---|---|---|
| M11E-FR-01 | عرض `daily_metrics` لفترة (7/30/90 يوماً): زيارات حسب نوع الصفحة، المسارات، اللغة، بوابة العمر، بدء/إكمال التقييم، الـ AI حسب outcome، أكثر المقالات طلباً للشرح. | ADM-050 |
| M11E-FR-02 | إخفاء القيم < 5 كـ "< 5"؛ لا تصدير خام في الـ MVP. | ADM-051 |
| M11E-FR-03 | لا مخططات لأولويات أو علامات خطر أو إجابات (غير موجودة). | ADM-060 |

## 6. User Flow (أمثلة)
- **نشر مقال:** Editor ينشئ ⇒ يكتب EN/AR ⇒ يربط مصادر ⇒ Submit (كل لغة) ⇒ Reviewer يعتمد AR ويرفض EN بتعليق ⇒ Editor يعدل EN ⇒ Submit ⇒ Approve ⇒ Editor ينشر اللغتين.
- **Ruleset:** import CLI ⇒ Super Admin يرسل القواعد للمراجعة ⇒ Reviewer يعتمد ⇒ Super Admin ينشئ Release ⇒ يشغّل الاختبارات ⇒ Reviewer يعتمد الإصدار ⇒ Super Admin ينشر.

## 7. UI/UX Requirements
- AdminLayout: شريط جانبي (حسب الصلاحية)، شريط علوي (اللغة، الحساب)، breadcrumbs.
- جداول: بحث، فلاتر حالة، ترتيب، ترقيم صفحات خادمي.
- StatusBadge موحد للحالات.
- AlertDialog لكل إجراء مدمر أو نشر.
- RTL كامل للوحة.
- رسائل toast للنجاح/الفشل.

## 8. Backend Requirements
Controllers تحت `App\Http\Controllers\Admin\*` (Application-Structure §3)؛ Form Requests `Admin\*`؛ Policies: `ArticleTranslationPolicy` (`update`, `submit`, `approve`, `publish`)، `ClinicalRuleVersionPolicy`، `RulesetReleasePolicy`، `AiConfigurationPolicy`، `UserPolicy`، `AuditLogPolicy`؛ Gate `four-eyes` (`$user->id !== $subject->author_id && $user->id !== $subject->last_editor_id`)؛ Services: `ArticleWorkflow`, `RuleReviewWorkflow`, `RulesetPublisher`, `AiConfigurationActivator`, `AuditLogger`؛ كل كتابة متعددة الجداول داخل `DB::transaction`.

## 9. Frontend Requirements
`pages/Admin/**`؛ مكونات `DataTable`, `BilingualTabs`, `MarkdownEditor` (textarea + معاينة؛ محرر غني اختياري لاحقاً)، `DiffViewer`, `StatusBadge`, `ConfirmDialog`, `ReadableCondition`, `TestReportView`, `MetricsChart` (مكتبة رسوم خفيفة — اختيار لاحق؛ تُطبّق مهارة `dataviz` إن استُخدمت). نماذج Inertia `useForm` مع أخطاء متداخلة (`translations.ar.title`).

## 10. Database Requirements
`users` (حقول Fortify + `preferred_locale`, `is_active`)، `roles`, `role_user`, `audit_logs` (11-A)؛ جداول المحتوى (M08)؛ جداول القواعد (M06)؛ `ai_configurations` (M09)؛ `daily_metrics` (M00).

## 11. Routes and Permissions
كل المسارات تحت `/admin` بـ middleware `web, auth, admin.active, 2fa, no-store, throttle:admin`.

| المجال | أمثلة المسارات | الأدوار |
|---|---|---|
| Auth | `/admin/login`, `/admin/two-factor-challenge`, `/admin/user/two-factor-authentication` (Fortify) | — |
| Dashboard | `GET /admin` | الكل |
| Users | `/admin/users` CRUD، `POST /admin/users/{user}/roles` | Super Admin |
| Audit | `GET /admin/audit-log` | Super Admin (كامل)، Medical Reviewer (عمليات الاعتماد) |
| Content | `/admin/articles`, `/admin/articles/{article}/translations/{locale}` + `submit`, `approve`, `reject`, `publish`, `hide`, `archive`؛ `/admin/categories`, `/admin/sources`, `/admin/glossary`, `/admin/doctor-questions` | Editor/Reviewer/Super حسب Security-Architecture §3 |
| Clinical | `/admin/clinical/rules`, `/admin/clinical/rules/{rule}/versions/{version}` + `submit`, `approve`, `reject`, `retire`؛ `/admin/clinical/releases` + `run-tests`, `approve`, `publish`؛ `/admin/clinical/guidance-texts` | حسب المصفوفة |
| AI | `/admin/ai/configurations` + `activate`, `/admin/ai/usage` | Super Admin |
| Analytics | `GET /admin/analytics` | الكل |

## 12. Business Rules
- BR-11-1: العينان الأربع لكل اعتماد طبي.
- BR-11-2: لا نشر لما ليس `approved`.
- BR-11-3: أي تعديل لعنصر معتمد ينشئ نسخة جديدة؛ لا حذف صلب لما نُشر.
- BR-11-4: لا منح الذات أدواراً؛ لا تعطيل آخر Super Admin.
- BR-11-5: Medical Reviewer بلا `credential_verified_at` لا يملك صلاحية `approve`.
- BR-11-6: نشر Ruleset/تفعيل AI/منح أدوار ⇒ `password.confirm` + audit.
- BR-11-7: لا وصول لأي بيانات مستخدمين — لا توجد.

## 13. Validation and Error Handling
Form Requests لكل عملية؛ أخطاء متداخلة لكل لغة؛ 403 مترجم عند غياب الصلاحية؛ تعارض تحرير متزامن ⇒ فحص `updated_at` (optimistic locking) ⇒ 409 "عدّله شخص آخر".

## 14. Security and Privacy
SEC-007..009؛ T-08..T-11؛ جلسة 30 دقيقة؛ لا كاش؛ CSP؛ audit لكل حساس؛ لا أسرار في `audit_logs.summary`.

## 15. Testing
- **مصفوفة الصلاحيات:** لكل (دور × عملية) اختبار Feature (سماح/رفض) — بما في ذلك العينين الأربع وBR-11-4/5.
- Auth: 2FA إلزامي؛ rate limits؛ reset password؛ مستخدم معطل.
- Workflow: انتقالات الحالة الصالحة/غير الصالحة لكل كيان.
- Ruleset publish: يرفض دون تقرير/اعتماد؛ ينجح ويجعل السابق Superseded.
- AI config: يرفض نصوص التوسيع؛ يرفض التفعيل دون تقرير؛ الحدود لا تتجاوز config.
- Analytics: إخفاء < 5.
- Audit: كل عملية حساسة تنشئ سجلاً بالحقول الصحيحة.

## 16. Acceptance Criteria
كل ADM محققة لكل فرعية في توقيتها؛ مصفوفة الصلاحيات خضراء؛ مراجعة أمنية للوحة قبل Demo.

## 17. Step-by-Step Implementation
**11-A:** (1) Fortify + إعداد views Inertia للمصادقة؛ (2) migrations الأدوار والتدقيق وحقول users؛ (3) Seeder Super Admin أولي عبر أمر `rafiq:admin:create` (لا كلمات مرور في الـ seeders)؛ (4) middleware 2FA/active؛ (5) AdminLayout + Dashboard هيكلي؛ (6) إدارة الحسابات والأدوار + Policies + tests؛ (7) AuditLogger + الشاشة.
**11-B:** (1) قوائم وCRUD للتصنيفات/المصادر؛ (2) محرر المقال ثنائي اللغة + المعاينة؛ (3) سير العمل + Policies + tests؛ (4) شاشة المراجعة + DiffViewer؛ (5) المسرد وبنك أسئلة الطبيب بنفس النمط؛ (6) تنبيهات المراجعة؛ (7) الفحوص الآلية قبل الإرسال.
**11-C:** (1) قوائم القواعد والإصدارات؛ (2) `ReadableCondition`؛ (3) انتقالات الحالة + tests؛ (4) Releases + run-tests + approve + publish + tests؛ (5) تحرير نصوص الإرشاد/الأسئلة.
**11-D:** (1) الشاشة والتحقق؛ (2) الإصدارات والتفعيل؛ (3) لوحة الاستخدام.
**11-E:** (1) استعلامات التجميع؛ (2) الرسوم والجداول مع الإخفاء؛ (3) tests.

## 18. Definition of Done
لكل فرعية: القبول + مصفوفة الصلاحيات الخاصة بها + audit + RTL + DoD العامة.

## 19. Open Questions
OQ-003 (المراجعون)، OQ-013 (احتفاظ السجلات)، OQ-021 (إظهار اسم المراجع للعامة)، OQ-029 (مراجع ثانٍ للغة)، OQ-032 (تنبيهات الدخول للأدمن بالبريد)، هل نحتاج Rich text editor (Tiptap) أم Markdown كافٍ؟ (مقترح: Markdown في الـ MVP).

---

## 20. ملاحظات التنفيذ — 11-A (2026-11-15)

**الحالة: 11-A منفذة** على الفرع `feature/m00-foundation` (دون commit). الفرعيات `11-B..11-E` تُنفذ في مواضعها حسب خارطة الطريق (`Implementation-Roadmap.md`).

| البند | ما نُفذ | ملاحظة |
|---|---|---|
| جداول قاعدة البيانات (Database-Design §3.1) | تهجير `2026_11_15_190000_create_admin_roles_and_audit_logs_tables.php`: إضافة حقول `two_factor_secret`، `two_factor_recovery_codes`، `two_factor_confirmed_at`، `preferred_locale`، `is_active`، `last_login_at` لجدول `users`؛ وإنشاء جداول `roles`، `role_user` (مع `granted_by`، `credential_verified_at`، `credential_reference`، `revoked_at`)، و`audit_logs` | أسرار التحقق الثنائي وأكواد الاسترداد مشفرة عبر `encrypted` و`encrypted:array` في نموذج `User`. |
| المصادقة والتحقق الثنائي الإلزامي (M11A-FR-01, M11A-FR-02, SEC-007) | `AuthController`، `TwoFactorController`، `TotpService` (RFC 6238 TOTP متوافق مع تطبيقات المصادقة مع نافذة ±1 خطوة و8 أكواد استرداد أحادية الاستخدام)، والوسيطان `EnsureTwoFactorEnabled` (`2fa`) و`EnsureAdminActive` (`admin.active`) | نُفذ التحقق الثنائي ومصادقة الأدمن دون إضافة حزم خارجية التزاماً بقاعدة عدم تغيير الاعتماديات دون إذن، مع مطابقة كاملة لعموديات Fortify في التصميم. |
| إنشاء الحساب الأولي عبر CLI (SEC-007) | الأمر `php artisan rafiq:admin:create --name= --email= --password= --locale=ar` يتحقق من قوة كلمة المرور (12 حرفاً فأكثر) وينشئ حساب `super_admin` ويسجل الحدث في `audit_logs` دون أي كلمات مرور ثابتة في الـ seeders | — |
| الأدوار والصلاحيات ومبدأ العينين الأربع (M11A-FR-03, M11A-FR-04, BR-11-1, BR-11-4, BR-11-5) | `AdminUserController`، `UserPolicy`، وبوابة `Gate::define('four-eyes', ...)`: تمنع تعديل المستخدم لأدواره بنفسه، وتمنع تعطيل الذات أو تعطيل/سحب صلاحية آخر `super_admin` نشط، وتشترط `credential_reference` لتوثيق `credential_verified_at` عند منح دور `medical_reviewer`، وترفض في `four-eyes` أي مراجع غير موثق أو يراجع عملاً شارك في تأليفه أو تحريره (حتى لو كان يحمل `super_admin`) | كل مسارات `/admin/users*` محمية بـ `password.confirm:admin.password.confirm`. |
| سجل التدقيق غير القابل للتعديل (M11A-FR-05, ADM-004) | `AuditLog` يرفض التعديل برمياً (`LogicException` عند `updating`)؛ `AuditLogger` ينقّي `summary` تلقائياً من جميع مفاتيح `rafiq.sensitive_keys` وأسرار 2FA ويخزن بصمة `ip_hash` عبر `HMAC-SHA256` بمفتاح التطبيق؛ `AuditLogController` يعرض السجل كاملاً لـ `Super Admin` ويقصره على عمليات الاعتماد والمراجعة الطبية للمراجع الطبي الموثق | — |
| تخطيط لوحة التحكم ثنائي اللغة والصفحات (M11A-FR-06, M11A-FR-07) | `AdminLayout` مع تبديل فوري للغة الواجهة (`PATCH /admin/locale`)، وشريط تنبيه وضع العرض التجريبي (`DemoBanner`)، وصفحات Inertia: `Admin/Auth/*` (6 صفحات)، `Admin/Dashboard`، `Admin/Users/Index`، و`Admin/AuditLog/Index`؛ مع وسم `<meta name="robots" content="noindex">` وترويسة `Cache-Control: no-store` وتشفير تاريخ المتصفح على السياقات الآمنة | لا تُشارك أي بيانات من `admin` في الجلسات أو الصفحات العامة (`admin: null` خارج `/admin*`). |
| الاختبارات | 114 اختباراً ناجحاً (1,489 assertion) على SQLite وMySQL — منها 10 اختبارات شاملة جديدة في `AdminAuthAndRolesTest` | تغطي إعداد وتحدي 2FA وأكواد الاسترداد، الحد من المحاولات (`429`)، الحسابات المعطلة، أمر CLI، مصفوفة الصلاحيات وتأكيد كلمة المرور، حماية آخر Super Admin، بوابة `four-eyes`، تنقية وعدم قابلية تعديل `audit_logs`، واستعادة كلمة المرور وتبديل اللغة. |

