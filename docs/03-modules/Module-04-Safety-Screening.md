# Module-04 — Safety Screening (فحص علامات الخطر)

> # DRAFT – PENDING QUALIFIED MEDICAL REVIEW
> الأسئلة والنتائج في هذه الوحدة مأخوذة من مسودة RS-2026.1 غير المعتمدة.

> الحالة: Planned · ترتيب التنفيذ: **9** · التقدير: 3–4 أيام

## 1. Purpose
اكتشاف علامات الخطر (Red Flags) قبل أي سؤال آخر، وتوجيه المستخدم فوراً إلى الطوارئ (P0) أو إلى تقييم في نفس اليوم (P1) بنصوص معتمدة، باستخدام المحرك الحتمي فقط.

## 2. Scope
**يشمل:** واجهة أسئلة الخطر (6 أسئلة كحد أقصى مع `show_if`)، endpoint `/assessment/screen`، شاشة الطوارئ P0، شاشة P1، صندوق معايير الطوارئ (SAFE-006)، النسخة المختصرة في J3، الربط مع M05.
**لا يشمل:** منطق التقييم (M06 — يُستدعى)، أسئلة التقييم العامة (M05)، التقرير (M07).

## 3. Dependencies
M03 (البوابات)، M06 (المحرك + Ruleset + أسئلة `section=safety` + نصوص الإرشاد P0/P1)، M02 (نقاط الدخول).

## 4. User Stories
- US-04-1: بصفتي شخصاً يعاني ألماً مفاجئاً شديداً، أريد أن أعرف خلال ثوانٍ أن عليّ الذهاب للطوارئ وكيف.
- US-04-2: بصفتي مستخدماً غير متأكد من وصف ألمي، أريد توجيهاً حذراً لا يتجاهل احتمال الخطر.
- US-04-3: بصفتي مستخدماً بلا علامات خطر، أريد الانتقال للأسئلة التالية بسرعة.

## 5. Functional Requirements
| ID | المتطلب | PRD |
|---|---|---|
| M04-FR-01 | جلب تعريفات أسئلة `section=safety` للمسار واللغة من Ruleset النشط عبر props الصفحة. | FR-030 |
| M04-FR-02 | عرض سؤال واحد لكل شاشة، خيارات نعم/لا/لست متأكداً كأزرار كبيرة؛ `show_if` يطبقه العميل للعرض فقط. | FR-030 |
| M04-FR-03 | عند أول "نعم" على سؤال P0 معروف (`stop_on_yes` في تعريف السؤال) يرسل العميل فوراً `/screen` دون انتظار بقية الأسئلة — لتقليل الزمن (SM-3)؛ الخادم يقيّم ما وصله ويقبل الإرسال الجزئي **فقط** عندما تكون الإجابة المرسلة تفعّل P0 (غير ذلك 422). | FR-032 |
| M04-FR-04 | `POST /{locale}/assessment/screen` ⇒ المحرك بقواعد `section=safety` ⇒ `{status: emergency|urgent|passed, priority, guidance, flags, ruleset_version, demo}`. | FR-031 |
| M04-FR-05 | شاشة P0: عنوان واضح، زر اتصال `tel:` برقم الطوارئ، تعليمات (لا تقد بنفسك، أحضر أدويتك)، "أين أقرب طوارئ؟" (نص عام — لا تحديد موقع)، لا أي محتوى آخر، لا AI. | FR-032 |
| M04-FR-06 | شاشة P1: "اطلب تقييماً طبياً اليوم" + صندوق متى تصبح طارئة + خيار "متابعة القراءة" (J1) — **لا متابعة للتقييم** (المقترح: توقف التقييم عند P1 لتجنب تمييع الرسالة — [مراجعة]). | FR-033 |
| M04-FR-07 | `passed` ⇒ انتقال إلى M05 في نفس الصفحة مع الاحتفاظ بإجابات الخطر في الحالة (تُرسل مجدداً مع التقييم الكامل). | — |
| M04-FR-08 | J3: سؤالان تمهيديان؛ yes/unsure على أي منهما ⇒ بقية أسئلة الخطر. | FR-034 |
| M04-FR-09 | عداد `assessment_started:{journey}` فقط (لا عداد للنتيجة). | ADM-050 |

## 6. User Flow
```text
Consent ⇒ Q1 (ss_current_pain_or_swelling)
   yes/unsure ⇒ Q2..Q5 (يتوقف فور yes على سؤال P0 ⇒ /screen ⇒ P0 screen)
   no ⇒ (تخطي Q2..Q5)
⇒ Q6 (ss_new_lump_or_change) ⇒ /screen
   emergency ⇒ P0 screen (نهاية)
   urgent ⇒ P1 screen (نهاية التقييم؛ متابعة القراءة)
   passed ⇒ M05
```

