import type { ReactNode } from 'react';

export type Tone = 'neutral' | 'brand' | 'success' | 'warning' | 'danger' | 'info';

const TONES: Record<Tone, string> = {
    neutral: 'bg-surface-sunken text-ink-muted ring-line',
    brand: 'bg-brand-soft text-brand-strong ring-brand/20',
    success: 'bg-success-soft text-success ring-success/20',
    warning: 'bg-warning-soft text-warning ring-warning/25',
    danger: 'bg-danger-soft text-danger ring-danger/20',
    info: 'bg-info-soft text-info ring-info/20',
};

/**
 * Small status pill. Colour is always paired with text so meaning never relies on colour alone.
 */
export function Badge({ tone = 'neutral', children, dot = false }: { tone?: Tone; children: ReactNode; dot?: boolean }) {
    return (
        <span
            className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset ${TONES[tone]}`}
        >
            {dot ? <span aria-hidden="true" className="size-1.5 rounded-full bg-current" /> : null}
            {children}
        </span>
    );
}
