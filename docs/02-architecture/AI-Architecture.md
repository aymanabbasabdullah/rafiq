# بنية الذكاء الاصطناعي (AI Architecture)

> الحالة: Draft · آخر تحديث: 2026-11-15
> هذه الوثيقة تصف **المكونات وخط المعالجة**. التفاصيل: [AI-Use-Cases](../06-ai/AI-Use-Cases.md) · [AI-Safety-Guardrails](../06-ai/AI-Safety-Guardrails.md) · [Gemini-Integration](../06-ai/Gemini-Integration.md) · [Knowledge-Grounding](../06-ai/Knowledge-Grounding.md) · [AI-Evaluation-Plan](../06-ai/AI-Evaluation-Plan.md)

## 1. المبدأ الحاكم

> **الحتمية تقرر، والـ AI يشرح.** الـ AI لا يرى بيانات المستخدم الصحية، ولا يرى نتيجة المحرك، ولا يقرر الطوارئ، ولا يعمل إلا بطلب صريح.

## 2. الأوضاع (Modes)

| Mode | الغرض | مصدر التأريض (Grounding) | مدخل المستخدم |
|---|---|---|---|
| `platform_guide` | AI-001: التنقل والخدمات | مقالات `kind=platform_guide` المعتمدة | سؤال حر قصير (مُرشَّح) |
| `explain_article` | AI-002: شرح/تلخيص مقال | مقاطع المقال الحالي فقط | سؤال عن المقال أو زر جاهز ("لخّص"، "بسّط") |
| `explain_term` | AI-002: تبسيط مصطلح | تعريف المسرد المعتمد + مقاطع ذات صلة | المصطلح (من قائمة) + سؤال اختياري |
| `general_education` | AI-002: الفرق بين مفهومين تعليميين عامين | بحث FULLTEXT في التصنيفات `ai_enabled` | سؤال حر قصير (مُرشَّح) |

لا يوجد وضع للتقييم أو التقرير أو تحضير الطبيب بنص المستخدم (ADR-009).

## 3. خط المعالجة (Pipeline)

```text
Request
  │ 0. Gates: age_confirmed ∧ ai_notice_ack ∧ ai.enabled ∧ locale
  │ 1. Throttle (session + hashed IP) ∧ BudgetGuard (daily cost cap)
  │ 2. Validation (mode enum, message ≤ 500 chars, history ≤ 6, article/term ids exist & published & ai_enabled)
  ▼
  3. RedFlagInterceptor  ── match ──► approved emergency text (no LLM)      outcome=blocked_redflag
  4. InputPreFilter      ── match ──► approved safe-redirect text (no LLM)  outcome=blocked_prefilter
       • PII patterns (email, phone, national-ID-like digits, URLs)
       • First-person health disclosure (ar/en patterns: "عندي/أعاني/أشعر بـ…" + body/symptom lexicon; "I have/I feel/my …")
       • Requests for diagnosis / dosage / prescription / "am I infertile" / "is my result normal"
       • Instruction-override patterns (prompt injection heuristics)
  ▼
  5. GroundingRetriever → ≤ 4 chunks (published ∧ approved ∧ ai_enabled ∧ same locale ∧ source not ai_use_restricted)
       no chunks ⇒ approved "لا أملك محتوى معتمداً عن هذا" (no LLM)        outcome=refused_out_of_scope
  6. PromptBuilder:
       system = CorePrompt(vN, immutable) + AdminConfig(safe fields only, as data) + locale + simplification level
       context = <approved_content> chunks with ids </approved_content> (delimited, marked as data)
       user = filtered message (+ filtered history)
  7. AiTextGenerator::generate() → Gemini generateContent (JSON schema output)
  8. OutputPostFilter:
       • schema valid ∧ cited_chunk_ids ⊆ provided ids ∧ non-empty (unless out_of_scope=true)
       • no dosage/medication-instruction patterns, no diagnostic phrasing, no non-approved URLs/phone numbers
       • length ≤ limit; language matches locale
       fail ⇒ approved fallback text                                     outcome=blocked_postfilter
  9. Response: answer (plain text) + citations (article titles/links) + fixed disclaimer (AI-021)
 10. UsageLogger: metadata only (AI-017); MetricsRecorder increment
```

## 4. المكونات (Laravel)

| الصنف | المسؤولية | ملاحظات |
|---|---|---|
| `Ai\Contracts\AiTextGenerator` | `generate(AiPrompt): AiResult` | يُربط في `AppServiceProvider`؛ `FakeAiTextGenerator` للاختبار والـ Demo دون مفتاح |
| `Ai\Gemini\GeminiTextGenerator` | REST call، timeouts، retry محدود، تحويل الأخطاء | لا يسجّل المحتوى |
| `Ai\CompanionService` | ينسّق الخطوات 3–9 | لا يعتمد على `Clinical\*` (ADR-020) |
| `Ai\Filters\RedFlagInterceptor` | قائمة عبارات علامات الخطر ثنائية اللغة | **مصدر القائمة:** ملف ضمن Ruleset المعتمد (`red_flag_lexicon.json`) — يُقرأ كبيانات عبر واجهة محايدة `SafetyLexiconProvider` لتفادي الاعتماد على محرك القواعد نفسه |
| `Ai\Filters\InputPreFilter` | PII + إفصاح شخصي + طلبات محظورة + injection | قواعد regex/lexicon مُصدَّرة ومختبرة |
| `Ai\Filters\OutputPostFilter` | التحقق من المخرجات | — |
| `Ai\GroundingRetriever` | استرجاع المقاطع | ADR-008 |
| `Ai\PromptBuilder` + `Ai\CorePrompt` | بناء الطلب | CorePrompt ثابت بالكود مع `VERSION` |
| `Ai\BudgetGuard` | يجمع `estimated_cost_micros` اليومي من `ai_usage_logs` (cache) | يعطّل عند السقف |
| `Ai\UsageLogger` | كتابة `ai_usage_logs` | — |

## 5. الواجهة (React)

- `features/ai-chat/ChatPanel` يُحمَّل **lazy** عند الضغط على الزر فقط (FR-080).
- `AiNotice` يُعرض قبل أول رسالة؛ الإقرار يُرسل `POST /{locale}/gate/ai-notice`.
- المحادثة في الذاكرة فقط؛ زر "مسح"؛ إغلاق التبويب يمحوها (FR-083).
- تحذير ثابت فوق مربع الإدخال: "لا تكتب اسمك أو أي معلومات عن حالتك الصحية".
- عداد الأحرف (500).
- عرض الاستشهادات كروابط للمقالات المعتمدة.

## 6. الإعدادات

`config/services.php` → `gemini.api_key` (من `GEMINI_API_KEY`)، `gemini.model`، `gemini.base_url`، `gemini.timeout`.
`config/rafiq.php` → `ai.enabled`، `ai.limits.*`، `ai.daily_budget_usd` (قيمة افتراضية؛ الإعداد النشط في `ai_configurations` يمكنه **خفضها** فقط لا رفعها فوق قيمة الملف).

## 7. نقاط الفشل والتدهور

| الفشل | السلوك |
|---|---|
| Gemini timeout/5xx | رسالة "المساعد غير متاح حالياً" + روابط المحتوى؛ outcome=error |
| 429 من Gemini | نفس ما سبق + تراجع (backoff) عام 60 ثانية عبر cache flag |
| تجاوز السقف | تعطيل حتى منتصف الليل (توقيت المنصة) |
| مخرجات غير صالحة | fallback معتمد |
| تغيير شروط Google | `ai.enabled=false` ريثما تُراجع (AI-019) |
