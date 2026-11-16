# Competition Readiness Audit — Rafiq | رفيق

> Status: Audit · Date: 2026-11-16 · Branch: `feature/m00-foundation` (uncommitted audit fixes) · Ruleset: RS-2026.1 (**Draft — pending qualified medical review**)
>
> Source of truth: the six official competition criteria. A criterion is marked **Fully** only when the requirement is implemented end to end, verified by automated tests, demonstrable, and has no open dependency that the requirement itself names. Otherwise it is **Partially** or **Not** implemented.
>
> Nothing in this audit approves clinical content. All rules, guidance texts, care destinations and the doctor question bank remain `draft` and run only in demo mode outside production (`ActiveRulesetResolver`, `DoctorPrepBank`).

---

## 0. Summary

| # | Criterion | Status | Demo-ready? | Main blocker |
|---|---|---|---|---|
| 1 | Early, clear risk identification; screening ≠ diagnosis | **Partially** | Yes (as draft) | No medical review of questions, rules or risk-domain mapping; test results are self-reported only |
| 2 | Culturally trustworthy experience | **Partially** | Yes | No cultural/religious or user review of wording; one question assumes a female partner |
| 3 | Smooth, low-friction assessment | **Partially** | Yes (guidance only) | Telehealth and services are described, not connected; no country service directory |
| 4 | Safe routing and referral | **Partially** | Yes (as draft) | Rules and destinations not medically approved; two routing branches need clinical decisions; no local emergency number or provider list |
| 5 | Privacy by design | **Partially** (technically strong) | Yes | Final privacy policy, data controller, DPIA and legal review missing; admin-account deletion not implemented |
| 6 | Tangible, measurable value | **Partially** | Yes (with honest labelling) | No real-user data yet; follow-through is a proxy; equity limited to language/device |

**Test baseline (2026-11-16):** PHPUnit 329 tests (328 passed, 1 skipped) on SQLite and MySQL; clinical test cases `rafiq:clinical:test RS-2026.1` = **45/45**; AI safety evaluation suite (in `AiFiltersTest`) passes; ESLint, TypeScript, Prettier clean; `composer audit` / `npm audit --omit=dev` = 0 advisories.

---

## 1. Criterion-by-criterion audit

### Criterion 1 — Early and clear risk identification

**Exact requirement:** Use medical history (with patient consent), lifestyle, metabolic, infectious and anatomical factors, and available test results to identify relevant risk indicators *before* a diagnosis of delayed fertility, with a clear distinction between screening and final medical diagnosis.

| Item | Detail |
|---|---|
| Implemented feature | Guided assessment (J2 concern, J3 conception) after explicit consent. Questions cover: **medical history** (chronic conditions, long-term medicines, testosterone/steroids, chemo/radiotherapy, family history, sexual/urinary symptoms); **lifestyle** (smoking, occupational exposure, optional `as_lifestyle`: heat, inactivity, alcohol, drugs, sleep/stress); **metabolic** (diabetes, obesity, hypertension, thyroid, weight concern); **infectious** (mumps after puberty, STI history, burning/discharge); **anatomical** (undescended testis, varicocele, surgery/injury); **test results** (previous fertility tests: told abnormal / normal / unsure). Each risk option maps to a `RiskDomain`; the report lists "Risk indicators we noticed" by domain with "an indicator is not a diagnosis", and shows a "This is a screening, not a diagnosis" card on every non-emergency result. |
| Module / files | M04, M05, M06, M07 · `resources/clinical/rulesets/RS-2026.1/questions.json` (`risk_domains`), `rules.json` (CR-006…CR-018), `app/Enums/RiskDomain.php`, `app/Services/Clinical/ClinicalRulesEngine.php` (`withAnswerFlags` → `riskDomains`), `RulesetSchemaValidator.php`, `app/Services/Assessment/GuidanceService.php`, `resources/js/pages/Public/Assessment.tsx` |
| Automated tests | Clinical cases TC-42…TC-45 (domains, lifestyle, prefer-not); `AssessmentTest::test_the_report_separates_screening_from_diagnosis_routes_to_a_care_pathway_and_groups_risk_indicators`; `ClinicalRulesEngineTest` (import, determinism, schema validation); `GatesTest::test_assessment_journeys_require_consent`, `test_consent_must_be_explicitly_ticked` |
| Demonstrable evidence | Run J3 in Arabic → report shows domain cards (التاريخ الطبي، نمط الحياة…) and the screening card; `php artisan rafiq:clinical:test RS-2026.1` table |
| Remaining gaps | (a) No qualified medical review of any question, option→domain mapping or rule (OQ-003). (b) Test results are **self-reported categories only**; Rafiq deliberately does not accept or interpret lab values — acceptable for screening, but "available test results" is only partly used. (c) No objective metabolic measures (BMI, blood pressure) by data-minimisation choice. (d) Risk indicators are not weighted or scored — they are presence flags. |
| Status | **Partially implemented** — functionally complete for a demo, clinically unapproved. |

