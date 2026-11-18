# البنية الأمنية (Security Architecture)

> الحالة: Draft · آخر تحديث: 2026-11-15 · مرتبط بـ [Threat-Model](../05-privacy/Threat-Model.md) و[Privacy-Requirements](../05-privacy/Privacy-Requirements.md)

## 1. الأهداف الأمنية

| الهدف | التطبيق في رفيق |
|---|---|
| السرية | لا بيانات صحية مخزنة؛ ما يعبر الشبكة محمي بـ TLS؛ لا تسريب عبر URL/سجلات/cache المتصفح. |
| السلامة (Integrity) | القواعد والمحتوى لا تتغير دون مراجعة وعينين أربع؛ الأولوية تُحسب على الخادم فقط. |
| التوفر | تعطل Gemini أو تجاوز السقف لا يعطل المنصة؛ حماية من إساءة الاستخدام. |
| المساءلة | سجل تدقيق لكل عمليات الأدمن الحساسة. |

## 2. متطلبات الأمن القابلة للاختبار

| ID | المتطلب | الاختبار |
|---|---|---|
| SEC-001 | HTTPS إلزامي في staging/production؛ HSTS `max-age=31536000; includeSubDomains`. | فحص الترويسات. |
| SEC-002 | ترويسات أمن على كل الاستجابات: `Content-Security-Policy` (لا inline scripts غير موقّعة بـ nonce؛ `connect-src 'self'`؛ لا نطاقات طرف ثالث)، `X-Content-Type-Options: nosniff`، `Referrer-Policy: no-referrer`، `Permissions-Policy` (تعطيل camera/microphone/geolocation)، `X-Frame-Options: DENY`/`frame-ancestors 'none'`. | Feature test لـ `SecurityHeaders` middleware. |
| SEC-003 | `Cache-Control: no-store, private` لاستجابات التقييم والـ AI وصفحات J2/J3/J4 ولوحة الإدارة. | Feature test. |
| SEC-004 | CSRF على كل POST (Inertia/axios `X-XSRF-TOKEN`)؛ لا استثناءات. | test بدون token ⇒ 419. |
| SEC-005 | حدود المعدل: التقييم 20/دقيقة/جلسة؛ AI 10/10 دقائق و30/يوم/جلسة + حد لكل IP مُجزّأ 60/ساعة؛ دخول الأدمن 5/دقيقة (بريد+IP)؛ 2FA 5/دقيقة. | tests ⇒ 429. |
| SEC-006 | التحقق من المدخلات بـ Form Requests: مفاتيح الأسئلة من قائمة الـ Ruleset النشط فقط، القيم من الخيارات المعلنة، رفض المفاتيح الزائدة، حجم الجسم ≤ 16KB للتقييم، رسالة AI ≤ 500 حرف، history ≤ 6 عناصر. | tests لكل حالة رفض ⇒ 422. |
| SEC-007 | مصادقة الأدمن: Fortify، كلمة مرور ≥ 12 حرفاً مع فحص `Password::uncompromised()`، 2FA TOTP إلزامي قبل الوصول لأي صفحة إدارة، رموز استرداد. | tests. |
| SEC-008 | جلسة الأدمن: انتهاء بعد 30 دقيقة خمول، تدوير معرف الجلسة عند الدخول، `password.confirm` للعمليات الحساسة (منح أدوار، نشر Ruleset، تفعيل إعدادات AI). | tests. |
| SEC-009 | التفويض بـ Policies لكل عملية إدارة؛ مبدأ العينين الأربع للاعتماد الطبي؛ لا يمكن لمستخدم منح نفسه دوراً. | tests لكل دور × عملية (مصفوفة في Module-11). |
| SEC-010 | تعقيم المحتوى (ADR-017)؛ React لا يستخدم `dangerouslySetInnerHTML` إلا لمخرجات `MarkdownRenderer` المعقمة من الخادم؛ ردود الـ AI تُعرض كنص عادي (لا Markdown/HTML) أو Markdown محدود يُعقّم على الخادم. | XSS tests بحمولات معروفة. |
| SEC-011 | الأسرار: `GEMINI_API_KEY` في `.env` فقط؛ لا متغيرات `VITE_*` حساسة؛ `config:cache` في الإنتاج؛ `APP_DEBUG=false`. | CI grep + فحص إقلاع يرفض `APP_DEBUG=true` في production. |
| SEC-012 | تدقيق التبعيات: `composer audit` و`npm audit` في CI؛ لا إصدارات عائمة في الإنتاج (lock files). | CI. |
| SEC-013 | السجلات: لا أجسام طلبات؛ معالج Monolog يحذف مفاتيح `answers`, `message`, `history`, `password`, `token`؛ لا تسجيل لاستجابات Gemini. | test يرمي استثناء داخل التقييم ويفحص السجل. |
| SEC-014 | Fail-safe: أخطاء المحرك ⇒ 503 مع SAFE-009، وليس نتيجة افتراضية "منخفضة الخطورة". | test. |
| SEC-015 | فحوص إقلاع (boot checks): رفض الإقلاع إذا (Demo mode ∧ production) أو (لا Ruleset منشور ∧ production ∧ مسارات التقييم مفعلة) — في الحالة الأخيرة تُعطّل المسارات وتعرض SAFE-009 بدل الفشل الكامل. | tests. |
| SEC-016 | النسخ الاحتياطي لقاعدة البيانات مشفر؛ لا يحتوي بيانات صحية بطبيعة التصميم. | تشغيلي. |
| SEC-017 | فصل بيانات اعتماد قاعدة البيانات: مستخدم تطبيق بصلاحيات DML فقط في الإنتاج؛ migrations بمستخدم منفصل. | تشغيلي. |
| SEC-018 | حماية حقن الـ prompt: [AI-Safety-Guardrails](../06-ai/AI-Safety-Guardrails.md) §5. | AI eval. |
| SEC-019 | لا رفع ملفات من المستخدمين العامين؛ رفع الأدمن (إن وُجد لاحقاً للصور) خارج الـ MVP. | — |
| SEC-020 | إدارة الثغرات: صفحة `/.well-known/security.txt` وجهة اتصال للإفصاح (OQ-028). | — |

