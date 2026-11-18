import { useMemo } from 'react';
import { useT } from '@/lib/i18n';

type DiffLine = {
    type: 'same' | 'add' | 'remove';
    text: string;
};

function computeLineDiff(previousText: string, currentText: string): DiffLine[] {
    const oldLines = previousText.split(/\r?\n/);
    const newLines = currentText.split(/\r?\n/);
    const m = oldLines.length;
    const n = newLines.length;

    const dp: number[][] = Array.from({ length: m + 1 }, () => Array<number>(n + 1).fill(0));

    for (let i = m - 1; i >= 0; i--) {
        const row = dp[i];
        if (!row) {
            continue;
        }
        for (let j = n - 1; j >= 0; j--) {
            if (oldLines[i] === newLines[j]) {
                row[j] = 1 + (dp[i + 1]?.[j + 1] ?? 0);
            } else {
                row[j] = Math.max(dp[i + 1]?.[j] ?? 0, row[j + 1] ?? 0);
            }
        }
    }

    const result: DiffLine[] = [];
    let i = 0;
    let j = 0;

    while (i < m && j < n) {
        if (oldLines[i] === newLines[j]) {
            result.push({ type: 'same', text: oldLines[i] ?? '' });
            i++;
            j++;
        } else if ((dp[i + 1]?.[j] ?? 0) >= (dp[i]?.[j + 1] ?? 0)) {
            result.push({ type: 'remove', text: oldLines[i] ?? '' });
            i++;
        } else {
            result.push({ type: 'add', text: newLines[j] ?? '' });
            j++;
        }
    }

    while (i < m) {
        result.push({ type: 'remove', text: oldLines[i] ?? '' });
        i++;
    }

    while (j < n) {
        result.push({ type: 'add', text: newLines[j] ?? '' });
        j++;
    }

    return result;
}

export function DiffViewer({
    previousText,
    currentText,
    dir = 'auto',
}: {
    previousText: string;
    currentText: string;
    dir?: 'rtl' | 'ltr' | 'auto';
}) {
    const t = useT();
    const lines = useMemo(() => computeLineDiff(previousText, currentText), [previousText, currentText]);
    const hasChanges = lines.some((line) => line.type !== 'same');

    if (!hasChanges) {
        return <p className="mt-3 rounded-xl bg-surface p-3 text-xs text-ink-muted">{t('admin.content.diff_unchanged')}</p>;
    }

    return (
        <div dir={dir} className="mt-3 max-h-96 overflow-auto rounded-xl border border-line bg-surface p-3 font-mono text-xs">
            {lines.map((line, index) => {
                const prefix = line.type === 'add' ? '+' : line.type === 'remove' ? '-' : ' ';
                const toneClass =
                    line.type === 'add'
                        ? 'bg-brand-soft/60 text-brand-strong'
                        : line.type === 'remove'
                          ? 'bg-danger-soft text-danger line-through'
                          : 'text-ink-muted';

                return (
                    <div
                        key={`${index}-${line.type}`}
                        className={`flex gap-2 rounded px-2 py-0.5 whitespace-pre-wrap ${toneClass}`}
                    >
                        <span className="select-none font-bold" dir="ltr">
                            {prefix}
                        </span>
                        <span className="min-w-0 flex-1">{line.text}</span>
                    </div>
                );
            })}
        </div>
    );
}