### Criterion 2 — Culturally trustworthy experience

**Exact requirement:** Accurate, private, easy-to-understand communication that respects religious, social and cultural concepts, avoids social stigma, and does not assume particular choices about the user's family.

| Item | Detail |
|---|---|
| Implemented feature | Full Arabic (RTL) and English (LTR) with identical key sets; plain-language guidance; "Your values, your choices" notice on the first assessment question (all questions optional, no assumption about family plans, respect for religious and cultural values, answers not stored); "Prefer not to say" on sensitive questions (lifestyle, partner age); "not trying" option; neutral wording ("delayed conception" rather than stigmatising terms); Quick Exit and "Clear everything" for privacy in shared spaces; no name or identifiers asked. |
| Module / files | M01–M05, M08 · `lang/ar.json`, `lang/en.json` (`assessment.respect_*`), `Assessment.tsx`, `QuickExitButton.tsx` |
| Automated tests | `LocalizationFilesTest` (same keys, no empty strings, logical CSS only); `LocaleRoutingTest`; TC-45 (prefer-not gives no finding); `GatesTest::test_clear_everything_ends_the_session_and_clears_browser_history` |
| Demonstrable evidence | Switch AR↔EN on any page; first assessment question shows the respect notice; Quick Exit |
| Remaining gaps | (a) No review by a cultural/religious advisor or target users (usability test with ≥5 users is planned in M12, not done). (b) `as_partner_age_band` asks about "your partner's age" with feminine Arabic wording (شريكتك) — an assumption about family structure that should be reviewed. (c) Alcohol / drug options may need culturally sensitive wording review. (d) Tone review of all guidance by a clinician + native editor not done. |
| Status | **Partially implemented** |

### Criterion 3 — Smooth, less complex assessment

**Exact requirement:** Remove key access barriers through options such as private digital registration, telehealth consultations and home-based steps when clinically appropriate, plus clear service navigation and fewer unnecessary hospital visits.

| Item | Detail |
|---|---|
| Implemented feature | No registration at all (anonymous — stronger than "private registration"); ~5-minute assessment with skip on every non-safety question; each care destination shows access options: "a remote (telehealth) consultation can be a first step", "in-person only if the doctor asks", "an in-person examination is needed" (when the rule sets `prompt_in_person_exam`), "steps you can take at home" (self-care only), "prepare your questions"; J4 doctor-visit preparation (questions + what to bring, print / .txt) to make a visit shorter. |
| Module / files | M05, M07, M10 · `GuidanceService::carePathway()`, `app/Enums/CarePathway.php` (`suitsTelehealth`), `Assessment.tsx`, `DoctorPrep.tsx`, `lib/doctorPrepHandoff.ts` |
| Automated tests | `AssessmentTest::test_urgent_and_emergency_results_never_offer_telehealth_or_home_steps`, `test_a_needed_examination_replaces_telehealth_and_skipped_questions_are_never_reassuring`; `DoctorPrepTest` (4 tests); `JourneysTest` |
| Demonstrable evidence | Report "Your recommended next step" card with access chips; report → "Prepare for your doctor visit" hand-off |
| Remaining gaps | (a) **Telehealth is advice, not a service**: no booking, provider list or integration. (b) No service directory (clinics, fertility centres, urology) — needs a target country and verified providers. (c) "Home steps" are limited to general lifestyle guidance in CR-018 / self-care; no clinically validated home tests are offered (correctly — none are approved). (d) Whether telehealth-first is clinically appropriate per destination is a medical decision not yet reviewed. |
| Status | **Partially implemented** |

