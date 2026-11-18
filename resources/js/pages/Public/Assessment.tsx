import { Link, usePage } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft, ArrowRight, CheckCircle2, Compass, Download, FlaskConical, Phone, Printer, RotateCcw, ShieldAlert, SkipForward, Stethoscope } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { ChangeJourney } from '@/components/public/ChangeJourney';
import { ConfirmDialog } from '@/components/public/ConfirmDialog';
import { EmergencyNotice } from '@/components/public/EmergencyNotice';
import { PageIntro } from '@/components/public/PageIntro';
import { Alert } from '@/components/ui/Alert';
import { buttonClasses } from '@/components/ui/Button';
import { Spinner } from '@/components/ui/Spinner';
import { PublicLayout } from '@/layouts/PublicLayout';
import { setDoctorPrepHandoff } from '@/lib/doctorPrepHandoff';
import { JsonRequestError, postJson } from '@/lib/http';
import { useT } from '@/lib/i18n';
import type { JourneyKey } from '@/types';

type Condition = { q: string; op: string; value?: string | string[] };

type Question = {
    key: string;
    section: 'safety' | 'assessment';
    type: 'boolean_unsure' | 'single' | 'multi' | 'band';
    required: boolean;
    prompt: string;
    options: { value: string; label: string }[];
    exclusive: string[];
    show_if: Condition | null;
    stop_on_yes: boolean;
};

type Report = {
    priority: string | null;
    label: string | null;
    headline: string | null;
    nextStep: string | null;
    guidance: string[];
    doctorTopics: string[];
    notices: string[];
    showEmergencyCriteria: boolean;
    articles: { title: string; url: string }[];
    carePathway: { key: string; title: string; body: string; access: { key: string; label: string }[] } | null;
    otherPathways: { key: string; title: string }[];
    riskDomains: { key: string; label: string; body: string }[];
    rulesetVersion: string;
    demo: boolean;
};

type FeedbackSignal = 'report_trust' | 'report_clear' | 'next_step_taken';

/** Whole seconds since a timestamp, for the anonymous time-to-guidance band. */
function secondsSince(start: number): number {
    return Math.round((Date.now() - start) / 1000);
}

/** Fire-and-forget anonymous counter; a failure never affects the visitor. */
function sendSignal(url: string, signal: FeedbackSignal, value: string): void {
    void postJson(url, { signal, value }).catch(() => undefined);
}

type Answer = string | string[];

type AssessmentProps = {
    journey: 'concern' | 'conception';
    questions: Question[] | null;
    demo: boolean;
    screenUrl: string;
    evaluateUrl: string;
    feedbackUrl: string;
    learnUrl: string | null;
    doctorPrepUrl: string | null;
    otherJourneys: { key: JourneyKey; url: string | null }[];
};

type Step = { name: 'safety'; index: number } | { name: 'assessment'; index: number } | { name: 'result'; kind: 'emergency' | 'urgent' | 'report'; report: Report } | { name: 'unavailable' } | { name: 'error' };

const PRIORITY_STYLES: Record<string, string> = {
    P0: 'border-danger bg-danger-soft text-danger',
    P1: 'border-warning bg-warning-soft text-warning',
    P2: 'border-info bg-info-soft text-info',
    P3: 'border-brand bg-brand-soft text-brand-strong',
    P4: 'border-line-strong bg-surface-sunken text-ink-muted',
};

/** Display-only show_if check; the engine re-validates on the server. */
function isVisible(question: Question, answers: Record<string, Answer>): boolean {
    const condition = question.show_if;

    if (condition === null) {
        return true;
    }

    const answer = answers[condition.q];

    if (condition.op === 'in' && Array.isArray(condition.value)) {
        return typeof answer === 'string' && condition.value.includes(answer);
    }

    return condition.op === 'eq' ? answer === condition.value : true;
}

/**
 * J2/J3 guided assessment (Module-04 → Module-05 → Module-07). Answers live only in this page's memory and
 * are sent to the deterministic Clinical Rules Engine; nothing is stored. Warning signs come first, and a
 * single emergency answer stops everything (M04-FR-03).
 */
