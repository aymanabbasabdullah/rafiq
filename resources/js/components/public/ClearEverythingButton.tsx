import { router, usePage } from '@inertiajs/react';
import { Eraser } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/public/ConfirmDialog';
import { useT } from '@/lib/i18n';

/**
 * "Clear everything" (PRIV-011): ends the session on the server, then clears tab storage and Inertia history.
 */
export function ClearEverythingButton({ className = '' }: { className?: string }) {
    const { links } = usePage().props;
    const t = useT();
    const [confirming, setConfirming] = useState(false);
    const [busy, setBusy] = useState(false);

    const clear = () => {
        setBusy(true);

        try {
            window.sessionStorage.clear();
        } catch {
            // Storage may be unavailable; the server-side session is still cleared.
        }

        router.post(
            links.clearSession,
            {},
            {
                onFinish: () => {
                    setBusy(false);
                    setConfirming(false);
                },
            },
        );
    };

    return (
        <>
            <button
                type="button"
                onClick={() => setConfirming(true)}
                className={`inline-flex min-h-11 items-center gap-2 rounded-md border border-line px-3 py-2 text-sm font-medium text-ink hover:bg-surface ${className}`}
            >
                <Eraser aria-hidden="true" className="size-4" />
                {t('clear.button')}
            </button>
            <ConfirmDialog
                open={confirming}
                title={t('clear.confirm_title')}
                confirmLabel={t('clear.confirm')}
                cancelLabel={t('clear.cancel')}
                onConfirm={clear}
                onCancel={() => setConfirming(false)}
                busy={busy}
                tone="danger"
            >
                <p>{t('clear.confirm_body')}</p>
            </ConfirmDialog>
        </>
    );
}
