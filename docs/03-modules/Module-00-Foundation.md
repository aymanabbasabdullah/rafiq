# Module-00 — Foundation (الأساس التقني)

> الحالة: Planned · ترتيب التنفيذ: **1** · التقدير: 4–6 أيام عمل · المالك: Tech Lead

## 1. Purpose
تحويل هيكل Laravel 13 الفارغ إلى أساس جاهز لرفيق: Inertia + React + TypeScript، ثنائية اللغة واتجاه RTL/LTR، قاعدة البيانات، جلسات تحترم الخصوصية، ترويسات الأمن، أدوات الجودة وCI. كل Module لاحق يعتمد عليه.

## 2. Scope
**يشمل:** تثبيت Laravel Boost (ADR-018)؛ Inertia/React/TS؛ Tailwind 4 مع Logical utilities؛ `SetLocale` ومسارات `/{locale}`؛ lang files؛ `DirectionProvider`؛ `PublicLayout`/`AdminLayout` هيكلية؛ MySQL؛ جلسات Redis/file مشفرة؛ `SecurityHeaders`, `NoStoreCache`؛ `config/rafiq.php`؛ Boot checks؛ Enums الأساسية؛ صفحات الأخطاء؛ معالج السجلات الحذّاف؛ ESLint/Prettier/Pint؛ CI.
**لا يشمل:** أي محتوى أو منطق سريري أو AI أو مصادقة أدمن (M11-A).

## 3. Dependencies
لا شيء. يتطلب: موافقة المالك على تثبيت الحزم (GAP-01..05, 07, 08, 13) وقرارات ADR-002/004/011/015/016.

## 4. User Stories
- US-00-1: بصفتي مطوراً، أريد مشروعاً يعمل بـ Inertia+React+TS لأبني الصفحات بأنواع آمنة.
- US-00-2: بصفتي مستخدماً عربياً، أريد أن تفتح المنصة بالعربية وباتجاه RTL صحيح.
- US-00-3: بصفتي مستخدماً، أريد ألا تحتفظ المنصة بعنوان IP أو بصمة متصفحي في جلساتها.
- US-00-4: بصفتي Tech Lead، أريد CI يمنع دمج كود يكسر الاختبارات أو يستخدم أصناف CSS فيزيائية.

## 5. Functional Requirements
| ID | المتطلب | PRD |
|---|---|---|
| M00-FR-01 | تثبيت Boost وإعادة قراءة `AGENTS.md`. | TC-05 |
| M00-FR-02 | Inertia root view `resources/views/app.blade.php` مع `lang`/`dir` من الخادم و`@vite` لـ `app.tsx`. | L10N-004 |
| M00-FR-03 | `SetLocale` middleware: يقرأ `{locale}` من المسار، يرفض غير `ar|en` بـ 404، يخزّن في الجلسة، يضبط `App::setLocale`. | L10N-002 |
| M00-FR-04 | مسار `/` يعيد التوجيه إلى `/{locale}` (الجلسة ⇒ Accept-Language ⇒ `ar`). | L10N-002 |
| M00-FR-05 | Shared props في `HandleInertiaRequests`: `locale`, `dir`, `translations` (مجموعة الصفحة فقط), `alternateUrls`, `demoMode`, `emergency` (رقم وإعداد البلد), `gates` (age/consent/aiNotice كقيم منطقية). | — |
| M00-FR-06 | `useT()` وفحص CI لتطابق مفاتيح `ar.json` و`en.json`. | L10N-010 |
| M00-FR-07 | جلسات مشفرة بلا IP/UA (ADR-004)، lifetime 30. | PRIV-005 |
| M00-FR-08 | `SecurityHeaders` (SEC-002) و`NoStoreCache` (SEC-003) كـ middleware قابلة للتطبيق على مجموعات مسارات. | SEC-002/003 |
| M00-FR-09 | Boot checks (SEC-015، FR-053). | — |
| M00-FR-10 | صفحات أخطاء Inertia ثنائية اللغة 404/419/429/500/503 (503 تتضمن SAFE-009). | FR-101 |
| M00-FR-11 | معالج Monolog يحذف المفاتيح الحساسة (SEC-013)؛ `dontFlash` موسع. | PRIV-009 |
| M00-FR-12 | `DailyMetric` model + `MetricsRecorder::increment($metric, $dimension)` (أساس فقط). | ADR-014 |
| M00-FR-13 | CI pipeline (GAP-13). | NFR-008 |