### Criterion 4 — Safe routing and referral

**Exact requirement:** Direct users to the appropriate next step (self-care information, primary care, andrology/urology, fertility services, or urgent assessment), with explicit red flags, clinical limits and human medical oversight.

| Item | Detail |
|---|---|
| Implemented feature | Every safety/assessment rule outcome declares a `care_pathway` (validator rejects outcomes without one); the engine selects the destination of the **most urgent priority first**, then the more specialised destination, and lists others as "also relevant". Red flags are asked first; a P0 answer stops immediately with emergency instructions; P1 stops the assessment with same-day advice; "unsure" on an emergency question escalates to P1 with emergency criteria. Every report has a "Clinical limits and human medical oversight" section (fixed written rules; draft vs approved status; doctor decides; no AI in the result). AI companion is fully separate from the engine. |
| Module / files | M04, M06, M07, M09 · `rules.json` (`care_pathway`, `care_pathway_by_journey`), `CarePathway.php`, `ClinicalRulesEngine::pathways()`, `RulesetSchemaValidator::checkOutcome()`, `RulesetTestRunner` (`care_pathway`, `risk_domains`), `GuidanceService`, `Assessment.tsx` |
| Automated tests | TC-01…TC-37 (priorities, red flags), TC-38…TC-41 (destinations), **TC-46…TC-49 (escalation overrides, unanswered)**; `AssessmentTest` (emergency immediate screening, fever stop, urgent never telehealth); `ClinicalAdminTest` (release cannot be submitted before cases pass); `AiCompanionTest::test_warning_signs_get_the_emergency_reply_without_calling_the_model`; `AiFiltersTest::test_the_ai_layer_never_depends_on_the_clinical_rules_engine` |
| Demonstrable evidence | Emergency demo (sudden severe pain → emergency screen at once); fever → urgent same-day; trying > 12 months → fertility services; `rafiq:clinical:test` 45/45 |
| Remaining gaps | (a) **No rule, destination or guidance text is medically approved** (OQ-003). (b) Two branches need an explicit clinical decision (see §2.4). (c) Same-priority tie-break ranks fertility services above urology/andrology — a clinician must confirm (e.g. new lump + trying > 12 months currently shows fertility services first, with an in-person exam required). (d) Emergency number is `null` until a country is chosen; the UI says "call your local emergency number" (OQ-002/008). (e) No list of verified referral destinations. |
| Status | **Partially implemented** — logic complete and tested; clinical approval missing. |

### Criterion 5 — Privacy by design

**Exact requirement:** Explicit consent, data minimisation, user control over data sharing, strong access controls, and a clear explanation of how reproductive data is used and protected from misuse.

See §3 for the full audit.

| Item | Detail |
|---|---|
| Implemented feature | Age gate + explicit consent checkbox before any health question; answers held only in browser memory, evaluated in one request, never stored or logged; no identifiers collected; session whitelist; no IP/UA in session store; log scrubbing; answers never sent to the AI; admin with roles, mandatory TOTP 2FA, password confirmation for sensitive actions, four-eyes review, immutable audit log; aggregate counters with < 5 suppression; retention clean-up scheduled; CSP, no-referrer, Permissions-Policy; noindex and encrypted history on sensitive pages; Quick Exit and "Clear everything". |
| Module / files | M00, M03, M09, M11-A, M11-E, M12 · `GateController`, `SessionController`, `SecurityHeaders`, `config/rafiq.php` (`public_session_keys`, `sensitive_keys`, `retention`), `MetricsRecorder`, `MetricsReport`, `PruneCommand`, `AuditLogger` |
| Automated tests | `GatesTest` (21), `PublicSessionPrivacyTest`, `SecurityHeadersTest`, `LogScrubbingTest`, `AiCompanionTest::test_personal_details_and_health_disclosures_never_reach_the_model`, `LaunchReadinessTest` (no forbidden columns, admin behind auth, all writes throttled), `ScheduledTasksTest` (prune), `AdminAuthAndRolesTest`, `MetricsRecorderTest::test_free_text_is_rejected_as_a_metric_dimension`, `DoctorPrepTest::test_no_route_can_receive_the_visitors_own_questions` |
| Remaining gaps | Legal and governance items (privacy policy final text, data controller identity, DPIA, jurisdiction) and admin-account deletion — see §3.2. |
| Status | **Partially implemented** — technical controls are implemented and tested; legal/governance requirements are not met yet. |

