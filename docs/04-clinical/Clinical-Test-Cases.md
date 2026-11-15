# حالات اختبار القواعد السريرية (Clinical Test Cases)

> # DRAFT – PENDING QUALIFIED MEDICAL REVIEW
> النتائج المتوقعة أدناه مشتقة من **مسودة** [Clinical-Rules-Catalogue](Clinical-Rules-Catalogue.md) (RS-2026.1 Draft). يجب أن يعتمد المراجع الطبي كل نتيجة متوقعة قبل اعتماد الـ Ruleset. أي تغيير في قاعدة يجب أن يحدّث الحالات المتأثرة.

## 1. الاصطلاحات

- **المدخل** = `journey` + `age_confirmed` + الإجابات. أي سؤال أمني غير مذكور = `no`، أي سؤال أمني مخفي بـ `show_if` = `not_applicable`، أي سؤال تقييم غير مذكور = `null` (متخطى) ما لم يُذكر غير ذلك.
- **المتوقع** = `priority` + `triggered` (مرتبة) + `flags` + `http` (200 افتراضياً).
- الصيغة القابلة للتشغيل: `resources/clinical/test-cases/TC-xx.json` ويشغّلها `php artisan rafiq:clinical:test {version}` وتُستدعى من PHPUnit (M06).

## 2. الحالات

| TC | الوصف | المدخل | المتوقع |
|---|---|---|---|
| TC-01 | ألم مفاجئ شديد | concern; entry=yes; `ss_sudden_severe_pain=yes` | **P0**; [CR-002]; stop; لا تُقيّم أسئلة التقييم |
| TC-02 | ألم مع غثيان | concern; entry=yes; `ss_pain_with_nausea_vomiting_abdominal=yes` | **P0**; [CR-003] |
| TC-03 | ألم > ساعة/أثناء الراحة | concern; entry=yes; `ss_pain_over_1h_or_at_rest=yes` | **P0**; [CR-017] |
| TC-04 | P0 مع حمّى | concern; entry=yes; sudden=yes; fever=yes | **P0**; [CR-002, CR-004]; أعلى أولوية تفوز |
| TC-05 | حمّى فقط | concern; entry=yes; fever=yes | **P1**; [CR-004] |
| TC-06 | كتلة فقط | concern; entry=no; `ss_new_lump_or_change=yes` | **P2**; [CR-005]; flag `prompt_in_person_exam` |
| TC-07 | "لست متأكداً" على سؤال P0 | concern; entry=yes; sudden=unsure | **P1**; [SAFE-006]; flag `show_emergency_criteria` |
| TC-08 | لا علامات ولا إجابات تقييم | concern; entry=no; lump=no; كل التقييم null | **P4**; []; flag `unanswered_present` |
| TC-09 | لا ألم ولا كتلة ⇒ متابعة | concern; entry=no; lump=no (فحص فقط عبر `/screen`) | `safety_passed=true`؛ لا أولوية نهائية بعد |
| TC-10 | قاصر | concern; `age_confirmed=false` | **GATE**; [CR-001]; لا تقييم لأي قاعدة أخرى |
| TC-11 | محاولة > 12 شهراً | conception; entry=no; lump=no; trying=gt_12m | **P2**; [CR-015] |
| TC-12 | 6–12 شهراً + شريكة ≥ 35 | conception; trying=6_12m; partner=gte_35 | **P2**; [CR-015b] (تُعطّل إن لم تُعتمد CR-015b ⇒ P4) |
| TC-13 | أقل من 6 أشهر + تدخين | conception; trying=lt_6m; smoking=current | **P3**; [CR-011] |
| TC-14 | علاج كيميائي مخطط | concern; chemo=planned | **P2**; [CR-009]; flag `time_sensitive` |
| TC-15 | ستيرويدات حالية + > 12 شهراً | conception; steroids=current; trying=gt_12m | **P2**; [CR-008, CR-015] |
| TC-16 | ستيرويدات حالية في J2 | concern; steroids=current | **P3**; [CR-008] |
| TC-17 | حرقة/إفرازات | concern; sexual_urinary=[burning_or_discharge] | **P2**; [CR-013] |
| TC-18 | تاريخ عائلي لسرطان الخصية فقط | concern; family=[testicular_cancer] | **P3**; [CR-014] |
| TC-19 | فحص سابق "غير طبيعي" | conception; trying=lt_6m; previous=done_told_abnormal | **P2**; [CR-016] |
| TC-20 | فحص "طبيعي" + > 12 شهراً | conception; trying=gt_12m; previous=done_told_normal | **P2**; [CR-015]؛ لا عبارة طمأنة في الإرشاد |
| TC-21 | كتلة + حمّى | concern; entry=yes; fever=yes; lump=yes | **P1**; [CR-004, CR-005] |
| TC-22 | سؤال أمني ناقص | concern; entry=yes؛ `ss_sudden_severe_pain` غير مرسل | **HTTP 422**؛ لا نتيجة |
| TC-23 | قيمة مزورة | concern; `ss_sudden_severe_pain=maybe` | **HTTP 422** |
| TC-24 | مفتاح غير معروف | concern; + `as_hacked=1` | **HTTP 422** |
| TC-25 | `none` مع خيار آخر | concern; chronic=[none, diabetes] | **HTTP 422** |
| TC-26 | "لا أعرف" فقط | concern; chronic=[unknown]; family=[unknown] | **P4**; []; doctor topic `unknown_answers` |
| TC-27 | عدة عوامل P3 | conception; trying=lt_6m; occupational=[high_heat]; smoking=current; chronic=[diabetes] | **P3**; [CR-006, CR-011, CR-012]; إرشاد بلا تكرار، مرتب بالرمز |
| TC-28 | الحتمية | مدخل TC-15 × 100 تقييم | نتائج متطابقة تماماً (priority, triggered, flags, guidance order) |
| TC-29 | استقلال اللغة | مدخل TC-21 بـ `ar` ثم `en` | نفس priority وtriggered وflags؛ تختلف النصوص فقط |
| TC-30 | لا Ruleset منشور في Production | `APP_ENV=production`; لا release منشور | **HTTP 503** + SAFE-009 |
| TC-31 | وضع Demo خارج Production | `APP_ENV=local`; `RAFIQ_DEMO_MODE=true`; مدخل TC-01 | **P0**; `demo=true` في الاستجابة |
| TC-32 | Demo في Production | `APP_ENV=production`; `RAFIQ_DEMO_MODE=true` | فشل الإقلاع (boot check) |
| TC-33 | قواعد J3 لا تُطبق في J2 | concern; trying=gt_12m (مُرسل رغم أنه ليس من أسئلة J2) | **HTTP 422** (سؤال غير منطبق على المسار) |
| TC-34 | سؤال مخفي يُرسل بقيمة | concern; entry=no; `ss_sudden_severe_pain=yes` | **HTTP 422** (تناقض مع `show_if`) — يمنع التلاعب ويمنع الالتباس |
| TC-35 | حمّى "لست متأكداً" | concern; entry=yes; fever=unsure | **P2**; flag `confirm_fever_then_same_day` (مقترح [مراجعة]) |
| TC-36 | إرسال مبكر بعد P0 | concern; entry=yes; sudden=yes؛ بقية أسئلة الخطر **غير مرسلة** (`/screen`) | **P0**; [CR-002]; مقبول رغم النقص (M04-FR-03) |
| TC-37 | إرسال جزئي دون P0 | concern; entry=yes; sudden=no؛ بقية أسئلة الخطر غير مرسلة | **HTTP 422** |

