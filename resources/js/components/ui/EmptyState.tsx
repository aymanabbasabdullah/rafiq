import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

/**
 * Friendly placeholder that explains why a list is empty and what to do next (ui-ux-pro-max: Empty States).
 */
export function EmptyState({
    icon: Icon,
    title,
    description,
    action,
}: {
    icon: LucideIcon;
    title: string;
    description?: string;
    action?: ReactNode;
}) {
    return (
        <div className="flex flex-col items-center rounded-xl border border-dashed border-line-strong bg-surface px-6 py-10 text-center">
            <span className="inline-flex size-12 items-center justify-center rounded-full bg-brand-soft text-brand">
                <Icon aria-hidden="true" className="size-6" />
            </span>
            <p className="mt-4 font-semibold text-ink">{title}</p>
            {description !== undefined ? <p className="mt-1 max-w-sm text-sm text-ink-muted">{description}</p> : null}
            {action !== undefined ? <div className="mt-5">{action}</div> : null}
        </div>
    );
}