### Criterion 6 — Tangible, measurable value

**Exact requirement:** Demonstrate effectiveness through real cases or simulation, with KPIs such as completed assessments, time to reach the appropriate assessment, referral completion rate, user trust, and fairness across relevant population groups.

See §4 for the full audit.

| Item | Detail |
|---|---|
| Implemented feature | Admin analytics "Impact indicators": assessment completion rate, guidance within 6 minutes, next-step actions (proxy), average trust (1–5), clarity; equity table (completion by language × device); `rafiq:simulate` (synthetic visitors through the real engine). |
| Module / files | M11-E, M12 · `MetricsReport::kpis()/equity()`, `AssessmentController::recordMetrics()`, `ReportFeedbackController`, `SimulateCommand`, `Admin/Analytics/Index.tsx` |
| Automated tests | `AnalyticsTest` (6), `AssessmentTest::test_impact_counters_record_time_to_guidance_and_language_device_segments_without_the_result`, `test_report_feedback_accepts_only_fixed_signals_and_needs_consent` |
| Remaining gaps | No real-user data; referral completion is not measurable (only an in-app proxy); equity is limited to language/device; simulation is synthetic. |
| Status | **Partially implemented** |

---

## 2. Clinical safety review

> Reviewer note: this is an engineering review of behaviour, not a clinical approval.

### 2.1 Screening versus diagnosis — **Implemented (draft wording)**
- Report card "This is a screening, not a diagnosis" on urgent and routine results; risk section hint "an indicator is a reason to talk to a doctor, not a diagnosis"; oversight section; `report.limits`; download text includes the screening statement.
- Forbidden diagnostic phrases are rejected at import (`ForbiddenPhraseChecker`).
- Gap: wording not clinically reviewed.

### 2.2 Emergency red flags — **Implemented**
- Safety questions come first and are required; `stop_on_yes` questions are screened immediately on "yes" without waiting for the rest (`AssessmentTest::test_an_emergency_answer_is_screened_immediately_without_the_remaining_questions`).
- P0 rules: CR-002 (sudden severe pain), CR-003 (pain with nausea/vomiting/abdominal pain), CR-017 (pain > 1 h or at rest, *proposed*).
- Emergency number: not invented; `null` ⇒ "call your local emergency number".

### 2.3 P0 / P1 escalation and override — **Verified**
- Engine: highest priority wins (BR-06-1); P0 with `stop` ends evaluation; destination chosen from the most urgent priority group first.
- Verified by: TC-38 (P0 → urgent), **TC-46** (P1 fever + fertility + lifestyle → urgent), **TC-47** (unsure emergency question + routine answers → urgent, emergency criteria shown), **TC-48** (P0 with routine answers → stopped, urgent), and `AssessmentTest::test_urgent_and_emergency_results_never_offer_telehealth_or_home_steps` (screen and evaluate: access is only `same_day` or `emergency`).
- **Conclusion: urgent and emergency outcomes always override telehealth and self-care recommendations** in the current engine.

### 2.4 Care destination selection — **Implemented; needs clinical decisions**
| Item | Current behaviour | Why it needs review | Priority |
|---|---|---|---|
| CR-004 branch "unsure about fever" with pain/swelling | P2 → primary care; access shows "telehealth first" plus the notice "if you confirm a fever, get assessed today" | Pain/swelling with possible fever may warrant same-day assessment rather than telehealth-first | **High** |
| CR-005 branch "unsure about a new lump" | P3 → self-care; access shows "home steps" (self-check) | A clinician should decide whether uncertainty about a lump should route to an examination | **High** |
| Same-priority tie-break | urgent > fertility services > urology/andrology > primary care > self-care | e.g. new lump + trying > 12 months shows fertility services first (in-person exam still required, urology listed as "also relevant") | Medium |
| `care_pathway_by_journey` overrides (CR-007/008/009) | conception journey → fertility services | Clinical confirmation | Medium |