## 6. User Flow
لا تدفق مستخدم؛ تدفق تقني: زيارة `/` ⇒ redirect `/ar` ⇒ صفحة placeholder "Landing" تعرض `dir=rtl` وتبديل لغة يعمل ⇒ `/en` LTR.

## 7. UI/UX Requirements
- `PublicLayout`: Header (شعار نصي مؤقت، مبدّل لغة، زر Quick Exit placeholder)، `DemoBanner` (عند `demoMode`)، Footer (روابط placeholder).
- خط عربي + لاتيني مستضاف ذاتياً (GAP-10، PRIV-006) — يُحسم الاختيار بالتنسيق مع M01؛ **إزالة/استبدال Bunny fonts** إن كان يجلبها من CDN خارجي وقت التشغيل.
- Design tokens أولية في `@theme` (ألوان، مسافات، أنصاف أقطار) — الهوية النهائية في M01.
- حالات: تحميل صفحة Inertia (progress bar)، خطأ.

## 8. Backend Requirements
1. `composer require laravel/boost --dev && php artisan boost:install` (بموافقة).
2. `inertiajs/inertia-laravel` + middleware `HandleInertiaRequests` مسجّل في `bootstrap/app.php` لمجموعة `web`.
3. `config/rafiq.php`: `locales`, `default_locale`, `demo_mode`, `assessment_enabled`, `ai.enabled`, `emergency` (حسب البلد — قيمة placeholder حتى OQ-008), `retention`.
4. Enums: `Locale`, `Journey`, `Priority`, `ReviewStatus`, `PublishStatus`, `AdminRole`.
5. Middleware: `SetLocale`, `SecurityHeaders`, `NoStoreCache`.
6. `AppServiceProvider::boot()`: boot checks؛ `Model::shouldBeStrict(! app()->isProduction())`؛ `DB::prohibitDestructiveCommands(app()->isProduction())`.
7. `.env.example`: المفاتيح الجديدة بقيم فارغة (`GEMINI_API_KEY=` إلخ) — **لا تُعدّل `.env` الحقيقي إلا محلياً بواسطة المطور**.
8. migration `daily_metrics`؛ تعديل/استبدال إعداد الجلسات.

## 9. Frontend Requirements
1. `react`, `react-dom`, `@inertiajs/react`, `@vitejs/plugin-react`, `typescript`, `@types/*`.
2. `vite.config.js` ⇒ `vite.config.ts` مع `input: ['resources/css/app.css','resources/js/app.tsx']` ومكوّن React.
3. `tsconfig.json` strict + alias `@/` ⇒ `resources/js`.
4. `app.tsx`: `createInertiaApp` مع `resolve` لـ `pages/**`، و`DirectionProvider`.
5. `lib/i18n.ts`, `lib/direction.tsx`, `lib/http.ts` (postJson مع XSRF، بدون console logging للأجسام).
6. ESLint: `react`, `react-hooks`, `@typescript-eslint`؛ قاعدة مخصصة/`no-restricted-syntax` لمنع النصوص الحرفية في JSX (`react/jsx-no-literals` مع استثناءات)؛ سكربت grep لأصناف `ml-|mr-|pl-|pr-|left-|right-|text-left|text-right`.

## 10. Database Requirements
- MySQL 8.4، `utf8mb4_unicode_ci` (أو `utf8mb4_0900_ai_ci`)، `ngram_token_size=2` (يتطلب إعداد الخادم — يوثق في README المشروع).
- جدول `daily_metrics` (Database-Design §3.5).
- قرار الجلسات: إن بقي driver `database` ⇒ migration تعديل لحذف `ip_address`/`user_agent` + handler مخصص؛ وإلا لا يُستخدم الجدول.
- لا جداول أخرى.

## 11. Routes and Permissions
| Method | URI | الاسم | Middleware |
|---|---|---|---|
| GET | `/` | `root` | web |
| GET | `/{locale}` | `public.landing` (placeholder) | web, SetLocale, SecurityHeaders |
| GET | `/up` | health (موجود) | — |
لا صلاحيات.

## 12. Business Rules
- BR-00-1: اللغات المدعومة فقط `ar`, `en`؛ أي غيرهما 404.
- BR-00-2: `APP_ENV=production` ∧ `demo_mode` ⇒ فشل الإقلاع.
- BR-00-3: لا متغيرات `VITE_*` تحمل أسراراً.
- BR-00-4: كل نص واجهة من lang files.

