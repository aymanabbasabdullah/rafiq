# تكامل Gemini (Gemini Integration)

> الحالة: Draft · آخر تحديث: 2026-11-15 · **لم يتم استدعاء Gemini API في هذه المرحلة.**

## 1. نتائج مراجعة الشروط (تاريخ التحقق: 2026-11-15)

| # | البند | ما وجدناه | الأثر على رفيق | المصدر |
|---|---|---|---|---|
| 1 | الاستخدام الطبي | يُمنع الاستخدام في الممارسة السريرية أو لتقديم نصيحة طبية أو بطريقة تتطلب موافقة جهة تنظيم الأجهزة الطبية. المحتوى الطبي المولّد لا يُعد تشخيصاً أو علاجاً. | نطاق الـ AI محصور في التعليم العام والتنقل؛ رفض أي حالة شخصية؛ عدم وصل الـ AI بالمحرك؛ رأي تنظيمي (OQ-024). | SRC-006 (Use Restrictions; Disclaimers) |
| 2 | العمر | 18+؛ ولا يجوز في تطبيق موجّه أو يُرجح أن يصل إليه من هم دون 18. | ADR-010 (المنصة 18+، Age Gate قبل الـ AI). | SRC-006 (Age Requirements) |
| 3 | Unpaid Services | تُستخدم المدخلات والمخرجات لتحسين منتجات Google، وقد يراجعها بشر؛ لا تُرسل معلومات حساسة أو سرية أو شخصية. | **ممنوع استخدام الطبقة المجانية** حتى في الـ Demo إن كانت هناك أي إمكانية لإدخال حقيقي؛ في التطوير بيانات اصطناعية فقط. | SRC-006 (Unpaid Services) |
| 4 | Paid Services | لا تُستخدم للتحسين؛ تُسجل لفترة محدودة لكشف المخالفات والمتطلبات القانونية. | AI-015. | SRC-006 (Paid Services) |
| 5 | المناطق | للمستخدمين في EEA/سويسرا/المملكة المتحدة: Paid Services فقط. الخدمة متاحة في "مناطق متاحة" فقط. | التحقق من قائمة المناطق للبلد المستهدف (OQ-002) قبل الإطلاق. | SRC-006 |
| 6 | مراقبة الإساءة | تسجيل الطلبات والسياق والمخرجات 55 يوماً. | إفصاح في سياسة الخصوصية وإشعار الـ AI (PRIV-007، FR-022). | SRC-007 |
| 7 | Grounding with Search/Maps | تخزين 30 يوماً بلا إمكانية تعطيل. | ممنوع (AI-016). | SRC-006, SRC-008 |
| 8 | ZDR | يعتمد على الإعداد وتجنب ميزات معينة؛ implicit caching في الذاكرة 24 ساعة؛ Vertex AI موصى به للضمانات المؤسسية. | لا Files/explicit cache/Interactions store؛ تقييم Vertex AI (OQ-006). | SRC-008 |
| 9 | سياسة الاستخدام المحظور | منع ادعاءات خبرة مضللة في الصحة؛ منع قرارات آلية ضارة في الرعاية الصحية دون إشراف بشري؛ منع المحتوى الجنسي الصريح. | الـ AI لا يدّعي صفة طبية؛ لا قرارات؛ المحتوى التعليمي عن الصحة الجنسية يجب أن يبقى علمياً غير صريح — قد تُفعّل مرشحات الأمان لدى Google على موضوعات تعليمية جنسية (اختبار في AI-Evaluation-Plan). | SRC-009 |

**ما لم نتمكن من التحقق منه:** تعريف دقيق لـ "medical advice"؛ وجود عملية رسمية لطلب ZDR على Developer API؛ أي استثناءات للاستخدام التعليمي الصحي. ⇒ OQ-024، R-02.

**إعادة التحقق إلزامية (AI-019)** قبل: تفعيل الـ AI في staging العام، الإطلاق، وكل تغيير للنموذج أو المزوّد. يُحدَّث هذا الجدول بالتاريخ.

## 2. الإعداد

