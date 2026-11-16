# Module-06 — Clinical Rules Engine (محرك القواعد السريرية)

> # DRAFT – PENDING QUALIFIED MEDICAL REVIEW
> المحرك نفسه برمجيات؛ القواعد التي يحمّلها (RS-2026.1) غير معتمدة.

> الحالة: Planned · ترتيب التنفيذ: **7** (مع M11-C) · التقدير: 6–8 أيام · المرجع: [Clinical-Safety-Framework](../04-clinical/Clinical-Safety-Framework.md) · [Clinical-Rules-Catalogue](../04-clinical/Clinical-Rules-Catalogue.md) · [Clinical-Test-Cases](../04-clinical/Clinical-Test-Cases.md)

## 1. Purpose
محرك حتمي، مستقل عن الـ AI، يقيّم إجابات Safety Screening وAssessment مقابل Ruleset Release معتمد، ويعيد الأولوية P0–P4 (أو GATE) والقواعد المفعلة ومفاتيح الإرشاد — قابل للاختبار والتتبع والمراجعة.

## 2. Scope
**يشمل:** صيغة تعريف القواعد والأسئلة (JSON)، الاستيراد إلى قاعدة البيانات، `ActiveRulesetResolver`، `ConditionEvaluator`، `ClinicalRulesEngine` (`screen`, `evaluate`)، DTOs، Boot checks، وضع Demo، مشغّل حالات الاختبار (CLI + PHPUnit)، قاموس العبارات المحظورة (SAFE-010)، قاموس علامات الخطر للـ AI كملف بيانات ضمن الإصدار، Kill switch.
**لا يشمل:** واجهات المستخدم (M04/M05/M07)، شاشات الإدارة (M11-C)، نصوص الإرشاد النهائية (محتوى طبي).

## 3. Dependencies
M00. M11-A (لتسجيل الفاعلين في الاعتماد — يمكن تنفيذ المحرك والاستيراد قبل شاشات الإدارة).

## 4. User Stories
- US-06-1: بصفتي Medical Reviewer، أريد أن أعرف أن ما اعتمدته هو بالضبط ما يُنفّذ.
- US-06-2: بصفتي مطوراً، أريد إضافة قاعدة باختبارات دون لمس كود المحرك.
- US-06-3: بصفتي مستخدماً، أريد نتيجة متسقة وغير متأثرة بلغتي أو جهازي.

## 5. Functional Requirements
| ID | المتطلب | PRD |
|---|---|---|
| M06-FR-01 | JSON Schema لتعريف القاعدة والسؤال (Clinical-Safety-Framework §4) مع تحقق صارم عند الاستيراد. | ADR-005 |
| M06-FR-02 | `rafiq:clinical:import {version}`: يتحقق، يحسب `source_hash`، ينشئ/يحدّث Release بحالة Draft وإصدارات القواعد والأسئلة والنصوص (Draft)، يسجّل `imported_from_commit`. لا يغيّر Release منشوراً. | ADR-005 |
| M06-FR-03 | `ActiveRulesetResolver`: Production ⇒ الإصدار `published` فقط؛ غير Production + Demo ⇒ يسمح بإصدار Draft محدد في `config('rafiq.demo_ruleset')`. | FR-052/053 |
| M06-FR-04 | `ConditionEvaluator`: العوامل `eq, neq, in, not_in, contains, contains_any, is_unsure, is_answered, is_unanswered, band_gte, band_lte` والمجمّعات `all/any/not`؛ دوال نقية. | FR-050 |
| M06-FR-05 | `ClinicalRulesEngine::screen()` و`::evaluate()` وفق الخوارزمية (Framework §5). | FR-050/051 |
| M06-FR-06 | `EvaluationResult` (readonly): `priority`, `triggered[] {code, version, priority}`, `guidanceKeys[]`, `doctorTopicKeys[]`, `relatedArticleKeys[]`, `flags[]`, `rulesetVersion`, `demo`. | NFR-009 |
| M06-FR-07 | `rafiq:clinical:test {version}` يشغّل `resources/clinical/test-cases/*.json` ويطبع تقريراً؛ يُخزن التقرير في `ruleset_releases.test_report` عند الطلب من الإدارة. | SAFE-008 |
| M06-FR-08 | فحص العبارات المحظورة على نصوص الإرشاد والأسئلة في الاستيراد وفي CI. | SAFE-010 |
| M06-FR-09 | `red_flag_lexicon.json` ضمن الإصدار + `SafetyLexiconProvider` (واجهة محايدة يقرأها M09 دون الاعتماد على المحرك). | AI-012 |
| M06-FR-10 | Kill switch `rafiq.assessment_enabled`. | Security §4 |
| M06-FR-11 | Cache للإصدار المحمّل (مفتاح بالإصدار + hash) مع إبطال عند النشر. | NFR-002 |

## 6. User Flow
لا واجهة مستخدم. تدفق المطور/المراجع: تعديل JSON ⇒ PR ⇒ CI (schema + TCs + محظورات) ⇒ دمج ⇒ import ⇒ مراجعة في M11-C ⇒ نشر.

