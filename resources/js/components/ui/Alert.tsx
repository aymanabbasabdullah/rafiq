import { CircleAlert, CircleCheck, Info, TriangleAlert, type LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

type AlertTone = 'info' | 'success' | 'warning' | 'danger';

const TONES: Record<AlertTone, { classes: string; icon: LucideIcon }> = {
    info: { classes: 'border-info/20 bg-info-soft text-ink [&_svg]:text-info', icon: Info },
    success: { classes: 'border-success/20 bg-success-soft text-ink [&_svg]:text-success', icon: CircleCheck },
    warning: { classes: 'border-warning/25 bg-warning-soft text-ink [&_svg]:text-warning', icon: TriangleAlert },
    danger: { classes: 'border-danger/25 bg-danger-soft text-ink [&_svg]:text-danger', icon: CircleAlert },
};

/**
 * Inline message with an icon, optional title and optional action. Colour is always paired with
 * an icon and text. Use `role="alert"` only for errors that appear after an action.
 */
export function Alert({
    tone = 'info',
    title,
    children,
    action,
    role,
    className = '',
}: {
    tone?: AlertTone;
    title?: string;
    children?: ReactNode;
    action?: ReactNode;
    role?: 'alert' | 'status' | 'note';
    className?: string;
}) {
    const { classes, icon: Icon } = TONES[tone];

    return (
        <div
            role={role}
            className={`flex flex-col gap-3 rounded-2xl border p-4 sm:flex-row sm:items-start ${classes} ${className}`}
        >
            <div className="flex flex-1 items-start gap-3">
                <Icon aria-hidden="true" className="mt-0.5 size-5 shrink-0" />
                <div className="min-w-0 text-sm">
                    {title !== undefined ? <p className="font-semibold">{title}</p> : null}
                    {children !== undefined ? (
                        <div className={title !== undefined ? 'mt-1 text-ink-muted' : ''}>{children}</div>
                    ) : null}
                </div>
            </div>
            {action !== undefined ? <div className="shrink-0 sm:self-center">{action}</div> : null}
        </div>
    );
}
