# Module-09 — Rafiq AI Companion

> الحالة: Planned · ترتيب التنفيذ: **8** (مع M11-D إعدادات AI) · التقدير: 7–9 أيام
> المراجع: [AI-Architecture](../02-architecture/AI-Architecture.md) · [AI-Use-Cases](../06-ai/AI-Use-Cases.md) · [AI-Safety-Guardrails](../06-ai/AI-Safety-Guardrails.md) · [Gemini-Integration](../06-ai/Gemini-Integration.md) · [Knowledge-Grounding](../06-ai/Knowledge-Grounding.md) · [AI-Evaluation-Plan](../06-ai/AI-Evaluation-Plan.md)

## 1. Purpose
مساعد ذكي يجعل المنصة أسهل استخداماً والمحتوى المعتمد أسهل فهماً (Platform Guide + Personalized Health Education)، ضمن حدود صارمة تمنع التشخيص والنصيحة الطبية وإرسال البيانات الصحية.

## 2. Scope
**يشمل:** الأوضاع الأربعة (`platform_guide`, `explain_article`, `explain_term`, `general_education`)، الخط الكامل (البوابات، الحدود، المرشحات، الاسترجاع، البناء، الاستدعاء، التحقق، السجل)، واجهة الدردشة، الاقتراحات، الاستشهادات، Fake generator للـ Demo، مجموعة التقييم وأمرها.
**لا يشمل:** شرح التقرير، تحضير الطبيب بنص المستخدم (ADR-009)، function calling، الصوت، الصور، Search grounding.

## 3. Dependencies
M03 (age + AI notice)، M08 (المحتوى والمقاطع)، M06 (`red_flag_lexicon` عبر `SafetyLexiconProvider`)، M11-A (حسابات لإعدادات AI)، M11-D (تُنفذ معاً).

## 4. User Stories
- US-09-1: بصفتي زائراً، أريد سؤال "كيف أستخدم المنصة؟" والحصول على إجابة واضحة.
- US-09-2: بصفتي قارئاً، أريد تلخيصاً مبسطاً لمقال طويل بلغتي.
- US-09-3: بصفتي مستخدماً، أريد فهم مصطلح طبي دون البحث في مكان آخر.
- US-09-4: بصفتي مستخدماً يكتب أعراضه، أريد أن يوجهني المساعد بأمان بدل أن يخمّن.
- US-09-5: بصفتي Super Admin، أريد التحكم في رسائل الترحيب والاقتراحات والحدود دون أن أستطيع تحويله إلى طبيب.

## 5. Functional Requirements
| ID | المتطلب | PRD |
|---|---|---|
| M09-FR-01 | زر "اسأل رفيق" (عائم/في الترويسة) + زر "اشرح لي" في المقالات والمسرد وبنك أسئلة J4؛ لا تحميل قبل النقر. | FR-080, AI-013 |
| M09-FR-02 | `POST /{locale}/ai/messages` بالحقول: `mode`, `message` (≤ 500)، `article_id?`, `term_id?`, `preset?` (summarize/simplify)، `history[≤6]`، `simplification` (1–3). | FR-081 |
| M09-FR-03 | الخط كاملاً (AI-Architecture §3). | AI-001..021 |
| M09-FR-04 | الردود المعتمدة (غير المولّدة) لكل outcome باللغتين من `ai_configurations.settings` + افتراضيات في lang files. | GR-10 |
| M09-FR-05 | الاستشهادات كروابط للمقالات. | AI-002 |
| M09-FR-06 | التنبيه الثابت تحت كل رد. | AI-021 |
| M09-FR-07 | مسح المحادثة؛ لا حفظ. | FR-083 |
| M09-FR-08 | اقتراحات أسئلة معتمدة حسب السياق (صفحة/مقال). | FR-082 |
| M09-FR-09 | `FakeAiTextGenerator` عند `GEMINI_API_KEY` فارغ في غير Production (للـ Demo والاختبارات). | — |
| M09-FR-10 | `rafiq:ai:eval {suite}` + ملفات `resources/ai-eval/*.jsonl`. | AI-020 |
| M09-FR-11 | `ai_usage_logs` + عدادات `ai_request:{outcome}` و`article_ai_explain`. | AI-017 |
| M09-FR-12 | حدود المعدل والسقف (AI-013)؛ backoff عام عند 429 من Google. | AI-013 |

## 6. User Flow
```text
نقر "اسأل رفيق" ⇒ (age? ⇒ gate) ⇒ (ai_notice? ⇒ AiNotice ⇒ ack) ⇒ ChatPanel (ترحيب + اقتراحات)
  ⇒ كتابة/اختيار ⇒ إرسال ⇒ [red-flag ⇒ رسالة الطوارئ المعتمدة + زر "افتح فحص علامات الخطر"]
                          [PII/شخصي ⇒ رد آمن + زر "مسار لدي قلق"]
                          [OK ⇒ Gemini ⇒ تحقق ⇒ رد + استشهادات + تنبيه]
```

## 7. UI/UX Requirements
- Panel جانبي على سطح المكتب، Sheet كامل على الموبايل؛ `aria-live="polite"` للردود.
- تحذير ثابت فوق الإدخال: "لا تكتب اسمك أو معلومات عن حالتك الصحية".
- عداد أحرف، زر إيقاف أثناء الانتظار، حالة "يكتب…"، مهلة 15 ثانية ⇒ رسالة.
- رسائل الطوارئ بصريّاً مختلفة (ليست فقاعة دردشة عادية) مع زر اتصال.
- الردود نص عادي (لا Markdown/HTML) — فقرات وقوائم بسيطة تُنسَّق في الواجهة من فواصل الأسطر.
- RTL كامل؛ اتجاه كل رسالة حسب لغتها (`dir="auto"`).