### 2.5 Telehealth versus in-person — **Implemented as guidance**
- Telehealth-first only for primary care, urology/andrology and fertility services, and **never** when a rule sets `prompt_in_person_exam` (then "an in-person examination is needed") — tested.
- Never for urgent (`same_day` / `emergency`) — tested.

### 2.6 Home-based steps — **Restricted to self-care**
- "Steps you can take at home" appears only when the destination is self-care. Content is general lifestyle guidance (CR-018, smoking, occupational) — no home tests or treatments.
- See CR-005 above: self-care currently also covers "unsure about a lump" (needs review).

### 2.7 Human medical oversight — **Implemented in workflow; no reviewer yet**
- Four-eyes review in admin (verified medical reviewer, no self-review), release cannot be submitted before all test cases pass, production uses only published + approved rules, drafts only in demo outside production, audit log of approvals.
- Gap: **no qualified medical reviewer has been appointed** (OQ-003). Nothing has been approved, by design.

### 2.8 Unknown and unanswered responses — **Implemented; improved in this audit**
- Safety questions are required; "unsure" on an emergency question → P1 (SAFE-006).
- Skipped assessment questions → `unanswered_present` notice ("this guidance may be incomplete"); "don't know" answers → doctor topic "Questions I didn't know how to answer".
- **Fixed in this audit:** when questions were skipped and the destination is self-care, the report no longer says "nothing in your answers needs a medical visit"; it says the picture is incomplete and to see a doctor for symptoms or worries (`report.pathway.self_care.body_incomplete`, tested; TC-49).
- "Prefer not to say" is respected and never treated as a finding (TC-45).

---

## 3. Privacy and consent audit

### 3.1 Implementation check
| Requirement | Implemented? | Evidence |
|---|---|---|
| Explicit consent | **Yes** — unticked checkbox, consent per session, declining leads to Learn; AI needs a separate notice acknowledgement | `GatesTest::test_consent_must_be_explicitly_ticked`, `test_ai_notice_must_be_acknowledged_before_any_ai_request` |
| Data minimisation | **Yes** — no name/contact/ID/date of birth (age gate is yes/no only); answers not stored; doctor-prep text never leaves the browser; counters with fixed dimensions only | `GatesTest::test_a_birth_date_is_never_accepted_instead_of_a_yes_or_no`, `LaunchReadinessTest`, `DoctorPrepTest`, `MetricsRecorderTest` |
| User control over sharing | **Yes, by absence of sharing** — nothing is shared; the user alone decides to print or download the report/notes locally; "Clear everything" and Quick Exit | `GatesTest::test_clear_everything_…` |
| Access control | **Yes** — admin-only data; RBAC, mandatory TOTP 2FA, password confirmation, rate-limited login, inactive accounts cut off, immutable audit log with hashed IP | `AdminAuthAndRolesTest` (13 tests) |
| Retention and deletion | **Mostly** — answers never stored; session 30 min (env); `rafiq:prune` deletes AI usage logs > 90 days and audit logs > 24 months daily; failed jobs 7 days | `ScheduledTasksTest`, `LaunchReadinessTest::test_retention_review_and_budget_tasks_are_scheduled` |
| Privacy information shown | **Yes (draft)** — consent screen bullet points; privacy page (what is processed, never collected, cookies, retention, rights); AI notice | Privacy page `legal.privacy.*` |
| Protection of reproductive health information | **Yes** — answers never persisted, never logged (sensitive-key scrubbing), never sent to Gemini (pre-filter + tests), no-store + noindex + encrypted history on sensitive pages, CSP | `AiCompanionTest`, `LogScrubbingTest`, `SecurityHeadersTest`, `JourneysTest::test_sensitive_journey_pages_encrypt_history_in_secure_contexts_and_prevent_indexing` |

