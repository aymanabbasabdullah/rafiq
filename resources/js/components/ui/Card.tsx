import type { ReactNode } from 'react';

/**
 * Surface container with a consistent border, radius and soft shadow.
 */
export function Card({
    children,
    className = '',
    as: Tag = 'div',
}: {
    children: ReactNode;
    className?: string;
    as?: 'div' | 'section' | 'article';
}) {
    return <Tag className={`rounded-2xl border border-line bg-surface-raised shadow-card ${className}`}>{children}</Tag>;
}

/**
 * Card with a titled header row and optional action.
 */
export function Panel({
    title,
    description,
    action,
    children,
    className = '',
}: {
    title: string;
    description?: string;
    action?: ReactNode;
    children: ReactNode;
    className?: string;
}) {
    const id = `panel-${title.replace(/\s+/g, '-').toLowerCase()}`;

    return (
        <section aria-labelledby={id} className={`rounded-2xl border border-line bg-surface-raised shadow-card ${className}`}>
            <header className="flex flex-wrap items-start justify-between gap-3 border-b border-line px-5 py-4">
                <div>
                    <h2 id={id} className="text-base font-semibold text-ink">
                        {title}
                    </h2>
                    {description !== undefined ? <p className="mt-0.5 text-sm text-ink-muted">{description}</p> : null}
                </div>
                {action}
            </header>
            <div className="p-5">{children}</div>
        </section>
    );
}
