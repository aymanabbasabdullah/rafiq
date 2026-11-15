# Module-03 — Privacy, Age & Consent (الخصوصية والعمر والموافقة)

> الحالة: Planned · ترتيب التنفيذ: **3** (قبل M02 لأن M02 يُسلّم إلى البوابات) · التقدير: 3–4 أيام
> **DRAFT – PENDING QUALIFIED MEDICAL REVIEW** — محتوى صفحة Under-18 (CR-001) غير معتمد.

## 1. Purpose
ضمان أن كل مستخدم: بالغ (ADR-010)، يعرف ما يحدث لبياناته قبل كل مرحلة حساسة، ويوافق صراحة على المعالجة المؤقتة لبياناته الصحية — دون جمع أي بيانات تعريفية.

## 2. Scope
**يشمل:** Age Gate (FR-020)، صفحة Under-18 (FR-023)، موافقة التقييم (FR-021)، إشعار الـ AI (FR-022)، إشعار J4 (FR-092)، Middleware البوابات، صفحات الخصوصية/الشروط/الحدود بمحتوى أولي (FR-024)، "امسح كل شيء" (PRIV-011)، Inertia history encryption (PRIV-008).
**لا يشمل:** سياسة خصوصية قانونية نهائية (تتطلب OQ-002)، Cookie banner (لا cookies غير ضرورية — لا حاجة له؛ يُذكر في السياسة).

## 3. Dependencies
M00، M01.

## 4. User Stories
- US-03-1: بصفتي مستخدماً بالغاً، أريد تأكيد عمري بنقرة دون إعطاء تاريخ ميلادي.
- US-03-2: بصفتي قاصراً، أريد أن أُوجَّه بلطف إلى من يساعدني، وأن أعرف ماذا أفعل في الطوارئ.
- US-03-3: بصفتي مستخدماً، أريد أن أفهم قبل التقييم أن إجاباتي لا تُحفظ.
- US-03-4: بصفتي مستخدماً، أريد أن أعرف قبل استخدام الـ AI أن رسالتي ستُرسل إلى Google وتُحتفظ مؤقتاً لديها.
- US-03-5: بصفتي مستخدماً، أريد مسح كل شيء بنقرة.

## 5. Functional Requirements
| ID | المتطلب | PRD |
|---|---|---|
| M03-FR-01 | Age Gate: "هل عمرك 18 عاماً أو أكثر؟" [نعم] [لا]؛ POST يخزن `age_confirmed=true|false`. | FR-020 |
| M03-FR-02 | `EnsureAgeConfirmed`: إن لم يوجد ⇒ redirect إلى البوابة مع `intended` (مسار داخلي فقط، allow-list)؛ إن كان `false` ⇒ Under-18. | FR-020 |
| M03-FR-03 | Under-18: محتوى ثابت معتمد طبياً (مسودة الآن) + SAFE-009؛ لا روابط للتقييم/AI؛ يمكن الرجوع وتصحيح الإجابة (مقصود — لا نمنع خطأ النقر). | FR-023 |
| M03-FR-04 | Assessment consent: ملخص (لا حفظ، لا هوية، معالجة مؤقتة، حدود المنصة)، checkbox غير محدد، "ابدأ"؛ `assessment_consent=true`. | FR-021 |
| M03-FR-05 | `EnsureAssessmentConsent` على J2/J3 وendpoints التقييم (JSON ⇒ 403 برسالة). | FR-021 |
| M03-FR-06 | AI notice: ما يُرسل (سؤالك + محتوى عام)، إلى من (Google Gemini)، الاحتفاظ لدى Google (SRC-007)، لا تكتب معلومات شخصية أو أعراضك، ليس نصيحة طبية؛ `ai_notice_ack=true`. | FR-022 |
| M03-FR-07 | `EnsureAiNoticeAcknowledged` على endpoint الـ AI ⇒ 409 برمز `ai_notice_required`. | FR-022 |
| M03-FR-08 | J4 notice (عرض فقط، بلا تخزين). | FR-092 |
| M03-FR-09 | "امسح كل شيء": POST `/session/clear` ⇒ `session()->invalidate()` + `regenerateToken()`؛ الواجهة تمسح الحالة و`router.clearHistory()`. | PRIV-011 |
| M03-FR-10 | تفعيل `encryptHistory` لمجموعات صفحات J2/J3/J4/AI. | PRIV-008 |
| M03-FR-11 | صفحات Privacy/Terms/Limits بمحتوى أولي مستند إلى Data-Inventory وScope (ملصق "مسودة" في Demo). | FR-024 |
| M03-FR-12 | العدادات: `age_gate:adult|under18`. | ADM-050 |

## 6. User Flow
```text
أي مسار ⇒ EnsureAgeConfirmed
   ├─ غير محدد ⇒ /{locale}/gate/age ⇒ [نعم] ⇒ intended  |  [لا] ⇒ /{locale}/under-18
   └─ true ⇒ J2/J3 ⇒ EnsureAssessmentConsent ⇒ /{locale}/gate/consent?journey=… ⇒ ابدأ ⇒ المسار
AI button ⇒ (ai_notice_ack?) لا ⇒ AiNotice modal ⇒ "فهمت" ⇒ POST ack ⇒ الدردشة
```
(`journey` في الـ query مقبول لأنه ليس بيانات صحية؛ قيمة من allow-list.)

