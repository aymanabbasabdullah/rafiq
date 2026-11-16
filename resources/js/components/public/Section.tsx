import type { ReactNode } from 'react';

/**
 * Small uppercase-free label above a heading. Arabic has no case, so weight and colour carry it.
 */
export function Eyebrow({ children, tone = 'brand' }: { children: ReactNode; tone?: 'brand' | 'mint' }) {
    return (
        <p
            className={`inline-flex items-center gap-2 text-sm font-semibold ${tone === 'brand' ? 'text-brand' : 'text-mint'}`}
        >
            <span aria-hidden="true" className="h-px w-6 bg-current opacity-60" />
            {children}
        </p>
    );
}

/**
 * Eyebrow + heading + intro, used to open every section so hierarchy stays consistent.
 */
export function SectionHeading({
    id,
    eyebrow,
    title,
    intro,
    align = 'start',
    action,
}: {
    id: string;
    eyebrow?: string;
    title: string;
    intro?: string;
    align?: 'start' | 'center';
    action?: ReactNode;
}) {
    const centered = align === 'center';

    return (
        <div className={`flex flex-col gap-5 md:flex-row md:items-end md:justify-between ${centered ? 'items-center text-center md:flex-col md:items-center' : ''}`}>
            <div className={`max-w-2xl ${centered ? 'mx-auto' : ''}`}>
                {eyebrow !== undefined ? <Eyebrow>{eyebrow}</Eyebrow> : null}
                <h2 id={id} className="mt-3 text-3xl text-ink md:text-4xl">
                    {title}
                </h2>
                {intro !== undefined ? <p className="mt-4 text-lg text-ink-muted">{intro}</p> : null}
            </div>
            {action !== undefined ? <div className="shrink-0">{action}</div> : null}
        </div>
    );
}

/**
 * A titled page section with a stable anchor id. `bleed` sections span the full width with their
 * own background; others sit in the centred content column.
 */
export function Section({
    id,
    title,
    eyebrow,
    intro,
    children,
    action,
    className = '',
}: {
    id: string;
    title: string;
    eyebrow?: string;
    intro?: string;
    children: ReactNode;
    action?: ReactNode;
    className?: string;
}) {
    const headingId = `${id}-title`;

    return (
        <section id={id} aria-labelledby={headingId} className={`scroll-mt-24 py-16 md:py-24 ${className}`}>
            <SectionHeading id={headingId} eyebrow={eyebrow} title={title} intro={intro} action={action} />
            <div className="mt-10 md:mt-12">{children}</div>
        </section>
    );
}

/**
 * Centred content column with the standard side gutter.
 */
export function Container({ children, className = '' }: { children: ReactNode; className?: string }) {
    return <div className={`mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8 ${className}`}>{children}</div>;
}
