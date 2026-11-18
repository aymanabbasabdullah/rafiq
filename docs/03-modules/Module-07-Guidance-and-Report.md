# Module-07 — Guidance & Report (الإرشاد والتقرير)

> # DRAFT – PENDING QUALIFIED MEDICAL REVIEW
> نصوص الإرشاد المعروضة مسودات غير معتمدة.

> الحالة: Planned · ترتيب التنفيذ: **11** · التقدير: 4–5 أيام

## 1. Purpose
تحويل نتيجة المحرك إلى إرشاد مفهوم وغير تشخيصي: ما مستوى الأولوية، ما الخطوة التالية، لماذا، ما الذي تناقشه مع طبيبك، وما حدود هذه النتيجة — مع إمكانية الطباعة والحفظ المحلي.

## 2. Scope
**يشمل:** `GuidanceService`، صفحة التقرير، الطباعة (CSS print)، تنزيل ملف نصي محلي، "انقل إلى تحضير الطبيب"، المحتوى المرتبط، رسائل الحدود، "امسح كل شيء"، شارة Demo.
**لا يشمل:** تفسير AI للتقرير (ممنوع X-01)، PDF من الخادم، إرسال بالبريد.

## 3. Dependencies
M06 (النتيجة)، M05 (التدفق)، M08 (روابط المحتوى المرتبط — يُعرض "لا يوجد" إن لم تتوفر).

## 4. User Stories
- US-07-1: بصفتي مستخدماً، أريد أن أفهم ماذا أفعل الآن بجملة واحدة واضحة.
- US-07-2: بصفتي مستخدماً، أريد معرفة سبب التوصية بلغة بسيطة.
- US-07-3: بصفتي مستخدماً، أريد طباعة النتيجة لأخذها للطبيب.
- US-07-4: بصفتي مستخدماً، لا أريد أن يبدو التقرير كتشخيص.

## 5. Functional Requirements
| ID | المتطلب | PRD |
|---|---|---|
| M07-FR-01 | `GuidanceService::build(EvaluationResult, locale)` ⇒ `GuidanceReport`: `priorityLabel`, `headline`, `nextStep`, `reasons[]` (من `reason` texts للقواعد المفعلة)، `doctorTopics[]`, `relatedArticles[]`, `limitations`, `flags`, `rulesetVersion`, `generatedAt`, `demo`. | FR-060 |
| M07-FR-02 | فقط نصوص `approved` في Production؛ في Demo نصوص Draft مع شارة. نص مفقود ⇒ استثناء ⇒ 503 (لا عرض ناقص). | SAFE-011 |
| M07-FR-03 | صفحة التقرير بالترتيب: الأولوية + الخطوة التالية ⇒ صندوق الطوارئ (إن `show_emergency_criteria`) ⇒ الأسباب ⇒ موضوعات للطبيب ⇒ المحتوى المرتبط ⇒ الحدود ⇒ الإجراءات. | FR-060 |
| M07-FR-04 | طباعة/PDF عبر `window.print()` بنسخة مطبوعة مبسطة (بدون Header/Footer تفاعلي) بلغة واتجاه الواجهة. | FR-061, L10N-014 |
| M07-FR-05 | تنزيل `.txt` يُولد في المتصفح (Blob) — اسم ملف محايد `rafiq-notes-YYYY-MM-DD.txt`. | FR-061 |
| M07-FR-06 | "انقل الموضوعات إلى تحضير الطبيب": حالة React مشتركة (context) ⇒ M10 دون الخادم. | FR-062 |
| M07-FR-07 | لا Score/نسب؛ لا عبارات محظورة (SAFE-010). | FR-063 |
| M07-FR-08 | "امسح كل شيء" ظاهر. | PRIV-011 |
| M07-FR-09 | عداد `report_printed:{journey}` (Could) — حدث بلا محتوى. | — |

## 6. User Flow
evaluate ⇒ Report ⇒ (طباعة | حفظ | نقل إلى J4 | قراءة مقال مرتبط | امسح كل شيء).

