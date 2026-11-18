import { Link } from '@inertiajs/react';
import { ArrowUpRight, type LucideIcon } from 'lucide-react';
import type { Tone } from '@/components/ui/Badge';

const ICON_TONES: Record<Tone, string> = {
    neutral: 'bg-surface-sunken text-ink-muted',
    brand: 'bg-brand-soft text-brand',
    success: 'bg-success-soft text-success',
    warning: 'bg-warning-soft text-warning',
    danger: 'bg-danger-soft text-danger',
    info: 'bg-info-soft text-info',
};

type StatCardProps = {
    label: string;
    value: string | number;
    icon: LucideIcon;
    tone?: Tone;
    hint?: string;
    href?: string;
};

/**
 * KPI tile: icon, large value, label and an optional hint. Becomes a link when `href` is given.
 */
export function StatCard({ label, value, icon: Icon, tone = 'brand', hint, href }: StatCardProps) {
    const body = (
        <>
            <div className="flex items-start justify-between gap-3">
                <span className={`inline-flex size-10 items-center justify-center rounded-xl ${ICON_TONES[tone]}`}>
                    <Icon aria-hidden="true" className="size-5" />
                </span>
                {href !== undefined ? (
                    <ArrowUpRight
                        aria-hidden="true"
                        className="size-4 text-ink-subtle transition-colors group-hover:text-brand rtl:-scale-x-100"
                    />
                ) : null}
            </div>
            <p className="mt-4 text-3xl font-semibold tabular-nums text-ink">
                <bdi>{value}</bdi>
            </p>
            <p className="mt-1 text-sm font-medium text-ink-muted">{label}</p>
            {hint !== undefined ? <p className="mt-2 text-xs text-ink-subtle">{hint}</p> : null}
        </>
    );

    const classes = 'group block rounded-2xl border border-line bg-surface-raised p-5 shadow-card';

    if (href !== undefined) {
        return (
            <Link href={href} className={`${classes} hover-lift hover:border-brand/40`}>
                {body}
            </Link>
        );
    }

    return <div className={classes}>{body}</div>;
}
