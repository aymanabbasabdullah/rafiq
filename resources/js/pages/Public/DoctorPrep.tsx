import { ArrowDown, ArrowUp, Check, Download, FlaskConical, Lock, Plus, Printer, Trash2, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { ChangeJourney } from '@/components/public/ChangeJourney';
import { PageIntro } from '@/components/public/PageIntro';
import { RedFlagReminder } from '@/components/public/RedFlagReminder';
import { Alert } from '@/components/ui/Alert';
import { buttonClasses } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { inputClasses } from '@/components/ui/Field';
import { PublicLayout } from '@/layouts/PublicLayout';
import { clearDoctorPrepHandoff, peekDoctorPrepHandoff } from '@/lib/doctorPrepHandoff';
import { useT } from '@/lib/i18n';
import type { JourneyKey } from '@/types';

type Topic = { key: string; label: string; questions: { id: string; text: string; help: string | null }[] };

type DoctorPrepProps = {
    topics: Topic[];
    bring: { id: string; text: string }[];
    demo: boolean;
    concernUrl: string | null;
    glossaryUrl: string | null;
    otherJourneys: { key: JourneyKey; url: string | null }[];
};

type Item = { uid: string; text: string; source: 'bank' | 'report' | 'mine'; bankId?: string };

const STEPS = ['topics', 'questions', 'mine', 'bring', 'preview'] as const;
type Step = (typeof STEPS)[number];

const MAX_OWN = 20;
const MAX_CHARS = 300;
const TEMPLATES = ['since_when', 'what_causes', 'which_tests', 'what_options'];

let nextUid = 1;
const uid = () => `item-${nextUid++}`;

/**
 * J4 doctor visit preparation (Module-10). Everything the visitor picks or writes stays in this page's
 * memory: there is no endpoint that receives it (BR-10-1), and closing the tab erases it.
 */
export default function DoctorPrep({ topics, bring, demo, concernUrl, otherJourneys }: DoctorPrepProps) {
    const t = useT();
    const [step, setStep] = useState<Step>('topics');
    const [selectedTopics, setSelectedTopics] = useState<string[]>([]);
    // Topics handed over from the assessment report start the list (M10-FR-06).
    const [items, setItems] = useState<Item[]>(() => peekDoctorPrepHandoff().map((text): Item => ({ uid: `report-${text}`, text, source: 'report' })));
    const [checked, setChecked] = useState<string[]>([]);

    useEffect(() => clearDoctorPrepHandoff(), []);

    useEffect(() => {
        window.scrollTo({ top: 0 });
    }, [step]);

    const ownCount = items.filter((item) => item.source === 'mine').length;
    const isChosen = (bankId: string) => items.some((item) => item.bankId === bankId);

    const toggleBank = (question: Topic['questions'][number]) => {
        setItems((current) =>
            current.some((item) => item.bankId === question.id)
                ? current.filter((item) => item.bankId !== question.id)
                : [...current, { uid: uid(), text: question.text, source: 'bank', bankId: question.id }],
        );
    };

    const addOwn = (text = '') => {
        if (ownCount < MAX_OWN) {
            setItems((current) => [...current, { uid: uid(), text, source: 'mine' }]);
        }
    };

    const update = (id: string, text: string) => setItems((current) => current.map((item) => (item.uid === id ? { ...item, text: text.slice(0, MAX_CHARS) } : item)));
    const remove = (id: string) => setItems((current) => current.filter((item) => item.uid !== id));

    const move = (index: number, offset: number) =>
        setItems((current) => {
            const target = index + offset;

            if (target < 0 || target >= current.length) {
                return current;
            }

            const next = [...current];
            const moved = next[index];
            const other = next[target];

            if (moved === undefined || other === undefined) {
                return current;
            }

            next[index] = other;
            next[target] = moved;

            return next;
        });

    const finalItems = items.filter((item) => item.text.trim() !== '');
    const finalBring = bring.filter((entry) => checked.includes(entry.id));

    const download = () => {
        const lines = [
            t('doctor_prep.print_title'),
            new Date().toLocaleDateString(),
            '',
            t('doctor_prep.preview_questions'),
            ...finalItems.map((item, index) => `${index + 1}. ${item.text.trim()}`),
            '',
            t('doctor_prep.preview_bring'),
            ...finalBring.map((entry) => `[ ] ${entry.text}`),
            '',
            t('footer.not_diagnosis'),
        ];
        const blob = new Blob([lines.join('\n')], { type: 'text/plain;charset=utf-8' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `rafiq-doctor-visit-${new Date().toISOString().slice(0, 10)}.txt`;
        link.click();
        URL.revokeObjectURL(link.href);
    };

    const stepButton = (value: Step, index: number) => (
        <li key={value}>
            <button
                type="button"
                onClick={() => setStep(value)}
                aria-current={step === value ? 'step' : undefined}
                className={`inline-flex min-h-10 cursor-pointer items-center gap-2 rounded-full px-3.5 text-sm font-medium transition-colors ${
                    step === value ? 'bg-brand text-brand-ink' : 'bg-surface-raised text-ink-muted hover:text-ink'
                }`}
            >
                <span className="font-display">
                    <bdi>{index + 1}</bdi>
                </span>
                {t(`doctor_prep.step.${value}`)}
            </button>
        </li>
    );

    return (
        <PublicLayout>
            <div className="mx-auto max-w-3xl space-y-8">
                <div className="print:hidden">
                    <PageIntro id="doctor-prep-title" eyebrow={t('journey.doctor_prep.title')} title={t('doctor_prep.title')} lead={t('doctor_prep.lead')} />
                </div>

                <Alert tone="info" title={t('journey.doctor_prep.local_notice_title')} className="print:hidden">
                    {t('journey.doctor_prep.local_notice_body')}
                </Alert>

                {demo ? (
                    <Alert tone="warning" title={t('doctor_prep.demo_title')} className="print:hidden">
                        {t('doctor_prep.demo_body')}
                    </Alert>
                ) : null}

                <nav aria-label={t('doctor_prep.steps_label')} className="print:hidden">
                    <ol className="flex flex-wrap gap-2">{STEPS.map(stepButton)}</ol>
                </nav>

                {step === 'topics' ? (
                    <section aria-labelledby="topics-heading" className="print:hidden">
                        <h2 id="topics-heading" className="text-2xl text-ink">
                            {t('doctor_prep.topics_heading')}
                        </h2>
                        <p className="mt-2 text-ink-muted">{t('doctor_prep.topics_hint')}</p>
                        {topics.length === 0 ? (
                            <div className="mt-6">
                                <EmptyState icon={FlaskConical} title={t('doctor_prep.empty_bank')} />
                            </div>
                        ) : (
                            <div className="mt-6 grid gap-3 sm:grid-cols-2">
                                {topics.map((topic) => {
                                    const active = selectedTopics.includes(topic.key);

                                    return (
                                        <button
                                            key={topic.key}
                                            type="button"
                                            aria-pressed={active}
                                            onClick={() => setSelectedTopics((current) => (active ? current.filter((key) => key !== topic.key) : [...current, topic.key]))}
                                            className={`flex min-h-14 cursor-pointer items-center justify-between gap-3 rounded-2xl border-2 px-5 text-start font-semibold transition-colors ${
                                                active ? 'border-brand bg-brand-soft text-brand-strong' : 'border-line-strong bg-surface-raised text-ink hover:border-brand'
                                            }`}
                                        >
                                            {topic.label}
                                            {active ? <Check aria-hidden="true" className="size-5 shrink-0" /> : null}
                                        </button>
                                    );
                                })}
                            </div>
                        )}
                        <StepNav onNext={() => setStep('questions')} />
                    </section>
                ) : null}

                {step === 'questions' ? (
                    <section aria-labelledby="questions-heading" className="print:hidden">
                        <h2 id="questions-heading" className="text-2xl text-ink">
                            {t('doctor_prep.questions_heading')}
                        </h2>
                        <p className="mt-2 text-ink-muted">{t('doctor_prep.questions_hint')}</p>
                        {selectedTopics.length === 0 ? (
                            <p className="mt-6 rounded-2xl bg-surface-sunken p-4 text-ink-muted">{t('doctor_prep.no_topics')}</p>
                        ) : (
                            <div className="mt-6 space-y-8">
                                {topics
                                    .filter((topic) => selectedTopics.includes(topic.key))
                                    .map((topic) => (
                                        <div key={topic.key}>
                                            <h3 className="font-display text-lg font-semibold text-ink">{topic.label}</h3>
                                            <ul className="mt-3 space-y-2">
                                                {topic.questions.map((question) => (
                                                    <li key={question.id}>
                                                        <label className="flex cursor-pointer items-start gap-3 rounded-2xl border border-line bg-surface-raised p-4 has-[:checked]:border-brand has-[:checked]:bg-brand-mist">
                                                            <input type="checkbox" checked={isChosen(question.id)} onChange={() => toggleBank(question)} className="mt-1 size-4 shrink-0 accent-brand" />
                                                            <span className="text-ink">{question.text}</span>
                                                        </label>
                                                    </li>
                                                ))}
                                            </ul>
                                        </div>
                                    ))}
                            </div>
                        )}
                        <StepNav onBack={() => setStep('topics')} onNext={() => setStep('mine')} />
                    </section>
                ) : null}

                {step === 'mine' ? (
                    <section aria-labelledby="mine-heading" className="print:hidden">
                        <h2 id="mine-heading" className="flex items-center gap-2 text-2xl text-ink">
                            {t('doctor_prep.mine_heading')}
                        </h2>
                        <p className="mt-2 flex items-center gap-2 text-sm font-medium text-brand-strong">
                            <Lock aria-hidden="true" className="size-4" />
                            {t('doctor_prep.stays_on_device')}
                        </p>

                        <div className="mt-6">
                            <p className="text-sm font-semibold text-ink">{t('doctor_prep.templates_label')}</p>
                            <div className="mt-2 flex flex-wrap gap-2">
                                {TEMPLATES.map((template) => (
                                    <button
                                        key={template}
                                        type="button"
                                        disabled={ownCount >= MAX_OWN}
                                        onClick={() => addOwn(t(`doctor_prep.template.${template}`))}
                                        className="cursor-pointer rounded-full border border-line-strong bg-surface-raised px-3.5 py-2 text-sm text-ink hover:border-brand disabled:opacity-50"
                                    >
                                        {t(`doctor_prep.template.${template}`)}
                                    </button>
                                ))}
                            </div>
                        </div>

                        <ul className="mt-6 space-y-3">
                            {items
                                .filter((item) => item.source === 'mine')
                                .map((item, index) => (
                                    <li key={item.uid} className="flex items-start gap-2">
                                        <label htmlFor={item.uid} className="sr-only">
                                            {t('doctor_prep.own_label', { n: index + 1 })}
                                        </label>
                                        <textarea
                                            id={item.uid}
                                            rows={2}
                                            maxLength={MAX_CHARS}
                                            value={item.text}
                                            dir="auto"
                                            onChange={(event) => update(item.uid, event.target.value)}
                                            className={`${inputClasses} rounded-2xl`}
                                        />
                                        <button
                                            type="button"
                                            onClick={() => remove(item.uid)}
                                            aria-label={t('doctor_prep.remove')}
                                            className="inline-flex size-10 shrink-0 cursor-pointer items-center justify-center rounded-full text-ink-muted hover:bg-danger-soft hover:text-danger"
                                        >
                                            <Trash2 aria-hidden="true" className="size-4" />
                                        </button>
                                    </li>
                                ))}
                        </ul>
                        <button type="button" disabled={ownCount >= MAX_OWN} onClick={() => addOwn()} className={buttonClasses('secondary', 'md', 'mt-4')}>
                            <Plus aria-hidden="true" className="size-4" />
                            {t('doctor_prep.add_own', { count: ownCount, max: MAX_OWN })}
                        </button>
                        <StepNav onBack={() => setStep('questions')} onNext={() => setStep('bring')} />
                    </section>
                ) : null}

                {step === 'bring' ? (
                    <section aria-labelledby="bring-heading" className="print:hidden">
                        <h2 id="bring-heading" className="text-2xl text-ink">
                            {t('doctor_prep.bring_heading')}
                        </h2>
                        <ul className="mt-6 space-y-2">
                            {bring.map((entry) => (
                                <li key={entry.id}>
                                    <label className="flex cursor-pointer items-start gap-3 rounded-2xl border border-line bg-surface-raised p-4 has-[:checked]:border-brand has-[:checked]:bg-brand-mist">
                                        <input
                                            type="checkbox"
                                            checked={checked.includes(entry.id)}
                                            onChange={() => setChecked((current) => (current.includes(entry.id) ? current.filter((id) => id !== entry.id) : [...current, entry.id]))}
                                            className="mt-1 size-4 shrink-0 accent-brand"
                                        />
                                        <span className="text-ink">{entry.text}</span>
                                    </label>
                                </li>
                            ))}
                        </ul>
                        <StepNav onBack={() => setStep('mine')} onNext={() => setStep('preview')} />
                    </section>
                ) : null}

                {step === 'preview' ? (
                    <section aria-labelledby="preview-heading">
                        <div className="rounded-[var(--radius-panel)] bg-surface-raised p-6 shadow-card sm:p-8 print:p-0 print:shadow-none">
                            <h2 id="preview-heading" className="text-2xl text-ink">
                                {t('doctor_prep.print_title')}
                            </h2>
                            <p className="mt-1 text-sm text-ink-subtle">
                                <bdi>{new Date().toLocaleDateString()}</bdi>
                            </p>

                            <h3 className="mt-6 font-display text-lg font-semibold text-ink">{t('doctor_prep.preview_questions')}</h3>
                            {finalItems.length === 0 ? (
                                <p className="mt-2 text-ink-muted">{t('doctor_prep.preview_empty')}</p>
                            ) : (
                                <ol className="mt-3 space-y-4">
                                    {finalItems.map((item, index) => (
                                        <li key={item.uid} className="flex items-start gap-3">
                                            <span className="font-display font-semibold text-brand">
                                                <bdi>{index + 1}</bdi>
                                            </span>
                                            <div className="min-w-0 flex-1">
                                                <p dir="auto" className="text-ink">
                                                    {item.text}
                                                </p>
                                                <div aria-hidden="true" className="mt-3 hidden h-10 border-b border-dashed border-line-strong print:block" />
                                            </div>
                                            <div className="flex shrink-0 gap-1 print:hidden">
                                                <button type="button" onClick={() => move(index, -1)} disabled={index === 0} aria-label={t('doctor_prep.move_up')} className="inline-flex size-9 cursor-pointer items-center justify-center rounded-full text-ink-muted hover:bg-surface-sunken disabled:opacity-30">
                                                    <ArrowUp aria-hidden="true" className="size-4" />
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => move(index, 1)}
                                                    disabled={index === finalItems.length - 1}
                                                    aria-label={t('doctor_prep.move_down')}
                                                    className="inline-flex size-9 cursor-pointer items-center justify-center rounded-full text-ink-muted hover:bg-surface-sunken disabled:opacity-30"
                                                >
                                                    <ArrowDown aria-hidden="true" className="size-4" />
                                                </button>
                                                <button type="button" onClick={() => remove(item.uid)} aria-label={t('doctor_prep.remove')} className="inline-flex size-9 cursor-pointer items-center justify-center rounded-full text-ink-muted hover:bg-danger-soft hover:text-danger">
                                                    <X aria-hidden="true" className="size-4" />
                                                </button>
                                            </div>
                                        </li>
                                    ))}
                                </ol>
                            )}

                            <h3 className="mt-8 font-display text-lg font-semibold text-ink">{t('doctor_prep.preview_bring')}</h3>
                            {finalBring.length === 0 ? (
                                <p className="mt-2 text-ink-muted">{t('doctor_prep.preview_bring_empty')}</p>
                            ) : (
                                <ul className="mt-3 space-y-2">
                                    {finalBring.map((entry) => (
                                        <li key={entry.id} className="flex items-start gap-3 text-ink">
                                            <span aria-hidden="true" className="mt-1 inline-block size-4 shrink-0 rounded border border-line-strong" />
                                            {entry.text}
                                        </li>
                                    ))}
                                </ul>
                            )}

                            <p className="mt-8 border-t border-line pt-4 text-sm text-ink-subtle">{t('footer.not_diagnosis')}</p>
                        </div>

                        <div className="mt-6 flex flex-wrap gap-3 print:hidden">
                            <button type="button" onClick={() => window.print()} className={buttonClasses('primary')}>
                                <Printer aria-hidden="true" className="size-4" />
                                {t('report.print')}
                            </button>
                            <button type="button" onClick={download} className={buttonClasses('secondary')}>
                                <Download aria-hidden="true" className="size-4" />
                                {t('report.download')}
                            </button>
                            <button type="button" onClick={() => setStep('topics')} className={buttonClasses('ghost')}>
                                {t('doctor_prep.edit')}
                            </button>
                        </div>
                    </section>
                ) : null}

                <div className="print:hidden">
                    <RedFlagReminder concernUrl={concernUrl} />
                </div>
                <div className="print:hidden">
                    <ChangeJourney journeys={otherJourneys} />
                </div>
            </div>
        </PublicLayout>
    );
}

function StepNav({ onBack, onNext }: { onBack?: () => void; onNext: () => void }) {
    const t = useT();

    return (
        <div className="mt-8 flex justify-between gap-3">
            {onBack !== undefined ? (
                <button type="button" onClick={onBack} className={buttonClasses('ghost')}>
                    {t('assessment.back')}
                </button>
            ) : (
                <span />
            )}
            <button type="button" onClick={onNext} className={buttonClasses('primary')}>
                {t('assessment.continue')}
            </button>
        </div>
    );
}
