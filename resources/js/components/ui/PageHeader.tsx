import type { ReactNode } from 'react';

/**
 * Page title row: eyebrow, title, description and actions. Wraps cleanly on small screens.
 */
export function PageHeader({
    title,
    description,
    eyebrow,
    actions,
}: {
    title: string;
    description?: string;
    eyebrow?: ReactNode;
    actions?: ReactNode;
}) {
    return (
        <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div className="min-w-0">
                {eyebrow !== undefined ? <div className="mb-1 text-sm font-medium text-brand">{eyebrow}</div> : null}
                <h1 className="text-2xl font-semibold text-ink md:text-3xl">{title}</h1>
                {description !== undefined ? (
                    <p className="mt-1.5 max-w-2xl text-sm text-ink-muted md:text-base">{description}</p>
                ) : null}
            </div>
            {actions !== undefined ? <div className="flex shrink-0 flex-wrap gap-2">{actions}</div> : null}
        </div>
    );
}