## 7. UI/UX Requirements
لا يوجد (مخرجات CLI مقروءة: جدول PASS/FAIL لكل TC مع الفرق).

## 8. Backend Requirements
```text
app/Services/Clinical/
  ClinicalRulesEngine.php        # screen(), evaluate()
  ConditionEvaluator.php         # pure
  ActiveRulesetResolver.php
  LoadedRuleset.php              # immutable in-memory representation
  RulesetImporter.php
  RulesetSchemaValidator.php
  ForbiddenPhraseChecker.php
  RulesetTestRunner.php
  SafetyLexiconProvider.php      # (contract in App\Contracts\Safety)
  Data/AssessmentInput.php, EvaluationResult.php, TriggeredRule.php
  Exceptions/NoActiveRuleset.php, InvalidAssessmentInput.php
app/Console/Commands/ClinicalImportCommand.php, ClinicalTestCommand.php
resources/clinical/schema/{rule,question}.schema.json
resources/clinical/rulesets/RS-2026.1/{questions.json, rules/*.json, guidance/{ar,en}.json, red_flag_lexicon.json}
resources/clinical/test-cases/TC-01.json ...
```
- `declare(strict_types=1)`؛ لا Facades داخل `ConditionEvaluator`؛ المحرك يعتمد على `LoadedRuleset` فقط (سهولة الاختبار).
- لا استيراد لأي `App\Services\Ai\*` (ADR-020).

## 9. Frontend Requirements
لا يوجد.

## 10. Database Requirements
`ruleset_releases`, `ruleset_release_items`, `clinical_rules`, `clinical_rule_versions`, `clinical_questions`, `clinical_question_translations`, `guidance_texts`, `guidance_text_translations` (Database-Design §3.3). Migrations + Models + Factories (للاختبارات فقط، بيانات اصطناعية).

## 11. Routes and Permissions
لا مسارات عامة جديدة (يستخدمها M04/M05). أوامر CLI: `rafiq:clinical:import` (Super Admin/DevOps في الخادم)، `rafiq:clinical:test`.

## 12. Business Rules
- BR-06-1: أعلى أولوية تفوز؛ P0 `stop`.
- BR-06-2: GATE قبل كل شيء.
- BR-06-3: ترتيب `guidanceKeys`: الأولوية ثم الرمز؛ بلا تكرار.
- BR-06-4: لا نتيجة افتراضية عند الخطأ — استثناء ⇒ 503.
- BR-06-5: إصدار Approved لا يُعدّل؛ التعديل ⇒ إصدار جديد.
- BR-06-6: في Production لا يُشغّل إلا Release `published` كل عناصره `approved`.
- BR-06-7: الحتمية: لا وقت/عشوائية/لغة في التقييم.

## 13. Validation and Error Handling
- مدخلات غير صالحة ⇒ `InvalidAssessmentInput` (تُحوّل إلى 422 في Form Request — المحرك يتحقق مجدداً دفاعياً).
- Schema غير صالح في الاستيراد ⇒ فشل مع تقرير الأخطاء، لا كتابة جزئية (transaction).
- عدم وجود Release ⇒ `NoActiveRuleset` ⇒ 503 + SAFE-009.

## 14. Security and Privacy
T-10، T-19، T-20؛ لا تسجيل للمدخلات؛ الاستيراد لا يعمل عبر HTTP؛ `source_hash` يكشف التلاعب بين Git وقاعدة البيانات.

## 15. Testing
- **Unit:** `ConditionEvaluator` لكل عامل (حالات حدية، أنواع خاطئة) — تغطية فروع 100% (NFR-012)؛ `Priority::max`.
- **Feature/Integration:** TC-01..TC-37 كاملة (Data Provider يقرأ ملفات JSON)؛ الحتمية TC-28؛ اللغة TC-29؛ البيئات TC-30..32؛ الاستيراد (صالح/غير صالح/لا يمس المنشور)؛ المحظورات.
- **Architecture:** لا اعتماد `Clinical` ⇄ `Ai`.
- **Mutation testing (Should):** Infection على `ConditionEvaluator` (بموافقة لإضافة الأداة).

## 16. Acceptance Criteria
1. كل TCs تمر في CI.
2. تغطية 100% للفروع في المحرك والمقيّم.
3. Demo يعمل محلياً بـ RS-2026.1 Draft؛ Production يرفض.
4. Medical Reviewer (أو المنتج مؤقتاً) يستطيع قراءة تقرير `rafiq:clinical:test`.

## 17. Step-by-Step Implementation
1. JSON Schemas للقاعدة والسؤال + أمثلة.
2. Migrations + Models + Enums للحالات.
3. `ConditionEvaluator` + unit tests (TDD).
4. `LoadedRuleset` + `ClinicalRulesEngine` + DTOs + tests بالـ fixtures.
5. كتابة RS-2026.1 JSON من الكتالوج (CR-001..CR-017 + SAFE-006 policy) + `questions.json` + نصوص الإرشاد المسودة باللغتين.
6. `RulesetImporter` + `rafiq:clinical:import` + tests.
7. `ActiveRulesetResolver` + Demo/Production + boot checks + tests TC-30..32.
8. ملفات TC-xx.json + `RulesetTestRunner` + `rafiq:clinical:test` + PHPUnit data provider.
9. `ForbiddenPhraseChecker` + CI.
10. `red_flag_lexicon.json` + `SafetyLexiconProvider`.
11. Cache + kill switch.
12. Arch test.