export default function Assessment({ journey, questions, demo, screenUrl, evaluateUrl, feedbackUrl, learnUrl, doctorPrepUrl, otherJourneys }: AssessmentProps) {
    const t = useT();
    const { emergency } = usePage().props;
    const safety = useMemo(() => (questions ?? []).filter((question) => question.section === 'safety'), [questions]);
    const assessment = useMemo(() => (questions ?? []).filter((question) => question.section === 'assessment'), [questions]);
    const [answers, setAnswers] = useState<Record<string, Answer>>({});
    const [step, setStep] = useState<Step>(questions === null ? { name: 'unavailable' } : { name: 'safety', index: 0 });
    const [busy, setBusy] = useState(false);
    const [restarting, setRestarting] = useState(false);
    // Start time for the anonymous "time to guidance" band; kept in memory only.
    const startedAt = useRef<number | null>(null);

    useEffect(() => {
        startedAt.current = Date.now();
    }, []);

    // Every new step starts at the top with focus on its heading, so an emergency result is never off-screen.
    useEffect(() => {
        window.scrollTo({ top: 0 });
        document.querySelector<HTMLElement>("#result-title, #question-title")?.focus({ preventScroll: true });
    }, [step]);

    const firstVisibleSafety = (from: number, current: Record<string, Answer>): number => {
        for (let index = from; index < safety.length; index++) {
            const question = safety[index];

            if (question !== undefined && isVisible(question, current)) {
                return index;
            }
        }

        return -1;
    };

    /** Safety answers with hidden questions marked not_applicable (Clinical-Test-Cases conventions). */
    const safetyPayload = (current: Record<string, Answer>, onlyAnswered = false): Record<string, Answer> => {
        const payload: Record<string, Answer> = {};

        for (const question of safety) {
            const value = current[question.key];

            if (value !== undefined) {
                payload[question.key] = value;
            } else if (!onlyAnswered && !isVisible(question, current)) {
                payload[question.key] = 'not_applicable';
            }
        }

        return payload;
    };

    const send = async (url: string, payload: Record<string, Answer>, withElapsed = false): Promise<{ status: string; report: Report } | null> => {
        setBusy(true);
        const elapsed = withElapsed && startedAt.current !== null ? { elapsed_seconds: secondsSince(startedAt.current) } : {};

        try {
            return await postJson<{ status: string; report: Report }>(url, { journey, answers: payload, ...elapsed });
        } catch (error) {
            const status = error instanceof JsonRequestError ? error.status : 0;

            if (status === 403) {
                window.location.reload();
            }

            setStep(status === 503 ? { name: 'unavailable' } : { name: 'error' });

            return null;
        } finally {
            setBusy(false);
        }
    };

    const screen = async (current: Record<string, Answer>, early: boolean) => {
        const response = await send(screenUrl, safetyPayload(current, early));

        if (response === null) {
            return;
        }

        if (response.status === 'emergency') {
            setStep({ name: 'result', kind: 'emergency', report: response.report });
        } else if (response.status === 'urgent') {
            setStep({ name: 'result', kind: 'urgent', report: response.report });
        } else if (!early) {
            setStep({ name: 'assessment', index: 0 });
        }
    };

    const answerSafety = (question: Question, value: string, index: number) => {
        const next = { ...answers, [question.key]: value };
        setAnswers(next);

        if (question.stop_on_yes && value === 'yes') {
            // Do not wait for the remaining questions: an emergency answer is evaluated at once.
            void screen(next, true);

            return;
        }

        const following = firstVisibleSafety(index + 1, next);

        if (following === -1) {
            void screen(next, false);
        } else {
            setStep({ name: 'safety', index: following });
        }
    };

    const finish = async (current: Record<string, Answer>) => {
        const payload = { ...safetyPayload(current) };

        for (const question of assessment) {
            const value = current[question.key];

            if (value !== undefined && !(Array.isArray(value) && value.length === 0)) {
                payload[question.key] = value;
            }
        }

        const response = await send(evaluateUrl, payload, true);

        if (response !== null) {
            setStep({ name: 'result', kind: 'report', report: response.report });
        }
    };

    const nextAssessment = (index: number, current: Record<string, Answer>) => {
        if (index + 1 >= assessment.length) {
            void finish(current);
        } else {
            setStep({ name: 'assessment', index: index + 1 });
        }
    };

    const toggleMulti = (question: Question, value: string) => {
        const current = Array.isArray(answers[question.key]) ? (answers[question.key] as string[]) : [];
        let next: string[];

        if (current.includes(value)) {
            next = current.filter((item) => item !== value);
        } else if (question.exclusive.includes(value)) {
            next = [value];
        } else {
            next = [...current.filter((item) => !question.exclusive.includes(item)), value];
        }

        setAnswers({ ...answers, [question.key]: next });
    };

    const restart = () => {
        setAnswers({});
        setStep({ name: 'safety', index: 0 });
        setRestarting(false);
        startedAt.current = Date.now();
    };

    const choice = (selected: boolean) =>
        `flex min-h-14 w-full cursor-pointer items-center justify-center rounded-2xl border-2 px-5 text-lg font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-60 ${
            selected ? 'border-brand bg-brand text-brand-ink' : 'border-line-strong bg-surface-raised text-ink hover:border-brand hover:bg-brand-soft'
        }`;

    const visibleSafetyCount = safety.filter((question) => isVisible(question, answers)).length;
    const currentSafety = step.name === 'safety' ? safety[step.index] : undefined;
    const currentAssessment = step.name === 'assessment' ? assessment[step.index] : undefined;
    const stepIndex = step.name === 'safety' || step.name === 'assessment' ? step.index : 0;

    return (
        <PublicLayout>
            <div className="mx-auto max-w-2xl">
                {demo ? (
                    <Alert tone="warning" title={t('assessment.demo_title')} className="mb-8 print:hidden">
                        {t('assessment.demo_body')}
                    </Alert>
                ) : null}

                {step.name === 'unavailable' ? (
                    <section aria-labelledby="unavailable-title" className="space-y-6">
                        <PageIntro id="unavailable-title" title={t('assessment.unavailable_title')} lead={t('assessment.unavailable_body')} icon={FlaskConical} />
                        <EmergencyNotice />
                        {learnUrl !== null ? (
                            <Link href={learnUrl} className={buttonClasses('primary')}>
                                {t('assessment.go_learn')}
                            </Link>
                        ) : null}
                    </section>
                ) : null}

                {step.name === 'error' ? (
                    <section className="space-y-6">
                        <Alert tone="danger" role="alert" title={t('assessment.error_title')}>
                            {t('assessment.error_body')}
                        </Alert>
                        <button type="button" onClick={restart} className={buttonClasses('secondary')}>
                            <RotateCcw aria-hidden="true" className="size-4" />
                            {t('assessment.restart')}
                        </button>
                    </section>
                ) : null}

                {currentSafety !== undefined ? (
                    <section aria-labelledby="question-title">
                        <p className="flex items-center gap-2 text-sm font-semibold text-danger">
                            <ShieldAlert aria-hidden="true" className="size-4" />
                            {t('assessment.safety_eyebrow')}
                        </p>
                        <p className="mt-1 text-sm text-ink-subtle">
                            {t('assessment.progress', { current: Math.min(stepIndex + 1, visibleSafetyCount), total: visibleSafetyCount })}
                        </p>
                        <h1 id="question-title" tabIndex={-1} className="mt-6 text-2xl outline-none text-ink md:text-3xl">
                            {currentSafety.prompt}
                        </h1>
                        <div className="mt-8 grid gap-3">
                            {currentSafety.options.map((option) => (
                                <button
                                    key={option.value}
                                    type="button"
                                    disabled={busy}
                                    onClick={() => answerSafety(currentSafety, option.value, stepIndex)}
                                    className={choice(answers[currentSafety.key] === option.value)}
                                >
                                    {option.label}
                                </button>
                            ))}
                        </div>
                        <p className="mt-6 text-sm text-ink-muted">{t('assessment.safety_note')}</p>
                        {busy ? (
                            <p className="mt-4 flex items-center gap-2 text-sm text-ink-muted">
                                <Spinner className="size-4 text-brand" /> {t('assessment.checking')}
                            </p>
                        ) : null}
                    </section>
                ) : null}

                {currentAssessment !== undefined ? (
                    <AssessmentQuestion
                        question={currentAssessment}
                        index={stepIndex}
                        total={assessment.length}
                        answer={answers[currentAssessment.key]}
                        busy={busy}
                        choiceClass={choice}
                        onSingle={(value) => {
                            const next = { ...answers, [currentAssessment.key]: value };
                            setAnswers(next);
                            nextAssessment(stepIndex, next);
                        }}
                        onToggle={(value) => toggleMulti(currentAssessment, value)}
                        onContinue={() => nextAssessment(stepIndex, answers)}
                        onSkip={() => {
                            const next = Object.fromEntries(Object.entries(answers).filter(([key]) => key !== currentAssessment.key));
                            setAnswers(next);
                            nextAssessment(stepIndex, next);
                        }}
                        onBack={stepIndex > 0 ? () => setStep({ name: 'assessment', index: stepIndex - 1 }) : undefined}
                        onRestart={() => setRestarting(true)}
                    />
                ) : null}

                {step.name === 'result' ? (
                    <ResultView
                        kind={step.kind}
                        report={step.report}
                        emergencyNumber={emergency.number}
                        feedbackUrl={feedbackUrl}
                        learnUrl={learnUrl}
                        doctorPrepUrl={doctorPrepUrl}
                        onRestart={() => setRestarting(true)}
                    />
                ) : null}

                {step.name === 'safety' || step.name === 'assessment' ? (
                    <div className="mt-16 print:hidden">
                        <ChangeJourney journeys={otherJourneys} />
                    </div>
                ) : null}
            </div>

            <ConfirmDialog
                open={restarting}
                title={t('assessment.restart_title')}
                confirmLabel={t('assessment.restart')}
                cancelLabel={t('clear.cancel')}
                onCancel={() => setRestarting(false)}
                onConfirm={restart}
            >
                <p>{t('assessment.restart_body')}</p>
            </ConfirmDialog>
        </PublicLayout>
    );
}