> **ملاحظة على TC-34:** بديل مدروس هو قبول القيمة وتقييمها (الأكثر تحفظاً). اخترنا 422 لأن الواجهة لا تُظهر السؤال أصلاً، فوصول قيمة يعني طلباً غير صادر عن الواجهة. **يُعرض على المراجع الطبي** ضمن RQ-3.

## 3. تغطية المتطلبات

| المتطلب | الحالات |
|---|---|
| أعلى أولوية (SAFE-005) | TC-04, TC-15, TC-21, TC-27 |
| P0 stop | TC-01..TC-04 |
| غير معروف/لست متأكداً (SAFE-006) | TC-07, TC-26, TC-35 |
| أسئلة بلا إجابة (FR-041/SAFE-007) | TC-08, TC-22, TC-36, TC-37 |
| تعارض/تلاعب | TC-23, TC-24, TC-25, TC-33, TC-34 |
| الأهلية | TC-10 |
| المسارات | TC-11..TC-13, TC-16, TC-33 |
| الحتمية واللغة | TC-28, TC-29 |
| البيئات | TC-30, TC-31, TC-32 |
| عدم الطمأنة الزائفة | TC-20 |

## 4. اختبارات إضافية غير سريرية مرتبطة (في M06/M07)

- لا كتابة في قاعدة البيانات/الجلسة/السجل أثناء أي حالة أعلاه (عدا `daily_metrics`).
- `Cache-Control: no-store` على كل استجابة.
- نصوص الإرشاد الناتجة خالية من قاموس العبارات المحظورة (SAFE-010).
