# استراتيجية الاختبار (Testing Strategy)

> الحالة: Draft · آخر تحديث: 2026-11-15 · مبنية على مهارة `testing-best-practices` المحلية: المشروع يستخدم **PHPUnit**؛ اختبار السلوك الملاحظ؛ Feature test أولاً لكل ما يمكن الوصول إليه عبر طلب؛ Unit فقط للمنطق غير المعتمد على الإطار (مثل `ConditionEvaluator`)؛ لا إضافة أدوات اختبار أو متصفحات دون موافقة المالك.

## 1. هرم الاختبار

| المستوى | الأداة | النطاق | يُشغّل في |
|---|---|---|---|
| Unit (PHP) | PHPUnit | `ConditionEvaluator`, `Priority`, `ArabicNormalizer`, `ChunkBuilder`, المرشحات، `OutputPostFilter`, `PromptBuilder`, `ForbiddenPhraseChecker` | CI |
| Feature (PHP) | PHPUnit + Laravel testing | كل endpoint: البوابات، التحقق، التفويض، الترويسات، عدم التخزين، Http fake للـ AI، سير العمل الإداري، مصفوفة الصلاحيات | CI (SQLite) + مجموعة `mysql` |
| Clinical regression | PHPUnit data provider + `rafiq:clinical:test` | TC-01..TC-37 | CI + قبل كل Release |
| Architecture | PHPUnit (فحص namespaces/imports) | ADR-020، لا `env()` خارج config، لا `dd/dump` | CI |
| Static | Pint، `tsc --noEmit`، ESLint، فحوص grep (lang keys، أصناف فيزيائية، أسرار) | الكل | CI |
| Frontend unit (اختياري) | Vitest + Testing Library (يحتاج موافقة) | آلة الحالات، Quick Exit، الطباعة | CI |
| E2E (اختياري) | Playwright أو Dusk (GAP-12، OQ-027) | المسارات الأربعة، مراقبة الشبكة لـ J4، RTL | قبل الإصدار |
| Accessibility | axe-core / Lighthouse | الصفحات الرئيسية ar/en | قبل الإصدار + CI (Should) |
| Performance | Lighthouse CI؛ اختبار زمن المحرك | NFR-001..003 | قبل الإصدار |
| Security | `composer audit`, `npm audit`، فحص الترويسات، مراجعة كود أمنية | الكل | CI + M12 |
| AI evaluation | `rafiq:ai:eval` | AI-Evaluation-Plan | staging (خارج CI) |
| Manual / UX | قوائم تحقق، قارئ شاشة، أجهزة حقيقية، 5+ مستخدمين | — | نهاية كل مرحلة |

## 2. مبادئ

1. **العزل:** `Http::preventStrayRequests()` في كل الاختبارات؛ لا اتصال بـ Gemini في CI؛ `Http::fake()` لكل سيناريو.
2. **البيانات:** Factories اصطناعية؛ لا بيانات حقيقية (PRIV-010)؛ قيم مكررة في متغيرات.
3. **الوقت:** `travel()`/`freezeTime()` للاحتفاظ والجلسات.
4. **التسمية:** `test_<behavior>_<condition>` ووصف بالإنجليزية في الكود؛ مجموعات `#[Group('clinical')]`, `#[Group('mysql')]`, `#[Group('ai')]`.
5. **اختبار "ما لا يحدث":** عدد صفوف الجداول ثابت بعد التقييم؛ حمولة Gemini خالية من المحظور؛ السجل خالٍ من الإجابات؛ لا طلبات Google عند التصفح.
6. **الأمان:** لكل endpoint: غير مصادق/غير مفوض/تحقق/CSRF/throttle.

## 3. اختبارات إلزامية مرتبطة بالمتطلبات الحرجة

| المتطلب | الاختبار |
|---|---|
| PRIV-004 / SM-7 | `test_full_assessment_persists_nothing_but_aggregate_counters` |
| PRIV-005 | `test_public_session_contains_only_allowed_keys` و`test_session_store_has_no_ip_or_user_agent` |
| PRIV-009 / SEC-013 | `test_exception_during_evaluation_does_not_log_answers` |
| AI-008 / GR-05 | `test_outgoing_gemini_payload_contains_no_assessment_or_identifiers` |
| AI-012 / GR-02 | `test_red_flag_message_never_reaches_gemini` (ar/en + عامية) |
| FR-080 / GR-12 | `test_browsing_pages_makes_no_ai_requests` |
| FR-052 / TC-30..32 | boot/production tests |
| SAFE-003 | `test_author_cannot_approve_own_content_or_rule` |
| SEC-002/003 | ترويسات على المجموعات |
| L10N-010 | فحص تطابق المفاتيح |
| SAFE-010 | فحص العبارات المحظورة على الـ Ruleset والمحتوى المزروع |

## 4. بيئات الاختبار

| البيئة | DB | Session/Cache | AI |
|---|---|---|---|
| CI (افتراضي) | SQLite `:memory:` (موجود في `phpunit.xml`) | array | Http fake |
| CI (mysql group) | MySQL 8.4 service | array | Http fake |
| staging | MySQL | Redis | Gemini Paid (سقف منخفض) أو Fake |

> ملاحظة: `phpunit.xml` الحالي يضبط `SESSION_DRIVER=array` و`CACHE_STORE=array` — مناسب. اختبار "لا IP في الجلسات" يحتاج تشغيلاً على الـ driver الفعلي (اختبار تكامل في مجموعة منفصلة أو فحص الإعداد).

## 5. معايير الخروج لكل مرحلة

- كل اختبارات المرحلة خضراء؛ لا اختبارات متخطاة دون سبب موثق.
- تغطية: 100% فروع للمحرك؛ لا هدف رقمي عام آخر (التغطية ليست غاية) — لكن كل قرار (branch/validation/authorization) في الكود الجديد مغطى.
- Checklist يدوي للمرحلة (RTL/LTR، موبايل، وصول) موقّع.

## 6. Regression قبل Demo (M12)

جدول مسارات × لغات × أجهزة:

| السيناريو | ar موبايل | en موبايل | ar سطح مكتب | en سطح مكتب |
|---|---|---|---|---|
| J1 + AI explain | ☐ | ☐ | ☐ | ☐ |
| J2 P0 | ☐ | ☐ | ☐ | ☐ |
| J2 P2 + طباعة | ☐ | ☐ | ☐ | ☐ |
| J3 > 12 شهراً | ☐ | ☐ | ☐ | ☐ |
| J4 + طباعة | ☐ | ☐ | ☐ | ☐ |
| AI red flag / PII / injection | ☐ | ☐ | ☐ | ☐ |
| Admin: نشر مقال بسير الاعتماد | — | — | ☐ | ☐ |
| Admin: Ruleset run-tests | — | — | ☐ | ☐ |
| Quick Exit / Clear everything | ☐ | ☐ | ☐ | ☐ |
