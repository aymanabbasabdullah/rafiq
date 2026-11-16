# Module-10 — Doctor Visit Preparation (التحضير لزيارة الطبيب)

> الحالة: Planned · ترتيب التنفيذ: **12** · التقدير: 3–4 أيام
> **ملاحظة تعديل:** هذه الوحدة تطبق ADR-009 — الـ AI لا يعالج نص المستخدم (يختلف عن Brief §4.C؛ OQ-005).

## 1. Purpose
مساعدة المستخدم على تنظيم موضوعات وأسئلة زيارته الطبية في قائمة واضحة قابلة للطباعة، دون تفسير طبي ودون أن تغادر أسئلته الشخصية جهازه.

## 2. Scope
**يشمل:** اختيار الموضوعات، بنك الأسئلة المعتمد، الأسئلة الشخصية المحلية، قوالب الصياغة المنظمة (Should)، الترتيب، "ما يجب إحضاره"، الاستيراد من التقرير (M07)، الطباعة/الحفظ المحلي، الحفظ الاختياري على الجهاز (Could)، "اشرح المصطلح" (M09 `explain_term`).
**لا يشمل:** إرسال نص المستخدم لأي خادم، حجز مواعيد، مشاركة مع الطبيب إلكترونياً.

## 3. Dependencies
M03، M08 (المسرد + إدارة بنك الأسئلة كمحتوى معتمد — جداول `doctor_question_templates` تُضاف في M11-B)، M07 (`DoctorPrepContext`)، M09 (اختياري لشرح المصطلحات).

## 4. User Stories
- US-10-1: بصفتي مريضاً، أريد قائمة أسئلة جاهزة أختار منها لأنني لا أعرف ماذا أسأل.
- US-10-2: بصفتي مريضاً، أريد إضافة أسئلتي الخاصة دون أن تُرسل لأي جهة.
- US-10-3: بصفتي مستخدماً أنهى تقييماً، أريد نقل موضوعات التقرير مباشرة إلى قائمتي.
- US-10-4: بصفتي مريضاً، أريد ورقة مطبوعة مرتبة بلغتي.

## 5. Functional Requirements
| ID | المتطلب | PRD |
|---|---|---|
| M10-FR-01 | الموضوعات (`topic_key`) من بنك الأسئلة المعتمد باللغة. | FR-090 |
| M10-FR-02 | لكل موضوع أسئلة معتمدة مع "مساعدة" قصيرة؛ إضافة/إزالة/ترتيب (أزرار أعلى/أسفل + سحب — سحب اختياري؛ الأزرار إلزامية للوصول). | FR-091 |
| M10-FR-03 | "أسئلتي": حقول نص حر حتى 20 سؤالاً × 300 حرف — **في الذاكرة فقط**. | FR-092 |
| M10-FR-04 | قوالب صياغة منظمة (Should): "منذ متى…؟"، "ما الذي قد يسبب…؟"، "ما الفحوص التي قد أحتاجها…؟"، "ما الخيارات المتاحة…؟" — يكمل المستخدم الفراغ محلياً. | ADR-009 (ج) |
| M10-FR-05 | "ما يجب إحضاره" Checklist معتمد. | FR-093 |
| M10-FR-06 | استيراد `doctorTopics` من `DoctorPrepContext` إن وُجد. | FR-062 |
| M10-FR-07 | معاينة + طباعة + `.txt` محلي. | FR-094 |
| M10-FR-08 | (Could) "احفظ على هذا الجهاز" localStorage بمفتاح محايد، معطّل افتراضياً، تحذير، زر حذف. | FR-095 |
| M10-FR-09 | زر "ما معنى هذا؟" بجانب المصطلحات المعلّمة في بنك الأسئلة ⇒ M09 `explain_term` (لا يُرسل أي نص من "أسئلتي"). | AI-002 |
| M10-FR-10 | تذكير علامات الخطر ثابت. | FR-004 |

## 6. User Flow
`/doctor-prep` ⇒ إشعار "يبقى على جهازك" ⇒ الموضوعات ⇒ الأسئلة المقترحة ⇒ أسئلتي ⇒ ما أحضره ⇒ معاينة ⇒ طباعة/حفظ ⇒ (امسح كل شيء).

## 7. UI/UX Requirements
- Stepper بأربع خطوات قابلة للتنقل الحر.
- أيقونة قفل + نص "لا يغادر جهازك" بجانب "أسئلتي".
- الطباعة: عنوان "أسئلتي لزيارة الطبيب"، التاريخ، الأقسام، أسطر فارغة للملاحظات، "رفيق لا يقدم تشخيصاً".
- حالة فارغة لكل خطوة.

## 8. Backend Requirements
- `DoctorPrepController@show` ⇒ props: الموضوعات + الأسئلة المعتمدة المنشورة + checklist + روابط المسرد (cache).
- لا endpoints تستقبل نصاً.
- Models `DoctorQuestionTemplate(+Translation)` — الإدارة في M11-B.