## 7. UI/UX Requirements
- **PriorityBadge:** لون + أيقونة + نص (لا لون وحده — وصول)؛ P1 بارز، P2 واضح، P3/P4 هادئ.
- عنوان التقرير محايد: "خطوتك التالية" — لا "نتيجتك".
- الحدود دائماً ظاهرة: "هذا ليس تشخيصاً…".
- Print: A4، هوامش، تاريخ، إصدار القواعد، "ليس تشخيصاً"، بلا روابط تفاعلية (URLs نصية للمقالات اختيارية).
- RTL/LTR للطباعة.
- `<title>` محايد (PRIV-021).

## 8. Backend Requirements
- `GuidanceService` + `Data/GuidanceReport` + `GuidanceTextRepository` (cache بالإصدار واللغة).
- ربط `relatedArticleKeys` بمقالات منشورة في اللغة (M08)؛ تجاهل غير المتوفر.
- `AssessmentController@evaluate` يعيد `GuidanceReport` JSON (يُكمل M05).

## 9. Frontend Requirements
`features/guidance/ReportView.tsx`, `PrintableReport.tsx`, `PriorityBadge.tsx`, `ReasonList.tsx`, `DoctorTopics.tsx`, `lib/print.ts`, `features/doctor-prep/DoctorPrepContext.tsx` (مشترك مع M10).

## 10. Database Requirements
لا جديد (`guidance_texts` من M06).

## 11. Routes and Permissions
لا مسارات جديدة (الاستجابة من `/assessment/evaluate`).

## 12. Business Rules
- BR-07-1: التقرير لا يُخزن ولا يُعاد توليده من الخادم دون إعادة إرسال الإجابات.
- BR-07-2: P4 يحمل دائماً نص "ليس تأكيداً لسلامتك".
- BR-07-3: `time_sensitive` يُبرز في أعلى التقرير.
- BR-07-4: `unanswered_present` ⇒ ملاحظة "بعض الأسئلة لم تتم الإجابة عنها؛ الإرشاد مبني على ما أجبت".
- BR-07-5: لا زر AI لشرح التقرير.

## 13. Validation and Error Handling
نص مفقود/Release غير صالح ⇒ 503 + SAFE-009. فشل الطباعة (متصفحات قديمة) ⇒ رسالة بديلة لنسخ النص.

## 14. Security and Privacy
PRIV-008 (no-store، encryptHistory)، PRIV-017 (لا شبكة عند الطباعة/الحفظ)؛ T-02 (تحذير: "الملف المحفوظ على جهازك يمكن أن يراه من يستخدم الجهاز").

## 15. Testing
- Unit: `GuidanceService` (ترتيب، dedupe، flags، P4 disclaimer، نص مفقود ⇒ استثناء).
- Feature: التقرير لكل TC يحوي مفاتيح النصوص المتوقعة؛ فحص المحظورات على المخرجات؛ no-store.
- Manual: الطباعة على Chrome/Safari iOS/Android باللغتين؛ النقل إلى J4.

## 16. Acceptance Criteria
FR-060..063 محققة؛ الطباعة صحيحة الاتجاه؛ لا طلبات شبكة عند الطباعة/الحفظ؛ مراجعة UX مع المراجع الطبي (حين يتوفر).

## 17. Step-by-Step Implementation
1. `GuidanceReport` DTO + `GuidanceService` + unit tests.
2. ربطه بـ `/evaluate` + Feature tests.
3. `ReportView` + `PriorityBadge` + الأقسام.
4. `PrintableReport` + CSS print + `lib/print.ts` (txt).
5. `DoctorPrepContext` + زر النقل.
6. المحتوى المرتبط (بعد M08) + الحالة الفارغة.
7. مراجعة المحظورات والوصول.

## 18. Definition of Done
القبول + DoD العامة.

## 19. Open Questions
هل نعرض أسماء القواعد (CR-xxx) للمستخدم؟ (مقترح: لا — فقط في النسخة المطبوعة كمرجع صغير للطبيب، OQ-034)؛ صياغات P0–P4 النهائية (RQ-9).
