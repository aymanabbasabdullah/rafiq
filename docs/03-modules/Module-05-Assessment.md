# Module-05 — Assessment (التقييم)

> # DRAFT – PENDING QUALIFIED MEDICAL REVIEW
> الأسئلة مأخوذة من مسودة RS-2026.1 غير المعتمدة.

> الحالة: Planned · ترتيب التنفيذ: **10** · التقدير: 4–5 أيام

## 1. Purpose
جمع إجابات مختصرة وغير تعريفية خاصة بكل مسار (J2 وJ3) تسمح للمحرك بتحديد الأولوية والخطوة التالية، مع شرح سبب كل سؤال واحترام حق التخطي.

## 2. Scope
**يشمل:** عرض أسئلة `section=assessment` حسب المسار، أنواع الأسئلة (single/multi/boolean_unsure/band)، التخطي، "لا أعرف"، "لماذا نسأل؟"، التقدم والرجوع والبدء من جديد، الإرسال إلى `/assessment/evaluate`، تبديل اللغة دون فقدان الحالة.
**لا يشمل:** أسئلة الخطر (M04)، المحرك (M06)، عرض التقرير (M07).

## 3. Dependencies
M04 (يسبق التقييم)، M06.

## 4. User Stories
- US-05-1: بصفتي مستخدماً، أريد أسئلة قليلة وواضحة، وأعرف لماذا تُسأل.
- US-05-2: بصفتي مستخدماً، أريد تخطي ما لا أرغب في الإجابة عنه.
- US-05-3: بصفتي مستخدماً، أريد تغيير اللغة في منتصف التقييم دون أن أبدأ من جديد.
- US-05-4: بصفتي زوجاً يستعد للإنجاب، أريد أسئلة تخص هدفي لا استبياناً عاماً.

## 5. Functional Requirements
| ID | المتطلب | PRD |
|---|---|---|
| M05-FR-01 | عرض أسئلة المسار من الـ Ruleset النشط بالترتيب المعلن. | FR-040, FR-013 |
| M05-FR-02 | التخطي لكل سؤال (`null`)، وخيار "لا أعرف" حيث `unsure_allowed`. | FR-041 |
| M05-FR-03 | التحقق في العميل من تنافي `none/unknown` مع غيرها (UX)، والتحقق النهائي في الخادم. | TC-25 |
| M05-FR-04 | "لماذا نسأل هذا؟" قابل للطي لكل سؤال. | FR-045 |
| M05-FR-05 | مؤشر تقدم (n من m)، رجوع، "ابدأ من جديد" مع تأكيد يمسح كل الإجابات. | FR-044 |
| M05-FR-06 | تبديل اللغة يحافظ على الحالة: الأسئلة تُعاد جلبها بالمفاتيح نفسها (partial reload لـ `questions` prop فقط) والإجابات تبقى في React. | L10N-003 |
| M05-FR-07 | `POST /{locale}/assessment/evaluate` بإجابات الخطر + التقييم ⇒ M06/M07 ⇒ التقرير. | FR-050 |
| M05-FR-08 | لا حقول نص حر، لا أرقام مخبرية، لا تاريخ ميلاد. | FR-042 |
| M05-FR-09 | عداد `assessment_completed:{journey}`. | SM-2 |

## 6. User Flow
`passed` من M04 ⇒ مقدمة قصيرة ("حوالي 2 دقيقة، يمكنك تخطي أي سؤال") ⇒ Q1..Qn ⇒ مراجعة سريعة (قائمة الإجابات مع "تعديل") ⇒ "اعرض الإرشاد" ⇒ evaluating ⇒ M07.

## 7. UI/UX Requirements
- مكوّن `QuestionRenderer` لكل نوع: بطاقات اختيار (RadioCardGroup)، مربعات متعددة (CheckboxCardGroup) مع "لا شيء مما سبق"، نطاقات كأزرار.
- لغة الأسئلة بسيطة وغير حكمية (مثلاً "استخدمت" بدلاً من "تعاطيت").
- شاشة مراجعة قبل الإرسال.
- موبايل: أزرار ≥ 44px، شاشة لكل سؤال؛ سطح مكتب: يمكن 2–3 أسئلة مرتبطة.
- حالات: تحميل، خطأ إرسال (إعادة محاولة دون فقد)، 422 (رسالة عامة + "ابدأ من جديد").

