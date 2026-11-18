import { useState } from 'react';
import { ConfirmDialog } from '@/components/public/ConfirmDialog';
import { Badge, type Tone } from '@/components/ui/Badge';
import { inputClasses } from '@/components/ui/Field';
import { useT } from '@/lib/i18n';

const PRIORITY_TONES: Record<string, Tone> = {
    GATE: 'neutral',
    P0: 'danger',
    P1: 'warning',
    P2: 'info',
    P3: 'brand',
    P4: 'neutral',
};

const RELEASE_TONES: Record<string, Tone> = {
    draft: 'neutral',
    pending_medical_review: 'warning',
    approved: 'success',
    published: 'brand',
    superseded: 'neutral',
    retired: 'neutral',
};

/**
 * Priority level with its name, never colour alone (SAFE-004).
 */
export function PriorityBadge({ priority }: { priority: string }) {
    const t = useT();

    return (
        <Badge tone={PRIORITY_TONES[priority] ?? 'neutral'}>
            <bdi dir="ltr">{priority}</bdi> {t(`admin.clinical.priority.${priority}`)}
        </Badge>
    );
}

export function ReleaseBadge({ status }: { status: string }) {
    const t = useT();

    return (
        <Badge tone={RELEASE_TONES[status] ?? 'neutral'} dot>
            {t(`admin.clinical.release_status.${status}`)}
        </Badge>
    );
}

/**
 * Approve or reject with a mandatory comment (Clinical-Review-Process §3–§4).
 */
export function DecisionDialog({
    decision,
    busy,
    onCancel,
    onConfirm,
}: {
    decision: 'approve' | 'reject' | null;
    busy: boolean;
    onCancel: () => void;
    onConfirm: (comment: string) => void;
}) {
    const t = useT();
    const [comment, setComment] = useState('');
    const reject = decision === 'reject';

    return (
        <ConfirmDialog
            open={decision !== null}
            title={reject ? t('admin.clinical.reject_title') : t('admin.clinical.approve_title')}
            confirmLabel={reject ? t('admin.clinical.action.reject') : t('admin.clinical.action.approve')}
            cancelLabel={t('clear.cancel')}
            tone={reject ? 'danger' : 'brand'}
            busy={busy || comment.trim() === ''}
            onCancel={() => {
                setComment('');
                onCancel();
            }}
            onConfirm={() => {
                onConfirm(comment);
                setComment('');
            }}
        >
            <p className="mb-3 text-sm">{reject ? t('admin.clinical.reject_body') : t('admin.clinical.approve_body')}</p>
            <label htmlFor="clinical-decision-comment" className="text-sm font-medium text-ink">
                {t('admin.clinical.comment_required')}
            </label>
            <textarea
                id="clinical-decision-comment"
                rows={3}
                value={comment}
                onChange={(event) => setComment(event.target.value)}
                className={`${inputClasses} mt-1.5`}
            />
        </ConfirmDialog>
    );
}
