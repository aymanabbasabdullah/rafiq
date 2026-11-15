import type { ReactNode } from 'react';

/**
 * A titled landing page section with a stable anchor id.
 */
export function Section({ id, title, children, intro }: { id: string; title: string; children: ReactNode; intro?: string }) {
    const headingId = `${id}-title`;

    return (
        <section id={id} aria-labelledby={headingId} className="scroll-mt-6 py-10">
            <h2 id={headingId} className="text-2xl font-semibold text-ink">
                {title}
            </h2>
            {intro !== undefined ? <p className="mt-2 max-w-prose text-ink-muted">{intro}</p> : null}
            <div className="mt-6">{children}</div>
        </section>
    );
}