## 7. UI/UX Requirements
- البوابة صفحة كاملة على الموبايل، Dialog على سطح المكتب؛ زران كبيران متساويان (لا "dark pattern").
- إشعار الموافقة ≤ 6 نقاط قصيرة + رابط "اقرأ المزيد".
- AI notice كـ Dialog قابل للوصول (focus trap، Esc يغلق دون إقرار).
- "امسح كل شيء" في Header (قائمة) وفي نهاية التقرير وJ4، مع تأكيد.
- حالات: أول زيارة، عودة ضمن الجلسة (لا إعادة سؤال)، انتهاء الجلسة (تُسأل مجدداً).

## 8. Backend Requirements
- `GateController@showAge|storeAge|showConsent|storeConsent|storeAiNotice`، `SessionController@clear`، `LegalPageController`.
- Form Requests: `StoreAgeGateRequest` (`is_adult` boolean required)، `StoreConsentRequest` (`journey` in [concern, conception]، `accepted` accepted)، `StoreAiNoticeRequest`.
- Middleware الثلاثة + تسجيل aliases.
- `intended` allow-list: أسماء مسارات عامة معروفة فقط (منع open redirect).

## 9. Frontend Requirements
- `pages/Public/Gate/Age.tsx`, `Gate/Consent.tsx`, `Under18.tsx`, `Legal/*`.
- `features/ai-chat/AiNotice.tsx` (يُستخدم في M09).
- `components/public/ClearEverythingButton.tsx`.
- Inertia `encryptHistory` config.

## 10. Database Requirements
لا جداول. الحالة في الجلسة فقط.

## 11. Routes and Permissions
| Method | URI | الاسم | Middleware |
|---|---|---|---|
| GET/POST | `/{locale}/gate/age` | `gate.age` / `gate.age.store` | throttle:30,1 |
| GET/POST | `/{locale}/gate/consent` | `gate.consent` / `.store` | age |
| POST | `/{locale}/gate/ai-notice` | `gate.ai-notice` | age |
| GET | `/{locale}/under-18` | `public.under18` | — |
| POST | `/{locale}/session/clear` | `session.clear` | — |
| GET | `/{locale}/privacy` · `/terms` · `/limits` | `public.legal.*` | — |

## 12. Business Rules
- BR-03-1: لا تاريخ ميلاد ولا عمر رقمي.
- BR-03-2: الموافقة لكل جلسة؛ لا تُنقل بين الجلسات.
- BR-03-3: رفض الموافقة لا يمنع J1/J4.
- BR-03-4: الـ AI يتطلب age=true ∧ ai_notice_ack=true.
- BR-03-5: Under-18 لا يصل لأي Endpoint تقييم/AI (403/redirect).

## 13. Validation and Error Handling
- قيم غير صالحة ⇒ 422 مع رسالة مترجمة.
- JSON endpoints بلا بوابة ⇒ 403/409 برموز آلية تفهمها الواجهة (`age_required`, `consent_required`, `ai_notice_required`).
- `intended` غير مسموح ⇒ يُتجاهل ⇒ Landing.

## 14. Security and Privacy
CSRF على كل POST؛ لا open redirect؛ الجلسة مشفرة؛ PRIV-012/013؛ T-02 (encryptHistory، clear)؛ T-23.

## 15. Testing
- Feature: مصفوفة (age: null/true/false) × (مسار) ⇒ السلوك المتوقع؛ consent مطلوب لـ J2/J3 فقط؛ AI ⇒ 409 قبل الإقرار؛ clear يبطل الجلسة ويغير الـ token؛ open redirect مرفوض؛ محتوى الجلسة بعد كل بوابة ضمن المفاتيح المسموحة.
- Manual: Dialogs بقارئ شاشة ولوحة مفاتيح.

## 16. Acceptance Criteria
1. لا يمكن الوصول لأي تقييم أو AI دون البوابات (tests).
2. نصوص الإشعارات صادقة وتطابق Data-Inventory (مراجعة).
3. "امسح كل شيء" يعمل ويمنع الرجوع للنتائج.

## 17. Step-by-Step Implementation
1. Middleware `EnsureAgeConfirmed` + Age Gate (backend + page) + tests.
2. Under-18 page (نص مسودة موسوم Draft).
3. Consent page + `EnsureAssessmentConsent` + tests (تطبق لاحقاً على مسارات M04/M05 — تُختبر الآن بمسار وهمي في الاختبار).
4. AI notice endpoint + middleware + مكون Dialog + tests (المكون يُدمج في M09).
5. Session clear + `ClearEverythingButton` + `clearHistory`.
6. `encryptHistory` لمجموعات المسارات.
7. صفحات Legal بمحتوى أولي + مراجعة منتج.
8. العدادات.

## 18. Definition of Done
القبول + مراجعة الخصوصية للنصوص + Threat-Model محدث (T-02، T-23) + DoD العامة.

## 19. Open Questions
OQ-002 القانون المنطبق والموافقة؛ OQ-004 كفاية التأكيد الذاتي للعمر؛ OQ-031 جهة الاتصال في السياسة؛ نص Under-18 (RQ-10).
