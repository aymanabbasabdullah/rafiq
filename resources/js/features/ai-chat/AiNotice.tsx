import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/public/ConfirmDialog';
import { postJson } from '@/lib/http';
import { useT } from '@/lib/i18n';

const POINTS = ['point1', 'point2', 'point3', 'point4', 'point5'];

type AiNoticeProps = {
    open: boolean;
    onAcknowledged: () => void;
    onCancel: () => void;
};

/**
 * Shown before the first AI request of a session (FR-022). No message is sent to the AI
 * until the visitor acknowledges it; the server enforces the same rule (EnsureAiNoticeAcknowledged).
 *
 * Mounted by the AI chat in Module-09.
 */
export function AiNotice({ open, onAcknowledged, onCancel }: AiNoticeProps) {
    const { links } = usePage().props;
    const t = useT();
    const [busy, setBusy] = useState(false);
    const [failed, setFailed] = useState(false);

    const acknowledge = async () => {
        setBusy(true);
        setFailed(false);

        try {
            await postJson(links.aiNotice, {});
            onAcknowledged();
        } catch {
            setFailed(true);
        } finally {
            setBusy(false);
        }
    };

    return (
        <ConfirmDialog
            open={open}
            title={t('ai_notice.title')}
            confirmLabel={t('ai_notice.acknowledge')}
            cancelLabel={t('ai_notice.cancel')}
            onConfirm={() => void acknowledge()}
            onCancel={onCancel}
            busy={busy}
        >
            <ul className="list-disc space-y-2 ps-5 text-ink">
                {POINTS.map((point) => (
                    <li key={point}>{t(`ai_notice.${point}`)}</li>
                ))}
            </ul>
            {failed ? (
                <p role="alert" className="mt-4 text-sm font-medium text-danger">
                    {t('ai_notice.error')}
                </p>
            ) : null}
        </ConfirmDialog>
    );
}
