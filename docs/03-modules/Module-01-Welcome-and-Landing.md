# Module-01 — Welcome & Landing (الترحيب والصفحة الرئيسية)

> الحالة: Planned · ترتيب التنفيذ: **2** · التقدير: 4–5 أيام · يتطلب مدخلات تصميم (OQ-015)

## 1. Purpose
تقديم هوية رفيق ورسالته، وشرح ما يقدمه وما لا يقدمه بوضوح، وبناء الثقة (الخصوصية، دور AI، الحدود)، وتوجيه الزائر إلى المسار المناسب — مع إتاحة طريق فوري للطوارئ.

## 2. Scope
**يشمل:** شاشة Welcome (FR-001)، Landing بستة أقسام (FR-002)، بطاقات المسارات (FR-003)، شريط علامات الخطر (FR-004)، Quick Exit (FR-005)، Header/Footer النهائيان (FR-100)، صفحات قانونية ثابتة (هيكل — المحتوى النهائي في M03)، SEO (L10N-013)، الهوية البصرية الأولية.
**لا يشمل:** Age Gate (M03)، منطق المسارات (M02)، أي CMS (النصوص من lang files في هذه المرحلة).

## 3. Dependencies
M00.

## 4. User Stories
- US-01-1: بصفتي زائراً جديداً، أريد أن أفهم خلال 10 ثوانٍ ما هو رفيق وما لا يفعله.
- US-01-2: بصفتي قلقاً على خصوصيتي، أريد معرفة ما يحدث لبياناتي قبل أن أبدأ.
- US-01-3: بصفتي شخصاً في حالة طارئة، أريد رؤية توجيه الطوارئ فوراً دون التمرير.
- US-01-4: بصفتي مستخدماً على جهاز مشترك، أريد زراً يخفي الموقع فوراً.

## 5. Functional Requirements
| ID | المتطلب | PRD |
|---|---|---|
| M01-FR-01 | Welcome تظهر مرة لكل جلسة (`welcomed` flag)، زر "ابدأ"، انتقال تلقائي بعد 4 ثوانٍ ما لم يتفاعل المستخدم أو يفضّل تقليل الحركة. | FR-001 |
| M01-FR-02 | Landing: Hero (قيمة + CTA "اختر رحلتك")، "ما هو رفيق"، "ماذا يقدم"، "كيف يعمل" (3 خطوات)، "خصوصيتك" (نقاط PRIV صادقة)، "دور الذكاء الاصطناعي"، "ما لا نقدمه"، المسارات الأربعة، FAQ مختصر. | FR-002/003 |
| M01-FR-03 | شريط الطوارئ أعلى الـ Landing وفي التذييل، بنص SAFE-009 العام (حتى اعتماد نصوص M07). | FR-004 |
| M01-FR-04 | Quick Exit: زر في Header + اختصار `Esc` مرتين؛ `location.replace(config.quick_exit_url)` بعد مسح الحالة. | FR-005 |
| M01-FR-05 | Footer: الخصوصية، الشروط، الحدود، المصادر، اللغة، الطوارئ. | FR-100 |
| M01-FR-06 | SEO: `title`/`description` مترجمة، `hreflang`, `og:locale`, `canonical`؛ Landing قابلة للفهرسة. | L10N-013 |
| M01-FR-07 | عداد `page_view:landing` و`locale_used:{locale}`. | ADM-050 |

## 6. User Flow
`/` ⇒ `/{locale}/welcome` (أول مرة) ⇒ "ابدأ" أو تلقائي ⇒ `/{locale}` Landing ⇒ CTA ⇒ `/{locale}/journeys` (M02).

## 7. UI/UX Requirements
- **النبرة:** هادئة، غير حكمية، بلا صور تشريحية أو صادمة؛ رسومات مجردة.
- **الهوية:** اسم "Rafiq | رفيق"؛ لوحة ألوان مطمئنة بتباين AA (اقتراح بالاستعانة بمهارة `ui-ux-pro-max-skill` — بحث `--domain color "healthcare calm trust"` و`typography` لخط عربي)؛ الهوية النهائية OQ-015.
- **الموبايل أولاً:** CTA رئيسي مرئي دون تمرير على 360×640؛ بطاقات المسارات عمود واحد ⇒ شبكة 2×2 على ≥ 768px.
- **RTL:** كل المسافات logical؛ أيقونات الأسهم معكوسة.
- **الحالات:** Welcome مع/بدون حركة؛ Landing؛ Quick Exit.
- **إمكانية الوصول:** ترتيب عناوين h1..h3؛ روابط تخطي "انتقل إلى المحتوى"؛ تركيز مرئي.