function AssessmentQuestion({
    question,
    index,
    total,
    answer,
    busy,
    choiceClass,
    onSingle,
    onToggle,
    onContinue,
    onSkip,
    onBack,
    onRestart,
}: {
    question: Question;
    index: number;
    total: number;
    answer: Answer | undefined;
    busy: boolean;
    choiceClass: (selected: boolean) => string;
    onSingle: (value: string) => void;
    onToggle: (value: string) => void;
    onContinue: () => void;
    onSkip: () => void;
    onBack?: () => void;
    onRestart: () => void;
}) {
    const t = useT();
    const selected = Array.isArray(answer) ? answer : [];

    return (
        <section aria-labelledby="question-title">
            <div className="flex items-center justify-between gap-3 text-sm text-ink-subtle">
                <span>{t('assessment.progress', { current: index + 1, total })}</span>
                <button type="button" onClick={onRestart} className="inline-flex min-h-10 cursor-pointer items-center gap-1.5 font-medium hover:text-ink">
                    <RotateCcw aria-hidden="true" className="size-4" />
                    {t('assessment.restart')}
                </button>
            </div>
            <div className="mt-3 h-1.5 overflow-hidden rounded-full bg-surface-sunken" aria-hidden="true">
                <div className="h-full rounded-full bg-brand transition-all" style={{ inlineSize: `${((index + 1) / total) * 100}%` }} />
            </div>
            <h1 id="question-title" tabIndex={-1} className="mt-8 text-2xl outline-none text-ink md:text-3xl">
                {question.prompt}
            </h1>
            {question.type === 'multi' ? <p className="mt-2 text-sm text-ink-muted">{t('assessment.multi_hint')}</p> : null}
            {index === 0 ? (
                <Alert tone="info" title={t('assessment.respect_title')} className="mt-6">
                    {t('assessment.respect_body')}
                </Alert>
            ) : null}

            <div className={`mt-8 grid gap-3 ${question.options.length > 4 ? 'sm:grid-cols-2' : ''}`}>
                {question.options.map((option) =>
                    question.type === 'multi' ? (
                        <button
                            key={option.value}
                            type="button"
                            aria-pressed={selected.includes(option.value)}
                            disabled={busy}
                            onClick={() => onToggle(option.value)}
                            className={choiceClass(selected.includes(option.value))}
                        >
                            {option.label}
                        </button>
                    ) : (
                        <button key={option.value} type="button" disabled={busy} onClick={() => onSingle(option.value)} className={choiceClass(answer === option.value)}>
                            {option.label}
                        </button>
                    ),
                )}
            </div>

            <div className="mt-8 flex flex-wrap items-center justify-between gap-3">
                {onBack !== undefined ? (
                    <button type="button" onClick={onBack} className={buttonClasses('ghost')}>
                        <ArrowLeft aria-hidden="true" className="size-4 rtl:-scale-x-100" />
                        {t('assessment.back')}
                    </button>
                ) : (
                    <span />
                )}
                <div className="flex flex-wrap gap-2">
                    <button type="button" disabled={busy} onClick={onSkip} className={buttonClasses('secondary')}>
                        <SkipForward aria-hidden="true" className="size-4 rtl:-scale-x-100" />
                        {t('assessment.skip')}
                    </button>
                    {question.type === 'multi' ? (
                        <button type="button" disabled={busy || selected.length === 0} onClick={onContinue} className={buttonClasses('primary')}>
                            {t('assessment.continue')}
                            <ArrowRight aria-hidden="true" className="size-4 rtl:-scale-x-100" />
                        </button>
                    ) : null}
                </div>
            </div>
            {busy ? (
                <p className="mt-4 flex items-center gap-2 text-sm text-ink-muted">
                    <Spinner className="size-4 text-brand" /> {t('assessment.checking')}
                </p>
            ) : null}
        </section>
    );
}

