import { usePage } from '@inertiajs/react';
import { EyeOff } from 'lucide-react';
import { useT } from '@/lib/i18n';
import { quickExit, useQuickExitShortcut } from '@/lib/quickExit';

/**
 * Always-visible Quick exit control (FR-005). The label stays visible at every width.
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
            className="inline-flex min-h-11 shrink-0 cursor-pointer items-center gap-2 rounded-full bg-ink px-3.5 whitespace-nowrap text-sm font-semibold text-white transition-colors hover:bg-brand-deep"
        >
            <EyeOff aria-hidden="true" className="size-4" />
            <span>{t('nav.quick_exit')}</span>
            <span id="quick-exit-hint" className="sr-only">
                {t('nav.quick_exit_hint')}
            </span>
        </button>
    );
}