## 8. Backend Requirements
- `WelcomeController`, `LandingController` (Inertia render + metrics).
- `config/rafiq.php`: `quick_exit_url` (موقع محايد — OQ-033، مثلاً موقع طقس عام).
- Session flag `welcomed`.
- `SeoMeta` helper يولّد alternates.

## 9. Frontend Requirements
- `pages/Public/Welcome.tsx`, `pages/Public/Landing.tsx`.
- مكونات: `Hero`, `FeatureList`, `HowItWorks`, `PrivacyPromise`, `AiRoleSection`, `LimitsSection`, `JourneyCard`, `EmergencyStrip`, `QuickExitButton`, `SiteFooter`.
- `lib/quickExit.ts`: يمسح أي حالة (store عام إن وُجد)، `sessionStorage.clear()` (لا نستخدمه أصلاً — احتياط)، `location.replace`.

## 10. Database Requirements
لا جداول جديدة (يستخدم `daily_metrics`).

## 11. Routes and Permissions
| Method | URI | الاسم |
|---|---|---|
| GET | `/{locale}/welcome` | `public.welcome` |
| GET | `/{locale}` | `public.landing` |
| GET | `/{locale}/privacy`, `/terms`, `/limits`, `/sources` | `public.legal.*` (هياكل) |
عامة، بلا مصادقة.

## 12. Business Rules
- BR-01-1: قسم "ما لا نقدمه" لا يُطوى افتراضياً.
- BR-01-2: لا ادعاءات "معتمد طبياً" ما لم تكن كل المحتويات المعروضة معتمدة (في Demo ممنوع).
- BR-01-3: نص الخصوصية يتطابق مع Data-Inventory (PRIV-007).

## 13. Validation and Error Handling
لا مدخلات. فشل تحميل الخط ⇒ fallback system fonts. JavaScript معطل ⇒ رسالة `<noscript>` ثنائية اللغة مع رقم الطوارئ.

## 14. Security and Privacy
لا سكربتات طرف ثالث؛ لا صور من CDN خارجي؛ Quick Exit لا يترك الصفحة في التاريخ (`replace`).

## 15. Testing
- Feature: Welcome مرة واحدة لكل جلسة؛ Landing 200 باللغتين؛ وجود مفاتيح الأقسام الستة؛ hreflang صحيح؛ العدادات تزيد.
- Frontend (اختياري Vitest + Testing Library — يُضاف بموافقة): Quick Exit يستدعي `location.replace`.
- Manual: 320px/360px/768px/1280px باللغتين؛ axe بلا مخالفات حرجة؛ قارئ شاشة للـ Landing.

## 16. Acceptance Criteria
1. FR-001..005 و100 محققة باللغتين.
2. Lighthouse موبايل: Performance ≥ 90، Accessibility ≥ 95 على Landing.
3. مراجعة محتوى: الأقسام صادقة ومتسقة مع PRD.

## 17. Step-by-Step Implementation
1. جلسة تصميم قصيرة: الألوان، الخطوط (عربي + لاتيني)، شعار نصي — تحديث `@theme`. ✔ موافقة المالك.
2. Header/Footer نهائيان + مبدّل اللغة + Quick Exit. ✔ test يدوي واختبار الاختصار.
3. صفحة Welcome + flag الجلسة + احترام `prefers-reduced-motion`. ✔ Feature test.
4. Landing: الأقسام بالترتيب، النصوص في lang files (مسودة نص يكتبها المنتج، مراجعة تحريرية). ✔ snapshot المفاتيح.
5. `JourneyCard` × 4 تربط بـ `/{locale}/journeys` (أو مباشرة بالمسار مع تمرير `?` **غير مسموح** لبيانات — فقط المسار نفسه).
6. EmergencyStrip بنص SAFE-009.
7. SEO meta + hreflang. ✔ test.
8. العدادات. ✔ test.
9. صفحات قانونية هيكلية (عناوين + "قيد الإعداد" في Demo).
10. فحص الوصول والأداء وإصلاح.

## 18. Definition of Done
القبول + مراجعة RTL/LTR بصرية + Lighthouse + DoD العامة.

## 19. Open Questions
OQ-015 الهوية والخطوط؛ OQ-033 رابط Quick Exit؛ OQ-008 رقم الطوارئ المعروض؛ هل Welcome تلقائية أم بزر فقط؟ (المقترح: كلاهما).
