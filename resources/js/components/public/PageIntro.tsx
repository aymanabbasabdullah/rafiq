import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { Eyebrow } from '@/components/public/Section';

/**
 * Opening block for interior pages: optional icon, eyebrow, the page's single h1, a lead paragraph
 * and actions. `centered` suits short decision pages (gates); the default suits reading pages.
 */
export function PageIntro({
    id,
    title,
    eyebrow,
    lead,
    icon: Icon,
    actions,
    centered = false,
    children,
}: {
    id: string;
    title: string;
    eyebrow?: string;
    lead?: string;
    icon?: LucideIcon;
    actions?: ReactNode;
    centered?: boolean;
    children?: ReactNode;
}) {
    return (
        <header className={`animate-fade-up ${centered ? 'mx-auto max-w-xl text-center' : 'max-w-3xl'}`}>
            {Icon !== undefined ? (
                <span className={`mb-6 inline-flex size-14 items-center justify-center rounded-2xl bg-brand-soft text-brand ${centered ? 'mx-auto' : ''}`}>
                    <Icon aria-hidden="true" className="size-7" />
                </span>
            ) : null}
            {eyebrow !== undefined ? (
                <div className={centered ? 'flex justify-center' : ''}>
                    <Eyebrow>{eyebrow}</Eyebrow>
                </div>
            ) : null}
            <h1 id={id} className="mt-3 text-3xl text-ink md:text-5xl md:leading-[1.15]">
                {title}
            </h1>
            {lead !== undefined ? <p className="mt-5 text-lg text-ink-muted md:text-xl">{lead}</p> : null}
            {children}
            {actions !== undefined ? <div className={`mt-8 flex flex-wrap gap-3 ${centered ? 'justify-center' : ''}`}>{actions}</div> : null}
        </header>
    );
}
