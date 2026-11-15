import { usePage } from '@inertiajs/react';
import { EyeOff } from 'lucide-react';
import { useT } from '@/lib/i18n';
import { quickExit, useQuickExitShortcut } from '@/lib/quickExit';

/**
 * Always-visible Quick exit control (FR-005).
 */
export function QuickExitButton() {
    const { quickExitUrl } = usePage().props;
    const t = useT();

    useQuickExitShortcut(quickExitUrl);

    return (
        <button
            type="button"
            onClick={() => quickExit(quickExitUrl)}
            title={t('nav.quick_exit_hint')}
            aria-describedby="quick-exit-hint"
            className="inline-flex min-h-11 items-center gap-2 rounded-md bg-ink px-3 py-2 text-sm font-semibold text-surface-raised hover:bg-ink/90"
        >
            <EyeOff aria-hidden="true" className="size-4" />
            <span>{t('nav.quick_exit')}</span>
            <span id="quick-exit-hint" className="sr-only">
                {t('nav.quick_exit_hint')}
            </span>
        </button>
    );
}