## 3. نموذج الصلاحيات (ملخص — المصفوفة الكاملة في Module-11)

| العملية | Content Editor | Medical Reviewer | Super Admin |
|---|---|---|---|
| إنشاء/تعديل مسودة محتوى | ✓ | ✓ | ✓ |
| إرسال للمراجعة الطبية | ✓ | ✓ | ✓ |
| اعتماد طبي (محتوى/قاعدة/نص إرشاد/سؤال) | ✗ | ✓ (ليس على عمله) | ✗ (إلا إذا حمل الدور الطبي المتحقق منه) |
| نشر/إخفاء محتوى معتمد | ✓ | ✗ | ✓ |
| استيراد Ruleset | ✗ | ✗ | ✓ (أمر CLI) |
| اعتماد Ruleset Release | ✗ | ✓ | ✗ |
| نشر Ruleset Release | ✗ | ✗ | ✓ (بعد اعتماد طبي) |
| تعديل إعدادات AI (draft) | ✗ | ✗ | ✓ |
| تفعيل إعدادات AI | ✗ | ✗ | ✓ (مع تقرير تقييم) |
| إدارة الحسابات والأدوار | ✗ | ✗ | ✓ |
| عرض الإحصاءات المجمعة | ✓ | ✓ | ✓ |
| عرض سجل التدقيق | ✗ | جزئي (عمليات الاعتماد) | ✓ |

## 4. الاستجابة للحوادث (مختصر)

| الحادثة | الإجراء الفوري |
|---|---|
| تسريب مفتاح Gemini | تدوير المفتاح في Google Cloud، تحديث `.env`، مراجعة الفوترة. |
| اختراق حساب أدمن | تعطيل الحساب، إبطال الجلسات، مراجعة `audit_logs`، مراجعة آخر عمليات نشر، استرجاع إصدار سابق من المحتوى/القواعد. |
| خطأ في قاعدة سريرية منشورة | **مفتاح إيقاف (kill switch)**: `RAFIQ_ASSESSMENT_ENABLED=false` يعطّل J2/J3 ويعرض SAFE-009؛ أو إعادة نشر الإصدار السابق. |
| سلوك AI غير آمن | تعطيل AI من الإعدادات (`enabled=false`) أو `RAFIQ_AI_ENABLED=false`؛ تحليل حالات الفشل في مجموعة التقييم. |

## 5. ملاحظات على المهارات المستخدمة
- المتطلبات SEC-004/005/006/010/011/012 تتبع قواعد `laravel-best-practices/rules/security.md` و`http-client.md` و`config.md`.
- نموذج التهديدات بمنهجية STRIDE وفق مهارة `threat-modeling` من مكتبة `agentic-awesome-skills`.