## 8. Backend Requirements
الأصناف في AI-Architecture §4. إضافة:
- `AiMessageController` (thin) + `AiMessageRequest`.
- RateLimiters: `ai-session`, `ai-ip` (مفتاح = HMAC(IP, app key) — لا IP خام).
- ربط `AiTextGenerator` ⇒ Gemini أو Fake حسب البيئة/المفتاح.
- `config/services.php` + `config/rafiq.php` ai section.
- Scheduler: لا شيء هنا (prune في M12).

## 9. Frontend Requirements
`features/ai-chat/{ChatLauncher, ChatPanel, MessageList, Message, EmergencyMessage, Citations, SuggestedPrompts, Composer, AiNotice}.tsx` — `React.lazy` للـ Panel. `useAiChat()` hook (حالة في الذاكرة، إلغاء الطلب عند الإغلاق).

## 10. Database Requirements
`ai_configurations`, `ai_usage_logs` (Database-Design §3.4). لا جداول محادثات.

## 11. Routes and Permissions
| Method | URI | الاسم | Middleware |
|---|---|---|---|
| POST | `/{locale}/ai/messages` | `ai.messages` | age, ai_notice, throttle:ai-session, throttle:ai-ip, no-store |
| GET | `/{locale}/ai/suggestions?context=…` | `ai.suggestions` | age (محتوى عام معتمد؛ `context` من allow-list) |

## 12. Business Rules
- BR-09-1: لا استدعاء Gemini إلا بعد اجتياز كل المرشحات ووجود مقاطع.
- BR-09-2: Red-flag يسبق كل شيء.
- BR-09-3: `personal_case`/`out_of_scope` ⇒ رد معتمد، لا نص النموذج.
- BR-09-4: لا نتائج تقييم/أعراض في أي حقل.
- BR-09-5: الإعدادات النشطة لا ترفع الحدود فوق `config`.
- BR-09-6: `history` تُعاد تصفيتها في كل طلب.

## 13. Validation and Error Handling
422 لحقول غير صالحة/طول/mode غير معروف/`article_id` غير منشور؛ 409 `ai_notice_required`؛ 429 بحدود؛ 503 `ai_disabled`/`budget_exceeded`؛ أخطاء Google ⇒ fallback (Gemini-Integration §4).

## 14. Security and Privacy
GR-01..20؛ T-05, T-06, T-07, T-12, T-17, T-18؛ PRIV-014؛ SEC-018. لا تسجيل للنص بأي مستوى سجل.

## 15. Testing
- Unit: كل مرشح (جداول أمثلة ar/en + عامية، إيجابي/سلبي)؛ `PromptBuilder` (لا PII، حدود المقاطع، علامات الفصل)؛ `OutputPostFilter`؛ `BudgetGuard`.
- Feature (Http::fake + preventStrayRequests): البوابات؛ الحدود؛ red-flag ⇒ 0 طلبات لـ Google؛ PII ⇒ 0 طلبات؛ حمولة الطلب الصادر لا تحتوي مفاتيح/أنماط محظورة (AI-008) ولا `tools`؛ استشهاد غير صالح ⇒ fallback؛ أخطاء 429/5xx/timeout؛ `ai_usage_logs` بلا نص؛ تصفح الصفحات ⇒ 0 طلبات.
- Arch: لا اعتماد على `Clinical`.
- Eval (staging، خارج CI): كل المجموعات؛ بوابات القبول.
- Manual: موبايل، قارئ شاشة، RTL.

## 16. Acceptance Criteria
1. كل اختبارات CI تمر.
2. تقرير تقييم staging يحقق بوابات AI-Evaluation-Plan §4.
3. Demo يعمل بـ Gemini (staging) وبالـ Fake.
4. مراجعة خصوصية لإشعار الـ AI والحمولة الصادرة.

## 17. Step-by-Step Implementation
1. Contract + Fake + ربط + config.
2. `AiMessageRequest` + controller هيكلي + البوابات + الحدود + tests.
3. `RedFlagInterceptor` (يقرأ lexicon) + tests.
4. `InputPreFilter` + tests (جداول أمثلة).
5. `GroundingRetriever` (يستخدم M08) + tests.
6. `CorePrompt` v1 + `PromptBuilder` + tests.
7. `GeminiTextGenerator` + Http fake tests + معالجة الأخطاء.
8. `OutputPostFilter` + tests.
9. `UsageLogger` + `BudgetGuard` + tests.
10. الواجهة: Launcher/Panel/Messages/Citations/Notice/Composer.
11. الاقتراحات.
12. مجموعات التقييم الأولية (≥ 50% من الأحجام) + الأمر.
13. تشغيل التقييم في staging وتحسين المرشحات/الـ prompt.
14. (مع M11-D) الإعدادات وإصداراتها.

## 18. Definition of Done
القبول + AI-019 (إعادة تحقق الشروط) مسجلة بالتاريخ + Threat-Model محدث + DoD العامة.

## 19. Open Questions
OQ-005، OQ-006 (Paid/Vertex/الميزانية)، OQ-024 (تفسير "medical advice")؛ هل `general_education` في الـ MVP أم Phase 2؟ (مقترح: مفعّل في Demo، قابل للتعطيل)؛ مستوى التبسيط الافتراضي.
