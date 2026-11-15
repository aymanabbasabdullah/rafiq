# Module-02 — User Journey Selection (اختيار الرحلة)

> الحالة: Planned · ترتيب التنفيذ: **4** (بعد M03) · التقدير: 2–3 أيام

## 1. Purpose
تمكين الزائر من اختيار المسار المناسب لاحتياجه (J1–J4) دون إجباره على استبيان، وتوجيهه إلى البوابات المناسبة ثم إلى نقطة دخول كل مسار.

## 2. Scope
**يشمل:** صفحة "اختر رحلتك"، نقاط دخول المسارات (صفحات هبوط قصيرة لكل مسار تشرح ما سيحدث)، التوجيه عبر البوابات، تغيير المسار، العداد المجمع.
**لا يشمل:** محتوى المسارات نفسها (M04–M10). قبل تنفيذ تلك الوحدات تعرض نقاط الدخول حالة "قريباً" في Demo فقط.

## 3. Dependencies
M00، M01، M03.

## 4. User Stories
- US-02-1: بصفتي زائراً، أريد أن أرى بوضوح الفرق بين المسارات الأربعة لأختار ما يناسبني.
- US-02-2: بصفتي مستخدماً في J1، أريد تغيير رأيي والانتقال لـ J2 دون فقدان مكاني في القراءة.
- US-02-3: بصفتي مستخدماً، لا أريد أن يُطلب مني ما لا علاقة له باحتياجي.

## 5. Functional Requirements
| ID | المتطلب | PRD |
|---|---|---|
| M02-FR-01 | صفحة `/{locale}/journeys` بأربع بطاقات (عنوان، وصف، ما ستحصل عليه، الوقت التقديري، ماذا نسألك/لا نسألك). | FR-010 |
| M02-FR-02 | صفحة دخول لكل مسار `/{locale}/learn`, `/concern`, `/conception`, `/doctor-prep` تمر بالبوابات المناسبة (مصفوفة §12). | FR-011..013 |
| M02-FR-03 | مكون "غيّر رحلتك" دائم داخل المسارات. | FR-010 |
| M02-FR-04 | لا نقل لأي إجابات بين المسارات (FR-010) باستثناء FR-062 (M07). | FR-010 |
| M02-FR-05 | عداد `journey_selected:{journey}` عند الدخول الأول في الجلسة. | FR-014 |
| M02-FR-06 | تذكير علامات الخطر الدائم في صفحات J1 وJ4 (رابط للطوارئ/J2). | FR-004 |

## 6. User Flow
Landing ⇒ `/journeys` ⇒ اختيار ⇒ (Age Gate) ⇒ (Consent لـ J2/J3) ⇒ صفحة الدخول ⇒ M04/M05 أو M08 أو M10.

## 7. UI/UX Requirements
- بطاقات قابلة للنقر بالكامل مع زر واضح؛ أيقونات محايدة.
- جملة مطمئنة: "لست مضطراً للإجابة عن أي أسئلة لتتعلّم".
- J2 بطاقة مميزة بعبارة "إذا كان لديك ألم شديد مفاجئ ⇒ الطوارئ الآن" ورابط مباشر لـ Safety Screening.
- RTL: ترتيب البطاقات يتبع اتجاه القراءة.

## 8. Backend Requirements
`JourneyController@index|enter($journey)`؛ ربط middleware حسب المسار؛ `MetricsRecorder`. Enum `Journey` بالقيم الأربع مع خصائص (`requiresConsent()`).

## 9. Frontend Requirements
`pages/Public/Journeys.tsx`؛ `components/public/JourneyCard.tsx` (مشترك مع M01)؛ `components/public/ChangeJourney.tsx`؛ صفحات دخول placeholder تُستبدل في الوحدات اللاحقة.

## 10. Database Requirements
لا جديد.

## 11. Routes and Permissions
| Method | URI | الاسم | Middleware |
|---|---|---|---|
| GET | `/{locale}/journeys` | `journeys.index` | — |
| GET | `/{locale}/learn` | `journeys.learn` | age |
| GET | `/{locale}/concern` | `journeys.concern` | age, consent, no-store |
| GET | `/{locale}/conception` | `journeys.conception` | age, consent, no-store |
| GET | `/{locale}/doctor-prep` | `journeys.doctor_prep` | age, no-store |

## 12. Business Rules
| المسار | Age | Consent | AI notice | Safety Screening |
|---|---|---|---|---|
| J1 Learn | ✓ | — | عند أول استخدام AI | — |
| J2 Concern | ✓ | ✓ | عند أول استخدام AI | ✓ إلزامي أولاً |
| J3 Conception | ✓ | ✓ | عند أول استخدام AI | ✓ مختصر ثم كامل عند الحاجة |
| J4 Doctor Prep | ✓ | — (لا شيء يُرسل) | عند أول استخدام AI | تذكير فقط |

## 13. Validation and Error Handling
`{journey}` خارج القيم ⇒ 404. وحدة المسار غير منفذة بعد ⇒ صفحة "قريباً" (غير Production فقط؛ في Production المسار مخفي من البطاقات عبر feature flag `rafiq.journeys.enabled`).

## 14. Security and Privacy
لا بيانات تُجمع. العداد مجمّع. `no-store` لصفحات J2/J3/J4.