## 13. Validation and Error Handling
- locale غير صالح ⇒ 404 ثنائي اللغة (بلغة الجلسة أو الافتراضية).
- 419 (CSRF) ⇒ صفحة "انتهت الجلسة" مع زر إعادة تحميل.
- استثناءات غير متوقعة ⇒ 500 Inertia، والسجل بلا مفاتيح حساسة.

## 14. Security and Privacy
SEC-001..004, 011, 012, 013, 015؛ PRIV-005, 006, 009. مراجعة: لا سكربتات خارجية؛ CSP مع nonce لـ Vite في التطوير (يُتحقق من توافق Vite dev server مع CSP — في local قد تُخفف السياسة بشروط بيئة).

## 15. Testing
| النوع | الاختبار |
|---|---|
| Feature | `/` يعيد التوجيه حسب Accept-Language؛ `/fr` ⇒ 404؛ `/ar` يحمل `dir=rtl`؛ ترويسات الأمن موجودة؛ `no-store` على مجموعة محمية؛ محتوى الجلسة = المفاتيح المسموحة فقط |
| Unit | Enums (`Priority::max`)؛ معالج السجلات يحذف المفاتيح |
| Boot | Demo + production يرمي استثناء |
| CI | `pint --test`، `phpunit`، `tsc --noEmit`، `eslint`، `vite build`، `composer audit`، `npm audit`، مقارنة مفاتيح lang، grep الأصناف الفيزيائية |

## 16. Acceptance Criteria
1. `npm run build` و`php artisan test` ينجحان؛ CI أخضر.
2. `/ar` و`/en` يعرضان placeholder بالاتجاه الصحيح والتبديل يحافظ على المسار.
3. لا IP/UA في أي تخزين جلسات (تحقق يدوي + test).
4. الترويسات SEC-002 موجودة.
5. ملف `.env.example` محدّث ولا أسرار في Git.

## 17. Step-by-Step Implementation
1. **(موافقة)** تثبيت Boost؛ `boost:install`؛ قراءة `AGENTS.md` المولد؛ commit.
2. إعداد MySQL محلياً في Laragon؛ إنشاء DB `rafiq` بـ utf8mb4؛ تحديث `.env` المحلي (يدوياً) و`.env.example`؛ `php artisan migrate`. ✔ تحقق: الاتصال يعمل.
3. قرار الجلسات (ADR-004): Redis محلياً (Laragon) ⇒ `SESSION_DRIVER=redis`, `SESSION_ENCRYPT=true`, `SESSION_LIFETIME=30`. ✔ test محتوى الجلسة.
4. تثبيت Inertia server + `HandleInertiaRequests` + `app.blade.php`. ✔ صفحة Inertia بسيطة تعمل.
5. تثبيت React/TS/plugin-react؛ `vite.config.ts`؛ `tsconfig.json`؛ `app.tsx`. ✔ `tsc --noEmit` و build.
6. Enums + `config/rafiq.php` + boot checks. ✔ unit/boot tests.
7. `SetLocale` + مسارات `/{locale}` + redirect الجذر + `lang/ar.json`, `lang/en.json` + shared translations + `useT()` + `DirectionProvider`. ✔ Feature tests للغة.
8. `PublicLayout` هيكلي + `DemoBanner` + مبدّل اللغة (يستخدم `alternateUrls`). ✔ تبديل يحافظ على المسار.
9. `SecurityHeaders` + `NoStoreCache`. ✔ tests.
10. صفحات الأخطاء. ✔ tests لكل رمز.
11. معالج السجلات + `dontFlash`. ✔ test.
12. `daily_metrics` + `MetricsRecorder`. ✔ unit (upsert ذري).
13. ESLint/Prettier + سكربتات الفحص (lang keys، physical classes). ✔ تعمل محلياً.
14. CI workflow (SQLite + MySQL jobs). ✔ أخضر على PR.
15. تحديث `README.md` للمشروع بخطوات الإعداد (لا يمس `docs/`).

## 18. Definition of Done
معايير القبول + مراجعة كود + لا تحذيرات TS/ESLint + التوثيق محدث + Threat-Model T-21 مغلق + [Definition-of-Done](../07-project-plan/Definition-of-Done.md) العامة.

