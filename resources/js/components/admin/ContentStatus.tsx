import { Badge, type Tone } from '@/components/ui/Badge';
import { useT } from '@/lib/i18n';

const REVIEW_TONES: Record<string, Tone> = {
    draft: 'neutral',
    pending_medical_review: 'warning',
    approved: 'success',
    retired: 'neutral',
};

const PUBLISH_TONES: Record<string, Tone> = {
    draft: 'neutral',
    published: 'brand',
    hidden: 'info',
    archived: 'neutral',
};

export function ReviewBadge({ status }: { status: string }) {
    const t = useT();

    return (
        <Badge tone={REVIEW_TONES[status] ?? 'neutral'} dot>
            {t(`admin.content.review.${status}`)}
        </Badge>
    );
}

export function PublishBadge({ status, live }: { status: string; live: boolean }) {
    const t = useT();

    return (
        <Badge tone={live ? 'brand' : (PUBLISH_TONES[status] ?? 'neutral')}>
            {live ? t('admin.content.live') : t(`admin.content.publish.${status}`)}
        </Badge>
    );
}
