# Module-12 — Testing & Launch (الاختبار والإطلاق)

> الحالة: Planned · ترتيب التنفيذ: **14** (مع أنشطة مستمرة منذ M00) · التقدير: 5–7 أيام للتصليب + الإطلاق التجريبي

## 1. Purpose
ضمان أن رفيق آمن سريرياً وخاص وأمين ومتاح وثنائي اللغة قبل عرضه في المسابقة (Demo) ثم قبل أي إطلاق عام، عبر اختبارات منهجية وقوائم تحقق وبوابات قرار.

## 2. Scope
**يشمل:** تنفيذ [Testing-Strategy](../07-project-plan/Testing-Strategy.md) على مستوى النظام، اختبارات الوصول والأداء والأمن، مراجعة الخصوصية، مهام الحذف المجدولة (`rafiq:prune`)، إعداد staging/demo، بيانات Demo الاصطناعية، Checklists الإطلاق، خطة الرجوع، المراقبة.
**لا يشمل:** حملات تسويق، الإطلاق التجاري.

## 3. Dependencies
كل الوحدات M00–M11.

## 4. User Stories
- US-12-1: بصفتي محكّماً في المسابقة، أريد تجربة المسارات الأربعة والـ AI بسلاسة مع شفافية حول حالة الاعتماد.
- US-12-2: بصفتي Product Owner، أريد دليلاً موثقاً على أن المنصة لا تخزن بيانات صحية.
- US-12-3: بصفتي Medical Reviewer، أريد التأكد أن ما اعتمدته هو ما يعمل في Production.

## 5. Functional Requirements
| ID | المتطلب | PRD |
|---|---|---|
| M12-FR-01 | `rafiq:prune` مجدول يومياً (Data-Lifecycle §3). | PRIV-011 |
| M12-FR-02 | `rafiq:content:review-reminders` يومياً (تنبيه داخل اللوحة). | SAFE-013 |
| M12-FR-03 | بيئة staging/demo مع `RAFIQ_DEMO_MODE=true`، بيانات اصطناعية، Gemini Paid بسقف منخفض أو Fake. | FR-053 |
| M12-FR-04 | سكربت Demo (سيناريوهات J1–J4 + AI + Admin) للعرض. | SM-11 |
| M12-FR-05 | مراقبة: `/up`، تنبيه أخطاء 5xx، تنبيه تكلفة AI ≥ 80% من السقف. | NFR-007 |
| M12-FR-06 | خطة رجوع (rollback) للكود وللمحتوى/القواعد (الإصدار السابق) وللـ AI (تعطيل). | Security §4 |
| M12-FR-07 | DPIA + مراجعة قانونية/تنظيمية قبل الإطلاق العام. | PRIV-020, OQ-024 |

## 6. User Flow
لا تدفق مستخدم؛ تدفق الإطلاق: Code freeze ⇒ Regression ⇒ Checklists ⇒ Go/No-Go ⇒ Deploy ⇒ Smoke ⇒ Monitor 72 ساعة.

## 7. UI/UX Requirements
مراجعة نهائية: الشريط التحذيري للـ Demo في كل صفحة؛ صفحات الأخطاء؛ النصوص القانونية النهائية؛ مراجعة RTL/LTR شاملة؛ مراجعة النبرة.

## 8. Backend Requirements
الأوامر المجدولة (`routes/console.php` schedule)، إعداد السجلات (`daily`, 14 يوماً)، `config:cache`/`route:cache`/`view:cache` في الإنتاج، worker للطابور (لـ chunking فقط)، `APP_DEBUG=false`.

## 9. Frontend Requirements
بناء الإنتاج، فحص حجم الحزم (NFR-001)، تقسيم الكود (AI panel، Admin منفصل)، source maps غير منشورة علناً.

## 10. Database Requirements
Migrations نظيفة من الصفر؛ نسخ احتياطي مشفر؛ مستخدم DB بصلاحيات محدودة (SEC-017)؛ فحص Schema آلي: لا أعمدة باسم `answers`, `symptoms`, `message`, `ip_address` (عدا ما هو موثق).

