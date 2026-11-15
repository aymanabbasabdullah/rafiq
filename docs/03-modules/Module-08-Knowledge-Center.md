# Module-08 — Knowledge Center (مركز المعرفة)

> الحالة: Planned · ترتيب التنفيذ: **6** (مع M11-B إدارة المحتوى) · التقدير: 6–7 أيام (عام + إدارة)

## 1. Purpose
مكتبة محتوى تعليمي ثنائي اللغة، موثّق المصادر، ومراجَع طبياً، عن صحة الرجل الإنجابية؛ وهي أيضاً المصدر الوحيد لتأريض الـ AI.

## 2. Scope
**يشمل (عام):** الصفحة الرئيسية للمعرفة، التصنيفات، صفحة المقال (مصادر، تاريخ المراجعة)، المسرد، البحث (POST)، المقالات المرتبطة، "غير متاح بلغتك"، نقطة تركيب زر "اشرح لي" (يُفعّل في M09).
**يشمل (خلفي):** النماذج والجداول، `MarkdownRenderer`، `ArticlePublisher`، `ChunkBuilder`، `ContentSearch`، Seeders اصطناعية للـ Demo. شاشات الإدارة في M11-B.
**لا يشمل:** الفيديو، التعليقات، التقييمات، المحتوى المولّد آلياً.

## 3. Dependencies
M00، M03 (Age Gate لـ J1)، M11-A (حسابات الأدمن للمؤلف/المعتمد).

## 4. User Stories
- US-08-1: بصفتي متعلماً، أريد تصفح موضوعات واضحة بلغتي.
- US-08-2: بصفتي قارئاً حذراً، أريد رؤية المصادر وتاريخ المراجعة الطبية.
- US-08-3: بصفتي باحثاً، أريد البحث دون أن يظهر بحثي في تاريخ المتصفح أو الرابط.
- US-08-4: بصفتي ناطقاً بالإنجليزية، أريد أن أعرف إن لم يكن المقال متوفراً بلغتي.

## 5. Functional Requirements
| ID | المتطلب | PRD |
|---|---|---|
| M08-FR-01 | `/{locale}/learn`: التصنيفات، مقالات مميزة، أحدث المراجعات، مربع بحث. | FR-070 |
| M08-FR-02 | `/{locale}/learn/{category}`: قائمة مقالات منشورة باللغة، ترتيب حتمي. | FR-070 |
| M08-FR-03 | `/{locale}/learn/{category}/{slug}`: العنوان، الملخص، المحتوى (HTML معقم)، المصادر (اسم/ناشر/رابط/تاريخ)، "آخر مراجعة طبية: التاريخ — بواسطة: Medical Reviewer" (OQ-021)، "المراجعة القادمة"، مقالات مرتبطة، مكان زر AI. | FR-071 |
| M08-FR-04 | `alternateUrls` للمقال المكافئ؛ إن لم تتوفر الترجمة المعتمدة ⇒ صفحة "غير متاح بلغتك" برابط اللغة الأخرى. | FR-072 |
| M08-FR-05 | المسرد `/{locale}/glossary` (أبجدي حسب اللغة). | FR-073 |
| M08-FR-06 | البحث: `POST /{locale}/learn/search` ⇒ JSON نتائج (عنوان، ملخص، رابط) — FULLTEXT مع تطبيع عربي؛ لا تخزين للاستعلام؛ لا تغيير URL. | PRIV-003 |
| M08-FR-07 | `ArticlePublisher::publish(translation)`: يتطلب `approved` ⇒ يعقّم ⇒ يولّد chunks (Job) ⇒ يبطل cache ⇒ audit. | ADR-008, ADR-017 |
| M08-FR-08 | Seeders Demo: ≥ 12 مقالاً اصطناعياً/تجريبياً باللغتين في 4 تصنيفات + 3 مقالات `platform_guide` + 20 مصطلحاً، معلّمة `[SYNTHETIC/DRAFT]` في Demo فقط. | PRIV-010 |
| M08-FR-09 | عدادات `page_view:learn_index|article` فقط. | ADM-050 |
| M08-FR-10 | Cache للمقالات المنشورة لكل لغة. | NFR-001 |

**تصنيفات مقترحة (للمحتوى — بانتظار المراجعة):** فهم الجهاز التناسلي الذكري؛ الألم والتورم وعلامات الخطر؛ الخصوبة والاستعداد للإنجاب؛ نمط الحياة والوقاية؛ الأدوية والعلاجات والتعرضات (معلومات عامة)؛ الفحوص الشائعة (ما هي، وليس تفسيرها)؛ التحضير لزيارة الطبيب؛ الصحة الجنسية والبولية (علمي غير صريح).

## 6. User Flow
J1 ⇒ `/learn` ⇒ تصنيف ⇒ مقال ⇒ (مصادر | مرتبط | AI | تغيير المسار) · بحث ⇒ نتائج ⇒ مقال.

## 7. UI/UX Requirements
- قراءة مريحة: عرض سطر 60–75 حرفاً (لاتيني) و50–65 (عربي)، line-height 1.7 للعربية.
- جدول محتويات للمقالات الطويلة.
- "ختم المراجعة" (ReviewStamp) أعلى المقال.
- المصادر في قسم "المراجع" مع أيقونة رابط خارجي (`rel="noopener noreferrer"`).
- حالات: تحميل، لا نتائج، غير متاح بلغتك، مقال "قيد التحديث" (review overdue — OQ-030).