### 3.2 Documented but not (fully) implemented
| Item | Documented in | Actual state | Priority |
|---|---|---|---|
| Final privacy policy, data controller identity and contact | Privacy page, OQ-031 | Placeholder text ("will be published before public launch") | High (public launch) / Medium (demo) |
| DPIA and legal/regulatory review (SaMD) | M12-FR-07, OQ-002/024 | Not done | High (public launch) |
| Deletion of deactivated admin accounts after 12 months | Data-Lifecycle §2 | **Done (follow-up):** `deactivated_at` recorded; Super Admin can delete only after 12 months, with password confirmation; audit keeps the old id (`AdminAuthAndRolesTest::test_deactivated_accounts_can_be_deleted_only_after_the_retention_period`) | Done |
| Optional `localStorage` save with explicit consent | M10-FR-08 / FR-095 (Could) | Not implemented (nothing is stored on the device — privacy-safe) | Low |
| Session lifetime 30 min | Privacy page | **Done (follow-up):** default is now 30; production refuses to boot with more than 30 minutes or the `database` session driver (`LaunchReadinessTest::test_production_refuses_long_sessions_and_the_database_session_driver`) | Done |
| Web-server/hosting log retention ≤ 14 days | Data-Lifecycle §2 | Hosting not chosen (OQ-011) | Medium |
| **New counters disclosure** | — | Was missing; **fixed in this audit**: privacy page now lists time band, device type and the optional trust/clarity rating | Done |

---

## 4. Impact measurement audit

### 4.1 Classification of every metric
| Metric | Type | What it really measures | Must not be presented as |
|---|---|---|---|
| `assessment_started` / `assessment_completed` (per journey) | Anonymous aggregate counter of real use | Server-side count of screening and evaluation requests | Unique users (one person can repeat) |
| Completion rate | Derived from aggregate counters | completed ÷ started requests | A per-person completion rate |
| `assessment_time` bands → "guidance within 6 minutes" | Anonymous counter from a **client-reported** elapsed time | Time from opening the assessment page to the result, as sent by the browser (not verified; can be wrong if the tab was idle) | Clinically "time to appropriate care" |
| `next_step_taken` (print, download, doctor_prep, article) → "Next-step actions (proxy)" | **Indirect engagement proxy** | Clicks inside Rafiq; one visitor may click several (rate is capped at 100% and is an upper bound) | **Referral completion** or care received — Rafiq cannot know whether anyone saw a doctor |
| `report_trust` (1–5), `report_clear` (yes/no) | Actual user measurement (voluntary, anonymous) | Self-reported trust/clarity from those who chose to answer (self-selection bias) | Representative satisfaction |
| `assessment_*_segment` (`locale:device`) → equity table | Anonymous counter | Completion by interface language and mobile/desktop (derived from the user-agent at request time, not stored) | Fairness across age, gender, region, income or other population groups |
| `rafiq:simulate` output | **Synthetic simulation** | How the ruleset routes random, weighted synthetic answers (reproducible by seed) | Real-world prevalence, outcomes or "hospital visits avoided" |
| All rates | Suppression | Any rate whose denominator is < 5 shows "not enough data" | — |

### 4.2 Limitations to state to judges
1. No real-user data exists yet; all KPI values in a demo come from test traffic.
2. Referral completion **cannot** be measured without follow-up or provider integration, both of which would require collecting identifiers — deliberately not done. The in-app proxy is labelled "(proxy)" in the admin UI and the hint states it is not confirmed referral.
3. Equity is limited to language and device type because no demographics are collected by design (privacy criterion). Proper fairness evaluation would need a separately consented research study.
4. The simulation figure (e.g. "95.3% could start remotely or at home", seed 2026, n = 2000) reflects routing of synthetic answers only and must always be shown with "synthetic — not real-world prevalence".
5. Counters count requests, not people; repeated assessments inflate both numerator and denominator.
6. Distribution of priorities/destinations is deliberately **not** shown in analytics (OQ-010).

### 4.3 Corrected in this audit
- Admin KPI hint and label now say "Next-step actions (proxy)" and "not confirmed medical referral or care completion".
- Equity hint now states it cannot show fairness across population groups.
- Module-12 notes §20.7 wording corrected (it previously called the proxy a "safe substitute for referral completion").

---

## 5. Local care access

