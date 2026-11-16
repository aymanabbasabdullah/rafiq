import { useEffect, useRef, type ReactNode } from 'react';
import { buttonClasses } from '@/components/ui/Button';

type ConfirmDialogProps = {
    open: boolean;
    title: string;
    children: ReactNode;
    confirmLabel: string;
    cancelLabel: string;
    onConfirm: () => void;
    onCancel: () => void;
    busy?: boolean;
    tone?: 'brand' | 'danger';
};

/**
 * Accessible modal confirmation built on the native <dialog> element
 * (focus trapping, Escape to cancel and the backdrop come from the browser).
 */
export function ConfirmDialog({
    open,
    title,
    children,
    confirmLabel,
    cancelLabel,
    onConfirm,
    onCancel,
    busy = false,
    tone = 'brand',
}: ConfirmDialogProps) {
    const dialog = useRef<HTMLDialogElement>(null);

    useEffect(() => {
        const element = dialog.current;

        if (element === null) {
            return;
        }

        if (open && !element.open) {
            element.showModal();
        } else if (!open && element.open) {
            element.close();
        }
    }, [open]);

    return (
        <dialog
            ref={dialog}
            aria-labelledby="confirm-dialog-title"
            onCancel={(event) => {
                event.preventDefault();
                onCancel();
            }}
            className="m-auto w-[min(32rem,calc(100vw-2rem))] rounded-[var(--radius-panel)] bg-surface-raised p-6 text-ink shadow-lift backdrop:bg-brand-deep/60 backdrop:backdrop-blur-sm open:animate-fade-up sm:p-8"
        >
            <h2 id="confirm-dialog-title" className="text-2xl">
                {title}
            </h2>
            <div className="mt-4 text-ink-muted">{children}</div>
            <div className="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    onClick={onCancel}
                    className={buttonClasses('secondary')}
                >
                    {cancelLabel}
                </button>
                <button
                    type="button"
                    onClick={onConfirm}
                    disabled={busy}
                    className={buttonClasses(tone === 'danger' ? 'destructive' : 'primary')}
                >
                    {confirmLabel}
                </button>
            </div>
        </dialog>
    );
}
