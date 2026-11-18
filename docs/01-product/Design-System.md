# نظام التصميم — رفيق | Rafiq

**الاتجاه:** "دقة هادئة" (Calm precision) — منتج صحي رقمي حديث: موثوق، راقٍ، هادئ، إنساني، ومتقدم تقنياً دون رموز "ذكاء اصطناعي" مستهلكة.
**الحالة:** هوية مرحلية حتى إغلاق OQ-015. المصدر الوحيد للقيم: `resources/css/app.css` (`@theme`).

## 1. المبادئ

1. **التسلسل قبل الزخرفة:** كل قسم يبدأ بـ Eyebrow ← عنوان بخط العرض ← فقرة تمهيدية (`SectionHeading`).
2. **بطاقات فقط حيث تفيد:** الرحلات بطاقات قابلة للنقر؛ المقالات والمواضيع والخصوصية قوائم تحريرية مفصولة بخطوط.
3. **الأمان لا يُخفى:** شريط الطوارئ وتنبيه علامات الخطر وحدود رفيق تبقى ظاهرة وفي مواضعها (BR-01-1، FR-004)، ولا يُستخدم اللون وحده للمعنى.
4. **الصدق البصري:** لا محاكاة لميزة غير موجودة؛ قسم رفيق الذكي معاينة تصميمية موسومة "قيد التطوير".
5. **ثنائية اللغة أصيلة:** خصائص منطقية فقط (`ms/me/ps/pe/start/end`)، الأيقونات الاتجاهية تنعكس (`rtl:-scale-x-100`)، والرسم الرئيسي ينعكس ليتبع اتجاه القراءة.

## 2. الألوان

| الرمز | القيمة | الاستخدام |
|---|---|---|
| `brand` | `#0D665E` | الأزرار الأساسية، الروابط، الأيقونات |
| `brand-strong` | `#164B50` | حالات hover، نصوص على خلفيات فاتحة |
| `brand-deep` | `#0F3438` | الأسطح الداكنة: البطل، قسم الذكاء، الشريط الجانبي للإدارة، الترحيب |
| `brand-soft` / `brand-mist` | `#E8F2EF` / `#F1F7F4` | خلفيات هادئة، أيقونات |
| `mint` | `#9FD4C4` | لمسات على الأسطح الداكنة فقط |
| `surface` / `surface-raised` | `#F7F8F5` / `#FFFFFF` | الخلفية / الأسطح المرفوعة |
| `ink` / `ink-muted` / `ink-subtle` | `#263A40` / `#4F6166` / `#6A7B7F` | النصوص (كلها AA على `surface`) |
| `accent` | `#B5683A` | لمسة دافئة نادرة (رحلة "قلق صحي") |
| `tint-learn/concern/conception/doctor` | — | تمييز الرحلات الأربع (هوية فقط، لا معنى) |
| `danger`, `warning`, `success`, `info` (+ `-soft`) | — | الحالات، دائماً مع أيقونة ونص |

## 3. الطباعة

| الدور | الخط | ملاحظات |
|---|---|---|
| العناوين (h1، h2، `.font-display`) | **Alexandria** 500/600 | يدعم العربية واللاتينية؛ `text-wrap: balance` |
| نص القراءة — العربية | **IBM Plex Sans Arabic** 400–600 | ارتفاع سطر 1.7، دون تباعد أحرف |
| نص القراءة — الإنجليزية | **Instrument Sans** 400–600 | — |

الخطوط تُنزّل وقت البناء وتُخدم من التطبيق نفسه (PRIV-006). المقاسات: h1 للبطل `4xl → 3.5rem`، عناوين الأقسام `3xl → 4xl`، نص المقال `1.0625rem → 1.125rem`.

## 4. المسافات والأشكال

- **العمود:** `Container` بعرض `max-w-6xl` وهامش جانبي 16/24/32px.
- **إيقاع الأقسام:** `py-16 md:py-24`؛ الفواصل بين الأقسام بخلفية `surface-raised` وخطوط `line`.
- **الزوايا:** أزرار وحقول بحث `rounded-full`؛ بطاقات `--radius-card` (20px)؛ ألواح كبيرة `--radius-panel` (28px).
- **الظلال:** `shadow-card` و`shadow-lift` فقط، وبقلة.

## 5. المكونات

| المكون | المسار | الغرض |
|---|---|---|
| `Button`, `ButtonLink`, `buttonClasses` | `components/ui/Button.tsx` | `primary`, `secondary`, `ghost`, `danger`, `destructive`, `inverse`, `quiet` × `sm/md/lg` (≥44px من `md`) |
| `Alert` | `components/ui/Alert.tsx` | رسائل `info/success/warning/danger` مع أيقونة |
| `Spinner`, `SkeletonLines` | `components/ui/Spinner.tsx` | حالات التحميل (تحترم تقليل الحركة) |
| `EmptyState` | `components/ui/EmptyState.tsx` | الحالات الفارغة |
| `Field`, `TextInput`, `SelectInput`, `PillCheckbox` | `components/ui/Field.tsx` | النماذج مع `aria-describedby` و`aria-invalid` |
| `Badge`, `Card`, `Panel`, `PageHeader`, `StatCard` | `components/ui/*` | واجهة الإدارة |
| `ConfirmDialog` | `components/public/ConfirmDialog.tsx` | حوار أصلي `<dialog>` (حبس التركيز وEsc) |
| `Container`, `Section`, `SectionHeading`, `Eyebrow` | `components/public/Section.tsx` | تخطيط الأقسام |
| `PageIntro` | `components/public/PageIntro.tsx` | افتتاحية الصفحات الداخلية (h1 واحد) |
| `SiteHeader`, `SiteFooter`, `LanguageSwitcher`, `QuickExitButton` | `components/public/*` | التنقل؛ الخروج السريع ظاهر دائماً (FR-005) |
| `JourneyCard` (`compact`/`detailed`) | `components/public/JourneyCard.tsx` | بطاقات الرحلات الأربع |
| `HeroVisual`, `BrandMark`, `BrandLockup` | `components/public/*` | الهوية البصرية |
| `ArticleCard`, `ArticleList` | `components/learn/ArticleCard.tsx` | قوائم المقالات التحريرية |

## 6. الحركة

خفيفة وذات غرض، على `transform/opacity` فقط: ظهور تدريجي (`animate-fade-up`، `.reveal`)، رسم خط الرحلة مرة واحدة (`.draw-line`)، نبض هادئ لنقاط الرحلة (`.halo`)، رفع خفيف عند المرور (`.hover-lift`). كل ذلك يتوقف مع `prefers-reduced-motion`.

## 7. الصفحة الرئيسية — البنية

البطل ← الرحلات الأربع ← كيف يعمل (3 خطوات بخط متصل) ← رفيق الذكي (معاينة موسومة "قيد التطوير" + ما لا يفعله) ← معاينة مركز المعرفة (أحدث مقال + فهرس المواضيع من قاعدة البيانات) ← الخصوصية ← عن رفيق وحدوده (مفتوحة دائماً) ← الأسئلة الشائعة ← دعوة ختامية.
حُذف قسم "ماذا يقدم رفيق" لأنه يكرر الرحلات وقسم الذكاء.

## 8. الإدارة

نفس الرموز والخطوط، بطابع وظيفي: شريط جانبي `brand-deep` بمؤشر نشط `mint` على جهة البداية، محتوى فاتح كثيف المعلومات، دون زخارف.