function ResultView({
    kind,
    report,
    emergencyNumber,
    feedbackUrl,
    learnUrl,
    doctorPrepUrl,
    onRestart,
}: {
    kind: 'emergency' | 'urgent' | 'report';
    report: Report;
    emergencyNumber: string | null;
    feedbackUrl: string;
    learnUrl: string | null;
    doctorPrepUrl: string | null;
    onRestart: () => void;
}) {
    const t = useT();
    const priority = report.priority ?? 'P4';
    const signal = (signalName: FeedbackSignal, value: string) => sendSignal(feedbackUrl, signalName, value);

    const download = () => {
        signal('next_step_taken', 'download');
        const lines = [
            t('report.download_title'),
            '',
            `${report.label ?? ''}: ${report.headline ?? ''}`,
            report.nextStep ?? '',
            ...(report.carePathway !== null ? ['', `${t('report.pathway_heading')}: ${report.carePathway.title}`, report.carePathway.body] : []),
            ...(report.riskDomains.length > 0 ? ['', t('report.domains_heading'), ...report.riskDomains.map((domain) => `- ${domain.label}`)] : []),
            '',
            ...report.guidance.map((text) => `- ${text}`),
            ...(report.doctorTopics.length > 0 ? ['', t('report.doctor_topics'), ...report.doctorTopics.map((topic) => `- ${topic}`)] : []),
            '',
            t('report.screening_body'),
            ...(report.demo ? [t('assessment.demo_body')] : []),
        ];
        const blob = new Blob([lines.join('\n')], { type: 'text/plain;charset=utf-8' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `rafiq-notes-${new Date().toISOString().slice(0, 10)}.txt`;
        link.click();
        URL.revokeObjectURL(link.href);
    };

    return (
        <section aria-labelledby="result-title" className="space-y-8">
            <div className={`rounded-[var(--radius-panel)] border-s-8 p-6 sm:p-8 ${PRIORITY_STYLES[priority] ?? PRIORITY_STYLES.P4}`} role={kind === 'emergency' ? 'alert' : undefined}>
                <p className="text-sm font-semibold">{report.label}</p>
                <h1 id="result-title" tabIndex={-1} className="mt-2 text-2xl outline-none text-ink md:text-3xl">
                    {report.headline}
                </h1>
                <p className="mt-3 text-lg text-ink">{report.nextStep}</p>
                {kind === 'emergency' ? (
                    emergencyNumber !== null ? (
                        <a href={`tel:${emergencyNumber}`} className={buttonClasses('destructive', 'lg', 'mt-6')}>
                            <Phone aria-hidden="true" className="size-5" />
                            <bdi dir="ltr">{emergencyNumber}</bdi>
                        </a>
                    ) : (
                        <p className="mt-6 text-lg font-semibold text-danger">{t('emergency.call_local')}</p>
                    )
                ) : null}
            </div>

            {report.showEmergencyCriteria ? (
                <Alert tone="danger" title={t('report.emergency_criteria_title')}>
                    {t('report.emergency_criteria_body')}
                </Alert>
            ) : null}

            {kind !== 'emergency' ? (
                <Alert tone="info" title={t('report.screening_title')}>
                    {t('report.screening_body')}
                </Alert>
            ) : null}

            {report.carePathway !== null && kind !== 'emergency' ? (
                <div className="rounded-[var(--radius-panel)] border border-line bg-surface-raised p-6 shadow-card">
                    <p className="flex items-center gap-2 text-sm font-semibold text-brand">
                        <Compass aria-hidden="true" className="size-4" />
                        {t('report.pathway_heading')}
                    </p>
                    <h2 className="mt-2 text-xl text-ink">{report.carePathway.title}</h2>
                    <p className="mt-2 text-ink-muted">{report.carePathway.body}</p>
                    <ul className="mt-4 flex flex-wrap gap-2">
                        {report.carePathway.access.map((access) => (
                            <li key={access.key} className="inline-flex items-center gap-1.5 rounded-full bg-brand-soft px-3 py-1.5 text-sm font-medium text-brand-strong">
                                <CheckCircle2 aria-hidden="true" className="size-4" />
                                {access.label}
                            </li>
                        ))}
                    </ul>
                    {report.otherPathways.length > 0 ? (
                        <p className="mt-4 text-sm text-ink-muted">
                            {t('report.pathway_also')} {report.otherPathways.map((pathway) => pathway.title).join(t('report.list_separator'))}
                        </p>
                    ) : null}
                </div>
            ) : null}

            {report.riskDomains.length > 0 ? (
                <div>
                    <h2 className="text-xl text-ink">{t('report.domains_heading')}</h2>
                    <p className="mt-1 text-sm text-ink-muted">{t('report.domains_hint')}</p>
                    <ul className="mt-4 grid gap-3 sm:grid-cols-2">
                        {report.riskDomains.map((domain) => (
                            <li key={domain.key} className="rounded-2xl border border-line bg-surface-raised p-4">
                                <p className="font-semibold text-ink">{domain.label}</p>
                                <p className="mt-1 text-sm text-ink-muted">{domain.body}</p>
                            </li>
                        ))}
                    </ul>
                </div>
            ) : null}

            {report.guidance.length > 0 ? (
                <div>
                    <h2 className="text-xl text-ink">{t('report.guidance')}</h2>
                    <ul className="mt-4 space-y-3">
                        {report.guidance.map((text) => (
                            <li key={text} className="rounded-2xl bg-surface-raised p-4 text-ink shadow-card">
                                {text}
                            </li>
                        ))}
                    </ul>
                </div>
            ) : null}

            {report.notices.length > 0 ? (
                <ul className="space-y-2">
                    {report.notices.map((notice) => (
                        <li key={notice} className="flex items-start gap-2 text-sm text-ink-muted">
                            <AlertTriangle aria-hidden="true" className="mt-0.5 size-4 shrink-0 text-warning" />
                            {notice}
                        </li>
                    ))}
                </ul>
            ) : null}

            {report.doctorTopics.length > 0 ? (
                <div>
                    <h2 className="text-xl text-ink">{t('report.doctor_topics')}</h2>
                    <ul className="mt-4 list-disc space-y-2 ps-6 text-ink">
                        {report.doctorTopics.map((topic) => (
                            <li key={topic}>{topic}</li>
                        ))}
                    </ul>
                </div>
            ) : null}

            {report.articles.length > 0 ? (
                <div className="print:hidden">
                    <h2 className="text-xl text-ink">{t('report.articles')}</h2>
                    <ul className="mt-4 space-y-2">
                        {report.articles.map((article) => (
                            <li key={article.url}>
                                <Link href={article.url} onClick={() => signal('next_step_taken', 'article')} className="font-medium text-brand underline-offset-4 hover:underline">
                                    {article.title}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </div>
            ) : null}

            <div className="rounded-2xl border-s-4 border-brand bg-surface-sunken p-5">
                <h2 className="flex items-center gap-2 text-base font-semibold text-ink">
                    <Stethoscope aria-hidden="true" className="size-4 text-brand" />
                    {t('report.oversight_title')}
                </h2>
                <ul className="mt-3 list-disc space-y-1.5 ps-5 text-sm text-ink-muted">
                    <li>{t('report.oversight_rules')}</li>
                    <li>{report.demo ? t('report.oversight_review_pending') : t('report.oversight_review_done')}</li>
                    <li>{t('report.oversight_clinician')}</li>
                    <li>{t('report.oversight_no_ai')}</li>
                    <li>{t('report.limits')}</li>
                </ul>
            </div>
            {report.demo ? <p className="text-sm font-medium text-warning">{t('assessment.demo_body')}</p> : null}

            <div className="flex flex-wrap gap-3 print:hidden">
                {kind === 'report' ? (
                    <>
                        <button
                            type="button"
                            onClick={() => {
                                signal('next_step_taken', 'print');
                                window.print();
                            }}
                            className={buttonClasses('secondary')}
                        >
                            <Printer aria-hidden="true" className="size-4" />
                            {t('report.print')}
                        </button>
                        <button type="button" onClick={download} className={buttonClasses('secondary')}>
                            <Download aria-hidden="true" className="size-4" />
                            {t('report.download')}
                        </button>
                    </>
                ) : null}
                {doctorPrepUrl !== null && kind === 'report' ? (
                    <Link
                        href={doctorPrepUrl}
                        onClick={() => {
                            signal('next_step_taken', 'doctor_prep');
                            setDoctorPrepHandoff(report.doctorTopics);
                        }}
                        className={buttonClasses('secondary')}
                    >
                        {t('report.doctor_prep')}
                    </Link>
                ) : null}
                {learnUrl !== null ? (
                    <Link href={learnUrl} className={buttonClasses('ghost')}>
                        {t('assessment.go_learn')}
                    </Link>
                ) : null}
                <button type="button" onClick={onRestart} className={buttonClasses('ghost')}>
                    <RotateCcw aria-hidden="true" className="size-4" />
                    {t('assessment.restart')}
                </button>
            </div>

            {kind === 'report' ? <ReportFeedback onSignal={signal} /> : null}
        </section>
    );
}

/**
 * Anonymous trust and clarity check (KPI "user trust"): one tap each, sent as counters only.
 */
function ReportFeedback({ onSignal }: { onSignal: (signal: FeedbackSignal, value: string) => void }) {
    const t = useT();
    const [trust, setTrust] = useState<string | null>(null);
    const [clear, setClear] = useState<string | null>(null);

    const pill = (selected: boolean) =>
        `inline-flex min-h-11 min-w-11 cursor-pointer items-center justify-center rounded-xl border px-3 text-sm font-semibold transition-colors disabled:cursor-default ${
            selected ? 'border-brand bg-brand text-brand-ink' : 'border-line-strong bg-surface-raised text-ink hover:border-brand'
        }`;

    return (
        <aside aria-labelledby="feedback-title" className="rounded-2xl border border-line bg-surface-raised p-5 print:hidden">
            <h2 id="feedback-title" className="text-base font-semibold text-ink">
                {t('report.feedback_title')}
            </h2>
            <p className="mt-1 text-sm text-ink-muted">{t('report.feedback_hint')}</p>

            <fieldset className="mt-4">
                <legend className="text-sm font-medium text-ink">{t('report.feedback_trust')}</legend>
                <div className="mt-2 flex flex-wrap items-center gap-2" dir="ltr">
                    {['1', '2', '3', '4', '5'].map((value) => (
                        <button
                            key={value}
                            type="button"
                            disabled={trust !== null}
                            aria-pressed={trust === value}
                            onClick={() => {
                                setTrust(value);
                                onSignal('report_trust', value);
                            }}
                            className={pill(trust === value)}
                        >
                            {value}
                        </button>
                    ))}
                </div>
                <p className="mt-1 flex justify-between text-xs text-ink-subtle">
                    <span>{t('report.feedback_trust_low')}</span>
                    <span>{t('report.feedback_trust_high')}</span>
                </p>
            </fieldset>

            <fieldset className="mt-4">
                <legend className="text-sm font-medium text-ink">{t('report.feedback_clear')}</legend>
                <div className="mt-2 flex gap-2">
                    {(['yes', 'no'] as const).map((value) => (
                        <button
                            key={value}
                            type="button"
                            disabled={clear !== null}
                            aria-pressed={clear === value}
                            onClick={() => {
                                setClear(value);
                                onSignal('report_clear', value);
                            }}
                            className={pill(clear === value)}
                        >
                            {t(`assessment.option.${value}`)}
                        </button>
                    ))}
                </div>
            </fieldset>

            {trust !== null || clear !== null ? (
                <p role="status" className="mt-4 text-sm font-medium text-success">
                    {t('report.feedback_thanks')}
                </p>
            ) : null}
        </aside>
    );
}
