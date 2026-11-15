import { useEffect, useRef, type ReactNode } from 'react';

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

    const confirmClass = tone === 'danger' ? 'bg-danger text-danger-ink' : 'bg-brand text-brand-ink';

    return (
        <dialog
            ref={dialog}
            aria-labelledby="confirm-dialog-title"
            onCancel={(event) => {
                event.preventDefault();
                onCancel();
            }}
            className="m-auto w-[min(32rem,calc(100vw-2rem))] rounded-xl border border-line bg-surface-raised p-6 text-ink shadow-xl backdrop:bg-ink/50"
        >
            <h2 id="confirm-dialog-title" className="text-xl font-semibold">
                {title}
            </h2>
            <div className="mt-4 text-ink-muted">{children}</div>
            <div className="mt-6 flex flex-wrap justify-end gap-3">
                <button
                    type="button"
                    onClick={onCancel}
                    className="inline-flex min-h-11 items-center rounded-md border border-line px-4 py-2 font-medium"
                >
                    {cancelLabel}
                </button>
                <button
                    type="button"
                    onClick={onConfirm}
                    disabled={busy}
                    className={`inline-flex min-h-11 items-center rounded-md px-4 py-2 font-semibold disabled:opacity-60 ${confirmClass}`}
                >
                    {confirmLabel}
                </button>
            </div>
        </dialog>
    );
}