| المفتاح (`.env`) | الوصف |
|---|---|
| `GEMINI_API_KEY` | مفتاح مشروع Paid — **لا يُعرض أبداً في الواجهة أو السجلات أو Git** |
| `GEMINI_MODEL` | يُحدد عند التنفيذ (نموذج Flash أو Flash-Lite مستقر، **ليس preview** في الإنتاج) بعد التحقق من صفحة النماذج الرسمية |
| `GEMINI_BASE_URL` | `https://generativelanguage.googleapis.com` (يُتحقق من إصدار الـ API عند التنفيذ) |
| `GEMINI_TIMEOUT` | 15 |
| `RAFIQ_AI_ENABLED` | مفتاح إيقاف |
| `RAFIQ_AI_DAILY_BUDGET_USD` | السقف الأعلى |

`config/services.php`:
```php
'gemini' => [
    'api_key' => env('GEMINI_API_KEY'),
    'model' => env('GEMINI_MODEL'),
    'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com'),
    'timeout' => (int) env('GEMINI_TIMEOUT', 15),
],
```
(توضيحي — لا يُنفذ الآن.)

## 3. شكل الطلب (توضيحي)

`POST {base_url}/v1beta/models/{model}:generateContent` مع ترويسة `x-goog-api-key` (يُتحقق من الصيغة والإصدار عند التنفيذ).

```json
{
  "systemInstruction": { "parts": [{ "text": "<CorePrompt + safe config fields>" }] },
  "contents": [
    { "role": "user", "parts": [{ "text": "<approved_content>[c12] ... [c13] ...</approved_content>\n<user_message>...</user_message>" }] }
  ],
  "generationConfig": {
    "temperature": 0.2,
    "maxOutputTokens": 600,
    "responseMimeType": "application/json",
    "responseSchema": { "type": "OBJECT", "properties": { "answer": {"type":"STRING"}, "cited_chunk_ids": {"type":"ARRAY","items":{"type":"STRING"}}, "out_of_scope": {"type":"BOOLEAN"}, "personal_case": {"type":"BOOLEAN"} }, "required": ["answer","cited_chunk_ids","out_of_scope","personal_case"] }
  },
  "safetySettings": [ "... thresholds تُضبط بعد التقييم ..." ]
}
```

**ممنوع في الطلب:** `tools.googleSearch`، `tools.googleMaps`، `cachedContent`، ملفات (`fileData`)، أي معرّف مستخدم/جلسة/IP في أي حقل (بما في ذلك `labels`/metadata).

## 4. معالجة الأخطاء

| الحالة | السلوك |
|---|---|
| انتهاء المهلة | رسالة "غير متاح" + outcome=error (`timeout`) |
| 429 | إعادة محاولة واحدة بعد 1–2 ثانية؛ ثم backoff عام 60 ثانية |
| 5xx | إعادة محاولة واحدة؛ ثم fallback |
| 400 (طلب غير صالح) | fallback + تنبيه للمطورين (السجل بلا محتوى) |
| حظر أمان من Google (`finishReason=SAFETY` أو `promptFeedback.blockReason`) | fallback معتمد؛ يُحسب في تقييم الإيجابيات الكاذبة |
| JSON غير صالح | fallback؛ outcome=blocked_postfilter |

## 5. التكلفة

- تقدير التكلفة لكل طلب من `usageMetadata` (رموز الإدخال/الإخراج) × أسعار مُخزنة في `config/rafiq.php` (تُحدّث يدوياً من صفحة الأسعار الرسمية عند التنفيذ — لا نوثق أسعاراً هنا لأنها تتغير).
- السقف اليومي الافتراضي المقترح للـ Demo: قيمة منخفضة يحددها المالك (OQ-006).
- تقليل التكلفة: حد 4 مقاطع، `maxOutputTokens` محدود، history ≤ 6، لا استدعاء عند التصفح، الاعتراض الحتمي قبل الاستدعاء.

## 6. الاختبار

- **لا استدعاءات حقيقية في CI**: `Http::fake()` + `Http::preventStrayRequests()`.
- `FakeAiTextGenerator` يعيد ردوداً حتمية لمسارات الواجهة في Demo بدون مفتاح.
- اختبار تكامل يدوي/مجدول (خارج CI) ضد Gemini بمجموعة التقييم (AI-Evaluation-Plan) بمفتاح staging وبيانات اصطناعية.

## 7. المهارات المستخدمة

مهارة `gemini-api-dev` (من مكتبة `agentic-awesome-skills`، مصدرها `google-gemini/gemini-skills`) استُخدمت للاطلاع على أسماء SDKs والنماذج الحالية فقط؛ **قائمة النماذج فيها تحتاج تحققاً** عند التنفيذ ولم تُعتمد كمرجع للشروط. المرجع للشروط هو SRC-006..SRC-009 مباشرة.
