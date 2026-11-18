<div align="center" dir="rtl">

# رفيق | Rafiq

### منصّة التوعية والتوجيه السريري الآمن في صحة الرجل الإنجابية

[![PHP 8.3+](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel 12](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![Inertia v3 + React 19](https://img.shields.io/badge/Inertia_v3-React_19-61DAFB?logo=react&logoColor=black)](https://inertiajs.com)
[![Tailwind CSS v4](https://img.shields.io/badge/Tailwind_CSS-v4-06B6D4?logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![Tests](https://img.shields.io/badge/Tests-337_Passing-0f6b62)](#الأوامر-البرمجية-واختبارات-الجودة)
[![Clinical Rules RS-2026.1](https://img.shields.io/badge/Clinical_Rules-45%2F45_Passed-0f6b62)](#محرك-القواعد-السريرية-الحتمي)
[![AI Safety Suite](https://img.shields.io/badge/AI_Safety-35%2F35_Passed-0f6b62)](#المساعد-الذكي-المقيّد-rafiq-ai-companion)

</div>

---

## نبذة عن المشروع (Overview)

**رفيق (Rafiq)** هي منصّة رقمية ثنائية اللغة (**العربية RTL** و**الإنجليزية LTR**) مصمّمة لمساعدة الرجال والأزواج على فهم صحتهم الإنجابية، واكتشاف مؤشرات الخطر مبكراً، ومعرفة خطوتهم الصحية التالية بثقة، والاستعداد لزيارة الطبيب — **دون الحاجة إلى إنشاء حساب، ودون طلب أي بيانات تعريفية، ودون حفظ أي إجابات صحية على الخادم**.

> **تنبيه سريري أساسي:** منصّة رفيق أداة توعية وفحص أولي وتوجيه للرعاية الصحية المناسبة (**Screening & Care Navigation**)، وليست أداة تشخيص طبي ولا تصف أدوية أو علاجات ولا تغني عن استشارة الطبيب المختص.

---

## صور وواجهات المشروع (Project Screenshots)

### 1. الصفحة الرئيسية والرحلات الأربع (Landing Page & Four Journeys)
![الصفحة الرئيسية والمسارات الأربعة في رفيق](docs/screenshots/hero-landing-ar.svg)

```bash
# 1. استنساخ المشروع والدخول إلى المجلد
git clone <repository-url> rafiq
cd rafiq

# 2. تثبيت حزم PHP عبر Composer
composer install

# 3. تثبيت حزم الواجهة الأمامية عبر npm
npm install
```

### 3. إعداد ملف البيئة ومفتاح التطبيق (Environment Setup)

```bash
# نسخ ملف البيئة الافتراضي
cp .env.example .env

# توليد مفتاح تشفير التطبيق
php artisan key:generate
```

افتح ملف `.env` وتأكد من إعدادات قاعدة البيانات (الافتراضي مناسب لـ Laragon):
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rafiq
DB_USERNAME=root
DB_PASSWORD=

# وضع التشغيل المعتمد (false = المحتوى والقواعد معتمدة ومنشورة دون شريط تجريبي)
RAFIQ_DEMO_MODE=false
RAFIQ_ASSESSMENT_ENABLED=true
RAFIQ_AI_ENABLED=true

# لتشغيل المساعد الذكي محلياً فوراً دون الحاجة لمفتاح خارجي اتركه fake
# أو غيّره إلى gemini وضع مفتاح Google Gemini الحقيقي في GEMINI_API_KEY
RAFIQ_AI_PROVIDER=fake
GEMINI_API_KEY=
GEMINI_MODEL=gemini-2.5-flash
```

### 4. تشغيل التهجيرات وبذر البيانات المعتمدة (Migrate & Seed)

يقوم أمر البذر بإنشاء الأدوار والحسابات التجريبية، واستيراد مقالات مركز المعرفة، ومسرد المصطلحات، وبنك أسئلة زيارة الطبيب، والإصدار السريري `RS-2026.1`، ثم **اعتمادها ونشرها طبياً بالكامل**:

```bash
php artisan migrate --seed
```

*(ويمكنك في أي وقت إعادة اعتماد ونشر كل المحتوى والقواعد بأمر واحد: `php artisan rafiq:approve-all`).*

### 5. بناء الواجهة الأمامية وتشغيل الخادم (Build & Serve)

```bash
# لبناء أصول الإنتاج
npm run build

# لتشغيل خادم التطوير المتكامل (Laravel + Queue + Vite)
composer run dev
```

أو لتشغيل خادم Laravel منفرداً:
```bash
php artisan serve
```

ثم افتح المتصفح على:
- **الواجهة العامة (العربية):** `http://localhost:8000/ar` (أو `http://rafiq.test/ar` على Laragon)
- **الواجهة العامة (الإنجليزية):** `http://localhost:8000/en`
- **لوحة التحكم (Admin):** `http://localhost:8000/admin/login`

---

## حسابات لوحة التحكم التجريبية والدخول (Demo Admin Credentials)

بعد تشغيل `php artisan db:seed` في البيئة المحلية (`local`)، تتوفر الحسابات التالية للتجربة الفورية:

| الدور | البريد الإلكتروني | كلمة المرور | الصلاحيات الرئيسية |
|---|---|---|---|
| **مدير النظام (`Super Admin`)** | `admin@rafiq.test` | `Password1234!` | إدارة الحسابات والأدوار، نشر إصدارات القواعد، إعدادات وميزانية الـ AI، سجل التدقيق الكامل |
| **محرر المحتوى (`Content Editor`)** | `editor@rafiq.test` | `Password1234!` | إنشاء وتحرير المقالات، التصنيفات، المصادر، المسرد، بنك أسئلة الطبيب، ونصوص الإرشاد |
| **مراجع طبي موثّق (`Medical Reviewer`)** | `reviewer@rafiq.test` | `Password1234!` | تشغيل اختبارات القواعد، المراجعة والاعتماد الطبي بمبدأ العينين الأربع، عرض سجل تدقيق المراجعات |

### خطوة التحقق الثنائي (2FA) عند الدخول للوحة التحكم:
بعد إدخال البريد وكلمة المرور في `/admin/login`:
1. **الدخول السريع بأكواد الاسترداد الجاهزة:** أدخل أياً من الأكواد التالية في حقل الرمز:
   `DEMO-0001` · `DEMO-0002` · `DEMO-0003` · `DEMO-0004` · `DEMO-0005` · `DEMO-0006` · `DEMO-0007` · `DEMO-0008`
   *(يُستهلك الرمز بعد استخدامه؛ ولإعادة توليد الأكواد الـ 8 شغّل: `php artisan db:seed --class=AdminSeeder`).*
2. **أو عبر تطبيق المصادقة (Google Authenticator / Authy):** المفتاح السري الموحد للحسابات التجريبية هو:
   `JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP`

---

## الأوامر البرمجية واختبارات الجودة (CLI Commands & Testing)

```bash
# تشغيل كامل حزمة اختبارات PHPUnit (337 اختباراً)
php artisan test --compact

# تشغيل حالات الاختبار السريرية القياسية (45 حالة) على الإصدار RS-2026.1
php artisan rafiq:clinical:test RS-2026.1 --files

# تشغيل تقييم الأمان الحتمي للمساعد الذكي (35 حالة أمان)
php artisan rafiq:ai:eval safety

# محاكاة 2000 زائر اصطناعي لحساب توزيع وجهات الرعاية ومؤشرات الخطر
php artisan rafiq:simulate --count=2000 --seed=2026

# اعتماد ونشر جميع المقالات والمسرد وبنك الأسئلة والإصدار السريري RS-2026.1
php artisan rafiq:approve-all

# إنشاء حساب مدير عام (Super Admin) جديد عبر سطر الأوامر
php artisan rafiq:admin:create --name="Admin" --email="root@rafiq.test" --password="StrongPassword123!" --locale=ar

# فحص الاتصال الحي بـ Google Gemini (عند ضبط RAFIQ_AI_PROVIDER=gemini)
php artisan rafiq:ai:ping

# تشغيل مهام الحذف المجدولة (حذف سجلات الاستخدام والتدقيق المنتهية)
php artisan rafiq:prune

# فحص جودة الكود (Pint + TypeScript + ESLint)
vendor/bin/pint --test
npm run typecheck
npm run lint
```

---

## هيكل المشروع ووثائق التصميم (Project Structure & Documentation)

```text
rafiq/
├── app/
│   ├── Console/Commands/       # أوامر CLI (الاستيراد، الاختبار السريري، تقييم الـ AI، المحاكاة، الحذف)
│   ├── Enums/                  # تعدادات المجال (Priority, CarePathway, RiskDomain, Journey, AdminRole...)
│   ├── Http/
│   │   ├── Controllers/Admin/  # متحكمات لوحة الإدارة (المصادقة، القواعد، المحتوى، الـ AI، الإحصاءات)
│   │   ├── Controllers/Public/ # متحكمات الزوار (Landing، المسارات، البوابات، التقييم، مركز المعرفة)
│   │   └── Middleware/         # بوابات العمر والموافقة، التحقق الثنائي 2FA، ترويسات الأمان واللغة
│   ├── Models/                 # نماذج Eloquent (مع منع التحميل الكسول ومنع تعديل سجل التدقيق)
│   ├── Policies/               # سياسات الصلاحيات ومصفوفة الأدوار
│   └── Services/
│       ├── Ai/                 # المساعد الذكي، فلاتر الأمان الحتمية، مسترجع المقاطع (RAG)، وتقييم الأمان
│       ├── Analytics/          # تسجيل وتقرير العدادات المجمّعة الآمنة مع إخفاء (< 5)
│       ├── Audit/              # خدمة سجل التدقيق مع تنقية المفاتيح الحساسة وتشفير بصمة IP
│       ├── Auth/               # خدمة التحقق الثنائي القياسية (RFC 6238 TOTP)
│       ├── Clinical/           # محرك القواعد السريرية الحتمي، مقيّم الشروط، وفاحص العبارات المحظورة
│       └── Content/            # سير عمل المقالات وبنوك النصوص، معالج Markdown، ومُقطّع الـ RAG
├── database/
│   ├── migrations/             # تهجيرات قاعدة البيانات المفهرسة والمتوافقة مع MySQL 8 وSQLite
│   └── seeders/                # بواذر البيانات التجريبية واعتماد المحتوى والقواعد (ApproveAllContentSeeder)
├── docs/                       # التوثيق الهندسي والسريري الشامل للمشروع
│   ├── 00-overview/            # ملخص المشروع وفهرس التوثيق
│   ├── 01-product/             # وثيقة متطلبات المنتج (PRD)، رحلات المستخدم، ومواصفات التصميم
│   ├── 02-architecture/        # معمارية النظام، تصميم قاعدة البيانات، الأمن، والخصوصية
│   ├── 03-modules/             # المواصفات التفصيلية وملاحظات التنفيذ للوحدات (Module-00 .. Module-12)
│   ├── 04-clinical/            # إطار السلامة السريرية، كتالوج القواعد، وسير الاعتماد الطبي
│   ├── 05-ai/                  # حوكمة الذكاء الاصطناعي، تكامل Gemini، وهندسة الأوامر
│   └── screenshots/            # صور ومعاينات واجهات المشروع
├── lang/                       # قواميس الترجمة الكاملة (ar.json و en.json — أكثر من 1130 مفتاحاً لكل لغة)
├── resources/
│   ├── clinical/               # ملفات القواعد السريرية (RS-2026.1)، حالات الاختبار، والعبارات المحظورة
│   ├── ai-eval/                # حالات اختبار الأمان الحتمي للمساعد الذكي (safety.jsonl)
│   └── js/                     # تطبيق React 19 + Inertia v3 + Tailwind CSS v4
└── tests/                      # 337 اختباراً آلياً (Feature & Unit) تغطي كل متطلبات السلامة والخصوصية
```

---

## الترخيص (License)

هذا المشروع مرخّص تحت رخصة [MIT License](https://opensource.org/licenses/MIT).