| Capability | Functional now | Requires |
|---|---|---|
| Emergency instructions | Yes — "call your local emergency number"; `tel:` button only if a number is configured | Country decision and verified number (`RAFIQ_EMERGENCY_COUNTRY`, `config/rafiq.php` → `emergency.numbers`) — OQ-002/008. **No number has been invented.** |
| Care destination type (self-care / primary / urology-andrology / fertility / urgent) | Yes (draft) | Medical approval |
| Telehealth suggestion | Text only ("a remote consultation can be a first step") | List of verified telehealth services or integration; country regulation on telemedicine |
| Service directory (clinics, fertility centres, urology, primary care) | **Not implemented** | Target country, verified provider data source, update/ownership process, legal review of listing providers |
| Booking / referral hand-off | **Not implemented** | External integrations (national health portal, booking APIs) and consent design |
| Doctor-visit preparation (questions, what to bring, print) | Yes | Medical review of the question bank |
| Home-based testing (e.g. home semen test kits) | Not offered (correctly) | Clinical evidence review and approval before any mention |

**Do not demonstrate any provider names, phone numbers or links** until a verified directory exists.

---

## 6. Compliance matrix

| Criterion | Implementation evidence | Test evidence | Remaining gaps | Medical review dependency | Privacy / legal dependency | Demo readiness | Priority of unresolved items |
|---|---|---|---|---|---|---|---|
| 1. Risk identification | Risk domains on 11 questions, 20 rules, report domain cards, screening card | TC-42…45, `AssessmentTest` (domains), engine/import tests | Self-reported tests only; no weighting; unreviewed | Questions, option→domain mapping, CR-018 (proposed) | — | **Ready (draft banner)** | High: medical review |
| 2. Cultural trust | AR/EN RTL, respect notice, optional questions, prefer-not, Quick Exit | `LocalizationFilesTest`, TC-45, `GatesTest` clear-everything | No cultural/user review; partner wording assumption | Tone of guidance texts | — | **Ready** | Medium: cultural review + partner wording; Medium: 5-user usability test |
| 3. Low friction | No registration, skip-able, access options per destination, J4 prep | `AssessmentTest` access tests, `DoctorPrepTest` | Telehealth/services not connected; no directory | Telehealth appropriateness per destination | Telemedicine rules per country | **Ready (guidance only)** | High: country decision; Medium: directory |
| 4. Safe routing | `care_pathway` on every outcome, override by priority, red flags, oversight section | TC-01…49, override tests, `ClinicalAdminTest` | Not approved; CR-004/CR-005 unsure branches; tie-break; no emergency number | **All rules, destinations, texts** (OQ-003) | SaMD / regulatory opinion (OQ-024) | **Ready (draft banner)** | **Critical: medical reviewer**; High: CR-004/005 decisions; High: emergency number |
| 5. Privacy by design | Consent, minimisation, no storage, RBAC + 2FA, audit, suppression, prune, CSP | `GatesTest`, `PublicSessionPrivacyTest`, `LaunchReadinessTest`, `AiCompanionTest`, `AdminAuthAndRolesTest`, `ScheduledTasksTest` | Final privacy policy, controller, DPIA; admin deletion flow | — | **Privacy policy (OQ-031), DPIA, jurisdiction (OQ-002), Gemini terms (OQ-006)** | **Ready** | High: privacy policy + controller (public); Medium: session env check; Low: admin deletion |
| 6. Measurable value | KPI panel, equity table, feedback, `rafiq:simulate` | `AnalyticsTest`, `AssessmentTest` counters/feedback | No real data; proxy only; limited equity; synthetic simulation | — | Any follow-up study needs separate consent | **Ready with honest labelling** | Medium: pilot with real (consented) users; Low: research design for equity |

---

## 7. Demonstration readiness

| Item | State |
|---|---|
| Demo mode banner and "not medically approved" notices | Present on every page, report and doctor-prep |
| Synthetic data only | Seeders: content, clinical demo, doctor-prep demo, admin demo accounts |
| Four journeys | Working in AR/EN; verified in browser |
| AI companion | **Gemini key currently rejected (401)** — use `RAFIQ_AI_PROVIDER=fake` for the demo, or obtain a valid key; explain that health answers are never sent |
| Admin | Demo accounts with 2FA; analytics, content workflow, clinical releases, AI settings, audit log |
| Scripted demo | Module-12 §20.4 |
| Staging environment | **Not set up** (OQ-011) |
| Accessibility / performance audit (axe, Lighthouse) | **Not run** |

---

## 8. Prioritised remaining items