## 8. Backend Requirements
- `AssessmentController@show($journey)` (Inertia: أسئلة safety + assessment للمسار باللغة، ruleset_version، demo flag).
- `AssessmentController@evaluate` ⇒ `EvaluateAssessmentRequest` (ديناميكي من تعريفات الأسئلة؛ يرفض أسئلة غير منطبقة على المسار TC-33) ⇒ `ClinicalRulesEngine::evaluate` ⇒ `GuidanceService::build` ⇒ JSON.
- `QuestionSetPresenter` يحوّل تعريفات الأسئلة + الترجمات إلى DTO للواجهة (cache لكل `ruleset_version`+`locale`).

## 9. Frontend Requirements
- توسعة `useAssessmentMachine` بمرحلة assessment.
- `QuestionRenderer.tsx`, `ReviewAnswers.tsx`, `WhyWeAsk.tsx`.
- الأنواع في `types/assessment.ts` (`QuestionDefinition`, `AnswerValue = string | string[] | null`).
- لا تخزين في storage.

## 10. Database Requirements
لا جديد (قراءة فقط).

## 11. Routes and Permissions
| Method | URI | الاسم | Middleware |
|---|---|---|---|
| GET | `/{locale}/concern` ، `/{locale}/conception` | `journeys.concern` / `journeys.conception` (تعرض صفحة التقييم) | age, consent, no-store |
| POST | `/{locale}/assessment/evaluate` | `assessment.evaluate` | age, consent, throttle:assessment, no-store |

## 12. Business Rules
- BR-05-1: إجابات الخطر تُرسل مجدداً مع التقييم ويعيد المحرك تقييمها (لا ثقة بنتيجة `/screen` سابقة من العميل).
- BR-05-2: التخطي لا يفعّل أي قاعدة.
- BR-05-3: "لا أعرف" لا يفعّل القاعدة ويضيف موضوعاً للطبيب.
- BR-05-4: سؤال خارج المسار ⇒ 422.

## 13. Validation and Error Handling
TC-22..25, TC-33, TC-34 ⇒ 422. حجم الجسم > 16KB ⇒ 413/422. Ruleset تغيّر أثناء الجلسة (نُشر إصدار جديد) ⇒ 409 `ruleset_changed` ⇒ الواجهة تطلب إعادة البدء (مع إرسال `ruleset_version` في الطلب).

## 14. Security and Privacy
PRIV-003/004/009؛ SEC-006؛ لا أي Analytics لمحتوى الإجابات؛ `encryptHistory`.

## 15. Testing
- Feature: TC-08, TC-10..20, TC-25..27, TC-33 عبر `/evaluate`؛ 409 عند تغير الإصدار؛ عدم كتابة أي صف (عدا العداد)؛ تبديل اللغة يعيد نفس المفاتيح.
- Unit (TS، اختياري): آلة الحالات — الرجوع، البدء من جديد، تنافي الخيارات.
- Manual: مسار كامل J2 وJ3 باللغتين على موبايل؛ قارئ شاشة.

## 16. Acceptance Criteria
TCs المذكورة تمر؛ SM-2 قابل للقياس؛ تبديل اللغة لا يفقد الحالة؛ لا نص حر.

## 17. Step-by-Step Implementation
1. `QuestionSetPresenter` + `show` + tests.
2. `EvaluateAssessmentRequest` + tests الرفض.
3. `evaluate` endpoint (يعيد نتيجة المحرك الخام مؤقتاً حتى M07) + TCs.
4. `QuestionRenderer` لكل نوع + `WhyWeAsk`.
5. التقدم/الرجوع/البدء من جديد/المراجعة.
6. تبديل اللغة مع partial reload.
7. معالجة 409/422/الشبكة.
8. العداد.

## 18. Definition of Done
القبول + DoD العامة + مراجعة لغوية للأسئلة (عربي/إنجليزي) بانتظار الطبية.

## 19. Open Questions
OQ-014 عمر الشريكة؛ هل يُسمح بأسئلة J3 لشريكة تجيب عن زوجها؟ (OQ-020)؛ صياغات الأسئلة النهائية (RQ-9).
