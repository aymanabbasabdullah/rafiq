import { ExternalLink, Lock } from 'lucide-react';
import { CheckList } from '@/components/public/CheckList';
import { ClearEverythingButton } from '@/components/public/ClearEverythingButton';
import { PageIntro } from '@/components/public/PageIntro';
import { Alert } from '@/components/ui/Alert';
import { PublicLayout } from '@/layouts/PublicLayout';
import { useT } from '@/lib/i18n';
import type { LegalPageKey } from '@/types';

type Source = { id: string; title: string; publisher: string; url: string; date: string };

type LegalProps = {
    page: LegalPageKey;
    sources: Source[];
    updatedAt: string;
};

const PRIVACY_POINTS = ['point1', 'point2', 'point3', 'point4', 'point5'];
const PRIVACY_PROCESSING = ['process1', 'process2', 'process3', 'process4', 'process5'];
const PRIVACY_SECTIONS = ['never', 'cookies', 'retention', 'rights', 'controller'];
const TERMS_PARAGRAPHS = ['p1', 'p2', 'p3', 'p4', 'p5'];
const CAN = ['can1', 'can2', 'can3', 'can4'];
const CANNOT = ['cannot1', 'cannot2', 'cannot3', 'cannot4', 'cannot5', 'cannot6'];

/**
 * Privacy, terms, limits and sources pages (FR-024, PRIV-019). Draft wording pending legal review (OQ-002, OQ-031).
 */
export default function Legal({ page, sources, updatedAt }: LegalProps) {
    const t = useT();
    const isPolicy = page === 'privacy' || page === 'terms';

    return (
        <PublicLayout>
            <article aria-labelledby="legal-title" className="mx-auto max-w-3xl">
                <PageIntro id="legal-title" eyebrow={t('footer.policies')} title={t(`legal.${page}.title`)} />
                {isPolicy ? (
                    <>
                        <p className="mt-4 text-sm text-ink-muted">
                            {t('legal.updated')}{' '}
                            <bdi dir="ltr">
                                <time dateTime={updatedAt}>{updatedAt}</time>
                            </bdi>
                        </p>
                        <Alert tone="warning" className="mt-6">
                            {t('legal.draft_notice')}
                        </Alert>
                    </>
                ) : null}

                <div className="mt-10 space-y-8 text-lg leading-relaxed text-ink [&_h2]:font-display [&_h2]:text-2xl [&_h2]:font-semibold">
                    {page === 'privacy' ? <PrivacySummary /> : null}
                    {page === 'terms' ? <TermsSummary /> : null}
                    {page === 'limits' ? <LimitsSummary /> : null}
                    {page === 'sources' ? <SourcesList sources={sources} /> : null}
                </div>
            </article>
        </PublicLayout>
    );
}

function PrivacySummary() {
    const t = useT();

    return (
        <>
            <p>{t('legal.privacy.intro')}</p>
            <ul className="space-y-3">
                {PRIVACY_POINTS.map((point) => (
                    <li key={point} className="flex items-start gap-3">
                        <Lock aria-hidden="true" className="mt-1 size-4 shrink-0 text-brand" />
                        <span>{t(`landing.privacy.${point}`)}</span>
                    </li>
                ))}
            </ul>
            <p className="rounded-2xl bg-surface-raised p-5 text-base text-ink-muted">{t('landing.privacy.honesty')}</p>

            <section>
                <h2 className="mb-4">{t('legal.privacy.process_title')}</h2>
                <ul className="list-disc space-y-2 ps-6">
                    {PRIVACY_PROCESSING.map((item) => (
                        <li key={item}>{t(`legal.privacy.${item}`)}</li>
                    ))}
                </ul>
            </section>

            {PRIVACY_SECTIONS.map((section) => (
                <section key={section}>
                    <h2 className="mb-3">{t(`legal.privacy.${section}_title`)}</h2>
                    <p>{t(`legal.privacy.${section}_body`)}</p>
                </section>
            ))}

            <ClearEverythingButton />
        </>
    );
}

function TermsSummary() {
    const t = useT();

    return (
        <ol className="list-decimal space-y-3 ps-6">
            {TERMS_PARAGRAPHS.map((paragraph) => (
                <li key={paragraph}>{t(`legal.terms.${paragraph}`)}</li>
            ))}
        </ol>
    );
}

function LimitsSummary() {
    const t = useT();

    return (
        <>
            <section>
                <h2 className="mb-4">{t('landing.limits.can_title')}</h2>
                <CheckList variant="yes" items={CAN.map((key) => t(`landing.limits.${key}`))} />
            </section>
            <section>
                <h2 className="mb-4">{t('landing.limits.cannot_title')}</h2>
                <CheckList variant="no" items={CANNOT.map((key) => t(`landing.limits.${key}`))} />
            </section>
            <p className="font-medium">{t('landing.faq.a4')}</p>
        </>
    );
}

function SourcesList({ sources }: { sources: Source[] }) {
    const t = useT();

    return (
        <>
            <p>{t('legal.sources.intro')}</p>
            <ul className="space-y-4">
                {sources.map((source) => (
                    <li key={source.id} className="rounded-[var(--radius-card)] bg-surface-raised p-5 shadow-card">
                        <a
                            href={source.url}
                            target="_blank"
                            rel="noopener noreferrer"
                            dir="ltr"
                            lang="en"
                            className="inline-flex items-start gap-2 font-semibold text-brand underline-offset-4 hover:underline"
                        >
                            <span>{source.title}</span>
                            <ExternalLink aria-hidden="true" className="mt-1 size-4 shrink-0" />
                        </a>
                        <span className="sr-only">{t('legal.sources.opens_new_tab')}</span>
                        <p className="mt-1 text-sm text-ink-muted" dir="ltr" lang="en">
                            {source.publisher}
                        </p>
                        <p className="mt-1 text-sm text-ink-muted">
                            {t('legal.sources.published')}{' '}
                            <bdi dir="ltr">
                                <time dateTime={source.date}>{source.date}</time>
                            </bdi>
                        </p>
                    </li>
                ))}
            </ul>
            <Alert tone="warning">{t('legal.sources.not_approved')}</Alert>
        </>
    );
}