## 11. Routes and Permissions
مراجعة `route:list`: لا مسارات عامة غير موثقة؛ لا `register`؛ كل `/admin` محمي؛ كل POST عام عليه throttle.

## 12. Business Rules — بوابات Go/No-Go

**بوابة Demo (المسابقة):**
- [ ] كل اختبارات CI خضراء (بما فيها TC-01..TC-37).
- [ ] شريط Demo ظاهر، ونص "غير معتمد طبياً" في الـ Landing والتقرير.
- [ ] بيانات اصطناعية فقط (PRIV-010).
- [ ] تقرير تقييم AI في staging يحقق البوابات الحرجة (RT-DX/RX/INJ = 100%).
- [ ] اختبار عدم التخزين (SM-7) وعدم إرسال المحظور لـ Gemini (AI-008) ناجحان.
- [ ] axe بلا مخالفات حرجة؛ Lighthouse ضمن الأهداف.
- [ ] إعادة التحقق من شروط Gemini (AI-019) مؤرخة.
- [ ] Gemini Paid tier مؤكد (أو Fake).

**بوابة الإطلاق العام (إضافةً لما سبق):**
- [ ] Ruleset منشور بكل عناصره `approved` (SM-5) + اعتماد طبي موقع.
- [ ] كل المحتوى المنشور معتمد طبياً لكل لغة (SM-4).
- [ ] DPIA + مراجعة قانونية للولاية (OQ-002) + رأي تنظيمي (OQ-024) + رأي حول شروط Gemini (OQ-024/R-02).
- [ ] أرقام ومسارات الطوارئ مكيّفة للبلد (OQ-008).
- [ ] اختبار اختراق أو مراجعة أمنية مستقلة (Should).
- [ ] سياسة الخصوصية النهائية منشورة.
- [ ] `RAFIQ_DEMO_MODE=false` والإقلاع يتحقق.

## 13. Validation and Error Handling
Smoke tests بعد النشر: `/up`، Landing ar/en، مقال، تقييم TC-01 وTC-08 (بيئة staging)، رسالة AI (Fake أو حقيقية)، دخول أدمن.

## 14. Security and Privacy
مراجعة أمنية للفرع (يمكن الاستعانة بـ `/security-review` و`/code-review`)؛ `composer audit`/`npm audit` نظيفة؛ فحص الترويسات (securityheaders)؛ فحص CSP؛ فحص الأسرار في Git history.

## 15. Testing
راجع [Testing-Strategy](../07-project-plan/Testing-Strategy.md). أنشطة خاصة بهذه الوحدة: Regression كامل، اختبار قابلية استخدام مع 5 مستخدمين على الأقل (بيانات اصطناعية/سيناريوهات)، اختبار قارئ شاشة، اختبار أجهزة حقيقية (Android منخفض، iPhone)، اختبار حمل خفيف (50 مستخدماً متزامناً).

## 16. Acceptance Criteria
بوابة Demo مكتملة قبل موعد المسابقة (OQ-001)؛ بوابة الإطلاق العام موثقة مع مالك لكل بند مفتوح.

## 17. Step-by-Step Implementation
1. أوامر الحذف والتذكير + الجدولة + tests (`travel()`).
2. إعداد staging (OQ-011) + متغيرات البيئة + Redis + MySQL + worker.
3. Seeders Demo النهائية (محتوى، أسئلة طبيب، Ruleset Draft، حساب أدمن تجريبي يُنشأ بأمر).
4. Regression + إصلاحات.
5. الوصول والأداء + إصلاحات.
6. مراجعة أمنية + خصوصية + إصلاحات.
7. تقييم AI النهائي في staging.
8. اختبار قابلية الاستخدام وتعديلات.
9. سكربت العرض + بروفة.
10. Go/No-Go Demo.
11. (لاحقاً) بوابة الإطلاق العام.

## 18. Definition of Done
البوابة المعنية مكتملة وموقعة من Product Owner (+ Medical Reviewer للإطلاق العام).

## 19. Open Questions
OQ-001 (موعد/متطلبات المسابقة)، OQ-011 (الاستضافة)، OQ-027 (أداة E2E)، من يوقّع على الإطلاق العام؟