| Priority | Item | Owner (proposed) | Blocks |
|---|---|---|---|
| **Critical** | Appoint a qualified medical reviewer; review RS-2026.1 rules, care destinations, risk-domain mapping, guidance texts, doctor question bank | Product Owner | Public launch; credibility of criteria 1 & 4 |
| **High** | Clinical decisions on CR-004 "unsure fever" and CR-005 "unsure lump" routing, and the same-priority tie-break | Medical reviewer | Criterion 4 |
| **High** | Decide target country → emergency number, telemedicine rules, service landscape | Product Owner | Criteria 3 & 4 |
| **High** | AI demo path: valid Gemini key on a paid tier, or fake provider for the demo | Product Owner / Dev | Demo |
| **High** | Staging deployment with cron and `SESSION_LIFETIME=30` | Dev | Demo |
| High (public) | Final privacy policy, data controller, DPIA, legal/SaMD opinion | Legal | Public launch (criterion 5) |
| Medium | Cultural/religious wording review, incl. partner-age question wording | Product + advisor | Criterion 2 |
| Medium | Usability test with ≥ 5 users (synthetic scenarios); accessibility (axe) and Lighthouse runs | Product / Dev | Criteria 2, 3, 6 |
| Medium | Verified service directory design (data source, ownership, updates) | Product Owner | Criterion 3 |
| Medium | Consented pilot to collect real KPI data | Product Owner | Criterion 6 |
| Done | Admin account deletion flow after 12 months | Dev | — |
| Low | Optional device-save (localStorage) with consent | Dev | M10 Could |

---

## 9. Changes made during this audit (uncommitted)

All changes are non-clinical, keep every rule in draft and do not weaken privacy:
1. `GuidanceService`: self-care text switches to an "incomplete picture" version when questions were skipped (`report.pathway.self_care.body_incomplete`).
2. Privacy page `legal.privacy.process4` now discloses the new anonymous counters.
3. Admin analytics: KPI renamed "Next-step actions (proxy)"; KPI and equity hints state their limits.
4. Module-12 §20.7 wording corrected (proxy ≠ referral completion).
5. New verification tests: clinical cases TC-46…TC-49 and two `AssessmentTest` tests for escalation override, in-person exam and incomplete self-care.

## 10. Follow-up work after the audit (2026-11-16, uncommitted)

| Item | What was done | Tests |
|---|---|---|
| Session safeguards | `config/session.php` default 30 min; `BootChecks` refuses > 30 min or the `database` session driver in production | `LaunchReadinessTest` |
| Admin account deletion | `users.deactivated_at`; `UserPolicy::delete` (Super Admin, not self, deactivated ≥ `rafiq.retention.deactivated_admin_months` = 12); `DELETE /admin/users/{user}` behind password confirmation; audit `user.deleted` with the old id only | `AdminAuthAndRolesTest` |
| Doctor question bank & glossary review (M11-B step 5) | `TextBankWorkflow` + `TextBankController` + `Admin/Content/TextBank`: edit → submit → approve/return by a verified medical reviewer who did not edit it (four-eyes) → publish/hide; editing an approved text returns it to draft; audit `doctor_question.*` / `glossary.*`. This gives the medical reviewer a working place to approve the doctor question bank and glossary (nothing was approved) | `TextBankTest` |
| Explain a glossary term (M09 `explain_term`) | "Ask Rafiq AI to explain" on each approved term when the assistant is enabled; only the term id is sent | `TextBankTest` |
| "What does this mean?" in doctor prep (M10-FR-09) | Glossary link and an empty Rafiq AI panel; the visitor's list is never sent | `TextBankTest` |
| Bug fix | Unknown `/admin/...` URLs returned HTTP 500 (shared data read the session before it existed); now 404 | `TextBankTest` (unknown bank → 404) |
| CI | Already present (`.github/workflows/ci.yml`: typecheck, lint, format, build, audits, PHPUnit on SQLite and MySQL) | — |

Test baseline after follow-up: **335 tests (334 passed, 1 skipped) on SQLite and MySQL**; ESLint, TypeScript, Prettier and build clean.

Still requiring people or decisions (unchanged): medical reviewer and clinical decisions (§8 Critical/High), target country (emergency number, telemedicine, service directory), AI provider for the demo, staging, privacy policy/DPIA/legal opinion, cultural review, usability and accessibility testing, consented pilot for real KPIs.