## 9. Frontend Requirements
`pages/Public/DoctorPrep.tsx`؛ `features/doctor-prep/{TopicPicker, QuestionBank, LocalQuestions, PhraseTemplates, BringChecklist, PrintableChecklist, useDoctorPrepState}`؛ استخدام `DoctorPrepContext`؛ `lib/print.ts`.

## 10. Database Requirements
`doctor_question_templates`, `doctor_question_template_translations` (Database-Design §3.2) مع حقول المراجعة والنشر.

## 11. Routes and Permissions
| Method | URI | الاسم | Middleware |
|---|---|---|---|
| GET | `/{locale}/doctor-prep` | `journeys.doctor_prep` | age, no-store |

## 12. Business Rules
- BR-10-1: نص "أسئلتي" لا يُرسل لأي endpoint (يُختبر).
- BR-10-2: بنك الأسئلة لا يحتوي تفسيراً ولا يقترح تشخيصاً (مراجعة طبية).
- BR-10-3: localStorage فقط بموافقة صريحة لكل جهاز.

## 13. Validation and Error Handling
حدود الطول/العدد في الواجهة؛ تعذر الطباعة ⇒ نسخ النص؛ localStorage غير متاح (وضع خاص) ⇒ إخفاء الخيار.

## 14. Security and Privacy
PRIV-017/018؛ T-02؛ لا Analytics للمحتوى (عداد `page_view:doctor_prep` و`report_printed:doctor_prep` فقط).

## 15. Testing
- Feature: الصفحة تعرض المنشور المعتمد فقط باللغة؛ no-store؛ لا مسارات POST للوحدة.
- Frontend/E2E (إن أُضيفت أداة — GAP-12): مراقبة الشبكة أثناء كتابة "أسئلتي" والطباعة ⇒ 0 طلبات تحمل النص؛ النقل من التقرير؛ localStorage opt-in/حذف.
- Manual: طباعة باللغتين.

## 16. Acceptance Criteria
FR-090..094 محققة؛ إثبات عدم إرسال النص؛ مراجعة طبية لبنك الأسئلة (حين يتوفر) أو وسم Draft في Demo.

## 17. Step-by-Step Implementation
1. Migrations/Models لبنك الأسئلة + Seeders اصطناعية (≥ 6 موضوعات × 5 أسئلة باللغتين).
2. `DoctorPrepController@show` + tests.
3. `useDoctorPrepState` + TopicPicker + QuestionBank (ترتيب بالأزرار).
4. LocalQuestions + PhraseTemplates.
5. BringChecklist + الاستيراد من Context.
6. المعاينة + الطباعة + txt.
7. (Could) localStorage opt-in.
8. ربط "ما معنى هذا؟" بـ M09.

## 18. Definition of Done
القبول + DoD العامة.

## 19. Open Questions
OQ-005 (AI لصياغة أسئلة المستخدم)؛ OQ-017 (localStorage)؛ قائمة الموضوعات النهائية.

## 20. ملاحظات التنفيذ (2026-11-16)
- **البنك:** جدولا `doctor_question_templates` (key، kind = question|bring، topic_key، sort_order) و`doctor_question_template_translations` (نص/مساعدة لكل لغة + review_status/publish_status + المعتمِد طبياً). أسماء القيود قصيرة صراحةً لحد MySQL (64 حرفاً).
- **البيانات:** `DoctorPrepDemoSeeder` ينشئ 6 موضوعات × 5 أسئلة + 6 عناصر "ما أحضره" باللغتين كمسودات (Draft) — اصطناعية، ويعمل في local/testing أو وضع Demo فقط.
- **العرض:** `DoctorPrepBank::forLocale()` يعرض المعتمد طبياً والمنشور فقط؛ المسودات تظهر فقط حين `demo_mode` خارج الإنتاج مع تنبيه "غير معتمد طبياً". `JourneyController` يعرض `Public/DoctorPrep` (بوابة العمر فقط، بلا موافقة تقييم، no-store، noindex، عداد `page_view:doctor_prep`).
- **الواجهة:** خطوات: المواضيع ← أسئلة مقترحة ← أسئلتي (حتى 20 × 300 حرف مع صيغ جاهزة) ← ما أحضره ← المعاينة (ترتيب بالأزرار، حذف، طباعة/PDF عبر المتصفح، تنزيل .txt). كل النصوص في ذاكرة React فقط.
- **النقل من التقرير:** `lib/doctorPrepHandoff.ts` (ذاكرة على مستوى الوحدة، تُمسح بعد القراءة) ينقل مواضيع الطبيب من تقرير M07 دون أي طلب شبكي.
- **BR-10-1:** اختبار يتحقق من عدم وجود أي مسار كتابة للوحدة (`DoctorPrepTest`).
- **مؤجل:** localStorage بموافقة (FR-08، Could)؛ (نُفذ لاحقاً: رابط "ما معنى هذا؟" للمسرد ورفيق الذكي، وإدارة البنك ومراجعته من اللوحة — 2026-11-16)؛ المراجعة الطبية للبنك (OQ-003).