## 8. Backend Requirements
Models: `ContentCategory(+Translation)`, `Article`, `ArticleTranslation`, `ArticleRevision`, `Source`, `ContentChunk`, `GlossaryTerm(+Translation)`. Services: `MarkdownRenderer` (league/commonmark، `html_input=strip`، `allow_unsafe_links=false`، روابط خارجية بـ `rel`)، `ArticlePublisher`، `ChunkBuilder` (+ `BuildContentChunks` Job)، `ContentSearch` (FULLTEXT + `ArabicNormalizer`). Controllers: `KnowledgeController@index|category|show|search`، `GlossaryController@index`. Policies للإدارة في M11-B.

## 9. Frontend Requirements
`pages/Public/Learn/{Index,Category,Article,Unavailable}.tsx`, `pages/Public/Glossary.tsx`؛ مكونات `ArticleBody` (يعرض HTML المعقم من الخادم فقط)، `SourceList`, `ReviewStamp`, `SearchBox` (postJson)، `RelatedArticles`، `AiExplainSlot` (فارغ حتى M09).

## 10. Database Requirements
Database-Design §3.2 بالكامل + عمود `text_normalized` في `content_chunks` + فهارس FULLTEXT ngram + unique (`locale`,`slug`).

## 11. Routes and Permissions
| Method | URI | الاسم | Middleware |
|---|---|---|---|
| GET | `/{locale}/learn` | `learn.index` | age |
| GET | `/{locale}/learn/{category}` | `learn.category` | age |
| GET | `/{locale}/learn/{category}/{slug}` | `learn.article` | age |
| POST | `/{locale}/learn/search` | `learn.search` | age, throttle:60,1 |
| GET | `/{locale}/glossary` | `glossary.index` | age |
> هل يتطلب المحتوى العام Age Gate؟ المقترح نعم (اتساقاً مع ADR-010)، لكن يمكن إتاحته لمحركات البحث (bots لا تمر بالبوابة؟) ⇒ القرار: صفحات المقالات **قابلة للفهرسة بدون بوابة** للقراءة فقط، والبوابة تظهر كـ overlay غير حاجب لمحركات البحث؟ ⇒ **OQ-035** (تعارض بين SEO والبوابة). الافتراض الحالي: البوابة مطلوبة للتصفح البشري، و`robots` يسمح بالفهرسة عبر عرض المقال لمن لا يملك جلسة كـ "معاينة" بدون AI.

## 12. Business Rules
- BR-08-1: النشر يتطلب `approved` لهذه الترجمة + ≥ 1 مصدر + `medically_approved_by ≠ author_id`.
- BR-08-2: لا بديل صامت للغة.
- BR-08-3: تعديل منشور ⇒ مسودة جديدة؛ المنشور يبقى.
- BR-08-4: المصادر `ai_use_restricted` تُعرض للقارئ لكن لا تدخل chunks (الـ chunks من نص رفيق فقط أصلاً).
- BR-08-5: أرشفة لا حذف.

## 13. Validation and Error Handling
slug غير موجود ⇒ 404 مترجم؛ بحث فارغ/> 100 حرف ⇒ 422؛ فشل Job التقطيع ⇒ إعادة محاولة 3 مرات ثم تنبيه في لوحة الإدارة (المقال يبقى منشوراً دون AI).

## 14. Security and Privacy
SEC-010 (XSS)؛ PRIV-003 (بحث POST)؛ لا تتبع للمقالات المقروءة لكل زائر؛ روابط خارجية `noreferrer`.

## 15. Testing
- Feature: النشر يرفض غير المعتمد/بلا مصدر/نفس المؤلف؛ عرض المقال باللغتين؛ غير متاح بلغتك؛ البحث يعيد المنشور فقط وباللغة؛ XSS payloads تُعقم؛ chunks تُولد وتُستبدل عند إعادة النشر؛ cache يُبطل.
- Unit: `ArabicNormalizer`؛ `ChunkBuilder` (أحجام، عناوين).
- MySQL group: FULLTEXT.

## 16. Acceptance Criteria
FR-070..073 محققة؛ Demo يحتوي محتوى اصطناعياً كافياً؛ لا HTML غير معقم؛ البحث العربي يجد كلمات مع/بدون تشكيل وبصيغ الألف المختلفة.

## 17. Step-by-Step Implementation
1. Migrations + Models + Factories + Enums.
2. `MarkdownRenderer` + XSS tests.
3. `ArticlePublisher` + `ChunkBuilder` + Job + tests.
4. Seeders اصطناعية (ar/en).
5. صفحات العرض العامة + ReviewStamp + SourceList.
6. "غير متاح بلغتك" + alternates.
7. `ArabicNormalizer` + `ContentSearch` + `/search` + tests (MySQL).
8. المسرد.
9. Cache + العدادات.
10. (مع M11-B) شاشات الإدارة.

## 18. Definition of Done
القبول + محتوى Demo موسوم + DoD العامة.

## 19. Open Questions
OQ-012 من يكتب المحتوى وترخيصه؛ OQ-021 إظهار اسم المراجع؛ OQ-030 سلوك المحتوى المتأخر عن المراجعة؛ OQ-035 SEO مقابل البوابة.