## 18. Definition of Done
القبول + مراجعة كود من مطورين اثنين للمحرك + تقرير TCs مرفق + Clinical-Rules-Catalogue محدث بأي تغيير + DoD العامة.

## 19. Open Questions
OQ-009؛ OQ-019 (المسودة الأصلية)؛ RQ-1..RQ-8؛ هل نعتمد مكتبة JSON Schema لـ PHP (`opis/json-schema` أو مماثل — حزمة جديدة تحتاج موافقة) أم تحقق يدوي؟

---

## 20. ملاحظات التنفيذ (2026-11-16)

**الحالة: منفذة** (دون commit). **DRAFT – PENDING QUALIFIED MEDICAL REVIEW:** لا قاعدة معتمدة؛ RS-2026.1 تعمل فقط خارج Production في وضع Demo.

| البند | ما نُفذ | ملاحظة |
|---|---|---|
| ملفات الإصدار | `resources/clinical/rulesets/RS-2026.1/{questions.json, rules.json, guidance/{ar,en}.json, red_flag_lexicon.json}` و`resources/clinical/forbidden_phrases.json` | `rules.json` ملف واحد بدل `rules/*.json`؛ حالات الاختبار في `resources/clinical/test-cases/RS-2026.1.json` (ملف واحد). |
| صيغة القاعدة | `when/outcome` أو `branches` مرتبة (أول فرع مطابق يُطبق)؛ `priority_by_journey` لـ CR-008/CR-009؛ `enabled: false` + `disabled_reason`؛ مفاتيح سياق `@journey` و`@age_confirmed` | CR-004 وCR-005 بفرعَي `yes`/`unsure` (مقترحات الكتالوج لـ TC-35 والكتلة غير المؤكدة)؛ SAFE-006 قاعدة سلامة برمز خاص. |
| CR-015b | مضمّنة **معطّلة** حتى التحقق من المصدر (SG-1, RQ-6) | TC-12 يتوقع P4 لذلك. |
| المحرك | `ClinicalRulesEngine::screen()/evaluate()` يعمل على `LoadedRuleset` فقط؛ `ConditionEvaluator` نقي (11 عاملاً + all/any/not) | لا لغة ولا وقت ولا عشوائية؛ استثناء بدل نتيجة افتراضية. |
| التحقق من المدخلات | مفاتيح مجهولة/خارج المسار/خارج الفحص، قيم مزورة، `none`/`unknown` المتنافية، سؤال مخفي بقيمة، أسئلة خطر ناقصة ⇒ `InvalidAssessmentInput` (422) | الإرسال الجزئي يُقبل فقط إذا فعّل P0 (TC-36/37). |
| التحقق من الملفات | `RulesetSchemaValidator` يدوي (دون حزمة JSON Schema جديدة) + `ForbiddenPhraseChecker` (SAFE-010) | يتحقق من المراجع بين القواعد والأسئلة والنصوص باللغتين. |
| الاستيراد | `rafiq:clinical:import RS-2026.1 [--commit=]`: معاملة واحدة، `source_hash` (sha256 بعد توحيد نهايات الأسطر)، يرفض إصداراً دخل المراجعة، ويرفض تغيير إصدار قاعدة معتمد | إعادة الاستيراد نفسها تعيد استخدام الإصدارات. |
| الاختيار حسب البيئة | `ActiveRulesetResolver`: Production ⇒ منشور وكل قواعده معتمدة فقط؛ Demo خارج Production ⇒ `rafiq.demo_ruleset`؛ غير ذلك ⇒ منشور فقط | Cache بالمفتاح `version + source_hash`. |
| الاختبارات | `rafiq:clinical:test RS-2026.1 [--files]` (جدول PASS/FAIL)؛ PHPUnit: 33 حالة من الملف + TC-29 (اللغة) + TC-30/31 (البيئات) + الاستيراد + المحظورات + فصل `Clinical` عن `Ai`؛ TC-32 مغطى في `BootChecksTest` | 37 اختبار وحدة لـ `ConditionEvaluator`. |
| قاموس علامات الخطر | `SafetyLexicon` (عقد) + `SafetyLexiconProvider` يقرأ قاموس الإصدار النشط مع رجوع لملف الإصدار التجريبي | لـ Module-09؛ لا يحدد أولوية. |
| البذور | `ClinicalDemoSeeder` يستورد RS-2026.1 كمسودة في local/testing أو Demo فقط | لا يعتمد ولا ينشر شيئاً. |
| مؤجل | واجهات M04/M05/M07 التي تستدعي المحرك (مسارات HTTP + 503 + Kill switch في الواجهة)، تسميات خيارات الأسئلة (`option_labels`)، Mutation testing | — |