## 7. UI/UX Requirements
- زمن من آخر نقرة إلى شاشة P0 < 1 ثانية (SM-3) — يُعرض هيكل الشاشة فوراً مع مؤشر، ثم النص المعتمد من الاستجابة؛ **احتياط:** إذا فشل الطلب يعرض العميل نص SAFE-009 العام (مضمّن في props) مع زر الطوارئ.
- ألوان P0: لون تحذير مع تباين AA، لا وميض.
- قارئ الشاشة: `role="alert"` لعنوان P0.
- لا يمكن الرجوع من P0 إلى الأسئلة إلا بزر صريح "عودة" (لتجنب الخروج غير المقصود — ومع ذلك مسموح).
- اللغتان: رقم الطوارئ داخل `<bdi>`.

## 8. Backend Requirements
- `AssessmentController@screen` (thin) ⇒ `ScreenAssessmentRequest` ⇒ `ClinicalRulesEngine::screen(AssessmentInput)` ⇒ `GuidanceService::forScreen()`.
- `ScreenAssessmentRequest`: يبني القواعد ديناميكياً من تعريفات الأسئلة النشطة (keys/values/required/show_if) + قاعدة الإرسال الجزئي P0.
- استجابة `no-store`؛ لا كتابة.

## 9. Frontend Requirements
- `features/assessment/useAssessmentMachine.ts` — آلة حالات: `consent → safety(q_i) → screening → emergency|urgent|assessment(…) → evaluating → report`.
- `SafetyQuestion.tsx`, `EmergencyScreen.tsx`, `UrgentScreen.tsx`, `EmergencyCriteriaBox.tsx`.
- `beforeunload` تحذير أثناء الأسئلة.
- الإرسال عبر `postJson` (ADR-019).

## 10. Database Requirements
لا جديد (يقرأ `clinical_questions` وترجماتها و`guidance_texts` عبر M06، مع cache).

## 11. Routes and Permissions
| Method | URI | الاسم | Middleware |
|---|---|---|---|
| POST | `/{locale}/assessment/screen` | `assessment.screen` | age, consent, throttle:assessment, no-store |

## 12. Business Rules
- BR-04-1: الأولوية من الخادم فقط.
- BR-04-2: P0 ينهي المسار؛ P1 ينهي التقييم (مقترح).
- BR-04-3: "لست متأكداً" على سؤال P0 ⇒ P1 + صندوق المعايير (SAFE-006).
- BR-04-4: لا AI في شاشات P0/P1 (لا زر Companion).
- BR-04-5: الإرسال الجزئي مقبول فقط إذا فعّل P0.

## 13. Validation and Error Handling
TC-22/23/24/34 ⇒ 422 مع رسالة عامة ("حدث خطأ في الإجابات، أعد المحاولة") دون إظهار تفاصيل. فشل الشبكة/503 ⇒ SAFE-009 + زر الطوارئ + "أعد المحاولة". لا Ruleset ⇒ 503.

## 14. Security and Privacy
SEC-003/005/006/014؛ PRIV-003/004/009؛ T-01/T-02.

## 15. Testing
- Feature: TC-01..07, TC-09, TC-21..23, TC-34, TC-35 عبر الـ endpoint؛ الإرسال الجزئي (P0 فقط)؛ البوابات؛ no-store؛ لا تغيّر في عدد صفوف الجداول.
- Performance: p95 < 200ms.
- Manual: P0 على موبايل باللغتين؛ قارئ شاشة؛ انقطاع الشبكة.

## 16. Acceptance Criteria
كل حالات الاختبار المذكورة تمر؛ SM-3 محقق؛ مراجعة UX لشاشتي P0/P1 مع المراجع الطبي (حين يتوفر).

## 17. Step-by-Step Implementation
1. `ScreenAssessmentRequest` + tests الرفض.
2. `AssessmentController@screen` + tests TC.
3. آلة الحالات (جزء safety) + `SafetyQuestion`.
4. `EmergencyScreen` + `UrgentScreen` + الاحتياط SAFE-009.
5. المسار المختصر لـ J3.
6. العداد + `beforeunload`.
7. مراجعة أداء ووصول.

## 18. Definition of Done
القبول + Demo banner ظاهر (Draft rules) + DoD العامة.

## 19. Open Questions
OQ-008 أرقام الطوارئ؛ OQ-009 سياسة "لست متأكداً"؛ هل يتوقف التقييم عند P1 (مقترح) أم يُسمح بالمتابعة؟ (RQ جديد للمراجع)؛ RQ-8 أسئلة إضافية.