## 15. Testing
Feature: كل مسار × حالات البوابة؛ العداد مرة لكل جلسة؛ مسار معطل بالـ flag مخفي و404؛ `no-store` موجود.

## 16. Acceptance Criteria
المصفوفة §12 مطبقة ومختبرة؛ تغيير المسار يعمل من أي صفحة مسار؛ لا استبيان في J1.

## 17. Step-by-Step Implementation
1. Enum `Journey` + `config('rafiq.journeys.enabled')`.
2. `JourneyController` + المسارات + middleware المصفوفة + tests.
3. صفحة Journeys + البطاقات.
4. صفحات دخول placeholder + `ChangeJourney`.
5. العداد + test.

## 18. Definition of Done
القبول + DoD العامة.

## 19. Open Questions
هل نعرض "اختبار سريع لمعرفة المسار المناسب"؟ (مقترح: لا في الـ MVP — يخالف PP-7). هل J3 مناسب للشريكة كمستخدمة؟ (OQ-020).

---

## 20. ملاحظات التنفيذ (2026-11-15)

**الحالة: منفذ** على الفرع `feature/m00-foundation` (دون commit).

| البند | ما نُفذ | ملاحظة |
|---|---|---|
| صفحة اختيار الرحلة (M02-FR-01) | `GET /{locale}/journeys` (`journeys.index`) تعرض البطاقات الأربع مع العنوان، الوصف، ما ستحصل عليه، الوقت التقديري، وما نسألك/لا نسألك، مع جملة "لست مضطراً للإجابة عن أي أسئلة لتتعلّم" وصندوق تنبيه طوارئ بارز ورابط مباشر لفحص علامات الخطر (J2) | بطاقات الـ Landing أصبحت تعمل كروابط مباشرة للمسارات، وزر الـ Hero الرئيسي ينقل إلى `/{locale}/journeys`. |
| نقاط دخول المسارات والمصفوفة (M02-FR-02, §12) | `/{locale}/learn` (`age`)، `/{locale}/concern` (`age`, `consent`, `no-store`, `encrypt-history`)، `/{locale}/conception` (`age`, `consent`, `no-store`, `encrypt-history`)، `/{locale}/doctor-prep` (`age`, `no-store`, `encrypt-history`) | بعد اجتياز الموافقة ينتقل الزائر مباشرة إلى المسار الذي اختاره (`concern` أو `conception`)، وخيار "ليس الآن — أفضّل التعلّم فقط" يوجه إلى `/{locale}/learn`. |
| إشعار J4 المحلي (M03-FR-08, FR-092) | تنبيه خصوصية سياقي في صفحة دخول `doctor-prep`: ما تكتبه يبقى على جهازك فقط ولا يُرسل لأي خادم | عرض فقط، بلا تخزين. |
| تذكير علامات الخطر (M02-FR-06) | مكوّن `RedFlagReminder` دائم في صفحتي دخول J1 (`learn`) وJ4 (`doctor_prep`) يعرض توجيه الطوارئ ورابطاً مباشراً إلى مسار J2 (`concern`) | يُخفى الرابط إذا كان مسار `concern` معطلاً بالـ flag. |
| تغيير المسار (M02-FR-03/04) | مكوّن `ChangeJourney` دائم أسفل كل صفحة مسار يعرض المسارات الأخرى المفعلة مع تنبيه أن الانتقال لا ينقل أي إجابات سابقة | — |
| التحكم بالمسارات (`rafiq.journeys.enabled`) | `Journey::isEnabled()` و`Journey::cards()`؛ تعطيل مسار يخفيه من Landing و`/{locale}/journeys` و`ChangeJourney` ويعيد `404` قبل البوابات وبعدها ويرفضه في `intended` | — |
| العداد المجمع (M02-FR-05) | `journey_selected:{learn\|concern\|conception\|doctor_prep}` يُسجل مرة واحدة لكل مسار في الجلسة (`journeys_selected.{key}`) | أضيف `journeys_selected` إلى `rafiq.public_session_keys` (مفاتيح منطقية للمسارات التي دُخلت في الجلسة فقط). |
| الخصوصية ومحركات البحث (PRIV-021) | صفحات دخول المسارات (`Public/JourneyEntry`) وصفحات البوابات تحمل `<meta name="robots" content="noindex">` وعنوان تبويب محايداً | صفحة `/{locale}/journeys` العامة تحتفظ بروابط `canonical` و`hreflang`. |
| خلل اكتُشف وأُصلح | `EncryptedStore` في جلسات Laravel لا يملك الدالة `boolean()` التي يملكها `Request` ⇒ استُبدلت بمقارنة صريحة `=== true` في `JourneyController` | كشفته اختبارات الـ Feature فوراً. |
| الاختبارات | 102 اختباراً ناجحاً (1,155 assertion) — منها 14 اختباراً جديداً (مع حالات DataProvider = 20 حالة) في `JourneysTest` | تغطي مصفوفة المسارات × البوابات، العداد مرة لكل جلسة، تعطيل المسار بالـ flag، ترويسات `no-store` و`encryptHistory` و`noindex`، تبديل اللغة، ومفاتيح الجلسة. |