## 19. Open Questions
OQ-007 (اللغة الافتراضية)، OQ-011 (الاستضافة ⇒ Redis متاح؟)، OQ-015 (الخطوط والهوية)، OQ-018 (موافقة Boost)، اختيار أداة CI (GitHub Actions مفترض).

---

## 20. ملاحظات التنفيذ (2026-11-15)

**الحالة: منفذ** على الفرع `feature/m00-foundation` (غير مدموج، غير مُرسل في commit).

| البند | ما نُفذ فعلياً | ملاحظة / انحراف عن الخطة |
|---|---|---|
| Laravel Boost | `laravel/boost ^2.10` + `boost:install` (guidelines, skills, MCP) | أضاف `.claude/skills/*` و`.mcp.json` و`boost.json` وأعاد توليد `CLAUDE.md`؛ ملفات `.claude/` الأصلية لم تتغير. أنشأ أيضاً `.cursor/` (مُتجاهل في Git). |
| Inertia | `inertiajs/inertia-laravel` **v3.7** + `@inertiajs/react` **v3.11** | v3: لا Axios؛ عميل XHR مدمج (`lib/http.ts`). **DevTools في v3 تسجّل الطلبات على القرص افتراضياً في `local`** ⇒ عُطّلت افتراضياً في `config/inertia.php` (PRIV-009). SSR معطّل افتراضياً. `useHttp` يحفظ البيانات في التاريخ ما لم يُستخدم `dontRemember` — يجب مراعاته في M05. `encryptHistory` يتطلب HTTPS (يهم M03). |
| React / TS | React 19.3، TypeScript **6.0** (strict) | ثُبّت TS على `~6.0` لأن `typescript-eslint` لا يدعم TS 7 بعد؛ ESLint على `^9` لتوافق `eslint-plugin-react`. |
| قاعدة البيانات | MySQL 8.4 (`rafiq`، `rafiq_testing`) utf8mb4، `ngram_token_size=2` | الاختبارات: SQLite افتراضياً + MySQL في CI. |
| الجلسات | `SESSION_DRIVER=file`، مشفرة، 30 دقيقة | امتداد phpredis غير مثبت في Laragon ⇒ استُخدم `file` محلياً (لا يخزن IP/UA — مُختبر). Redis يبقى خيار الإنتاج (يتطلب phpredis أو `predis/predis`). |
| اللغة | `/{locale}` (`ar` افتراضي)، `lang/{ar,en}.json`، `useT()`، `DirectionProvider` | الـ props المعتمدة على اللغة تُحسب بشكل كسول لأن `HandleInertiaRequests` يعمل قبل `SetLocale`. |
| الأمن | `SecurityHeaders` (CSP بـ nonce، self فقط)، `NoStoreCache`، Boot checks | CSP لا يُرسل لصفحة أخطاء Laravel التصحيحية (debug + 5xx) محلياً فقط. |
| الأخطاء | صفحات Inertia ثنائية اللغة 403/404/419/429/500/503 | 500 تبقى صفحة Laravel التصحيحية عند `APP_DEBUG=true`. |
| السجلات | `ScrubSensitiveData` tap على `single` و`daily` + `dontFlash` | — |
| الإحصاءات | `daily_metrics` + `MetricsRecorder` (upsert ذري، يرفض النص الحر) | — |
| الخطوط | Instrument Sans + IBM Plex Sans Arabic (مؤقت — OQ-015) مستضافة ذاتياً عند البناء | Tailwind مقيد بمصادر التطبيق (`source(none)`) لأن الفحص التلقائي كان يشمل آلاف ملفات `.claude/`. |
| الجودة | ESLint (منع النصوص الحرفية `react/jsx-no-literals`)، Prettier، Pint، اختبار تطابق مفاتيح اللغة، اختبار منع أصناف CSS الفيزيائية | الفحوص كاختبارات PHPUnit بدل سكربتات في مجلد جديد. |
| CI | `.github/workflows/ci.yml` (frontend + backend × SQLite/MySQL + audits + فحص GEMINI في الحزمة) | لم يُشغَّل على GitHub بعد (لا push). |
| الاختبارات | 45 اختباراً / 251 assertion ناجحة على SQLite وMySQL | `ExampleTest` عُدّل لأن `/` أصبح يعيد التوجيه. |
| المعاينة | `.claude/launch.json` (`php artisan serve`) | `rafiq.test` لا يُحل محلياً (Virtual host في Laragon غير مفعّل). |
