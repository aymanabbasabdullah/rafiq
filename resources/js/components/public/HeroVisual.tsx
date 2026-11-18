import { BookOpenCheck, Languages, Signpost, type LucideIcon } from 'lucide-react';
import type { CSSProperties } from 'react';
import { useT } from '@/lib/i18n';

type Chip = { key: string; icon: LucideIcon; position: CSSProperties; delay: string };

/**
 * Hero composition: a single path climbing through three points, from reliable information to a clear
 * next step, over quiet contour rings and a measurement grid. The SVG mirrors in RTL so the path
 * follows reading direction; the chips use logical insets so they mirror with it.
 *
 * Decorative only: every chip repeats a fact stated elsewhere on the page.
 */
export function HeroVisual() {
    const t = useT();

    // Node positions in the 400×400 viewBox: (70,330), (330,200), (70,75) → logical % offsets.
    const chips: Chip[] = [
        { key: 'chip_sources', icon: BookOpenCheck, position: { insetInlineStart: 'calc(17.5% + 18px)', top: 'calc(82.5% - 22px)' }, delay: '0.5s' },
        { key: 'chip_languages', icon: Languages, position: { insetInlineEnd: 'calc(17.5% + 18px)', top: 'calc(50% - 22px)' }, delay: '0.9s' },
        { key: 'chip_next', icon: Signpost, position: { insetInlineStart: 'calc(17.5% + 18px)', top: 'calc(18.75% - 22px)' }, delay: '1.3s' },
    ];

    return (
        <div aria-hidden="true" className="relative aspect-square w-full overflow-hidden rounded-[var(--radius-panel)] bg-brand-deep">
            <svg viewBox="0 0 400 400" className="absolute inset-0 size-full rtl:-scale-x-100">
                <defs>
                    <pattern id="hero-grid" width="20" height="20" patternUnits="userSpaceOnUse">
                        <circle cx="1" cy="1" r="1" fill="var(--color-mint)" opacity="0.16" />
                    </pattern>
                    <radialGradient id="hero-glow" cx="60%" cy="66%" r="60%">
                        <stop offset="0%" stopColor="var(--color-brand)" stopOpacity="0.55" />
                        <stop offset="100%" stopColor="var(--color-brand-deep)" stopOpacity="0" />
                    </radialGradient>
                </defs>

                <rect width="400" height="400" fill="url(#hero-glow)" />
                <rect width="400" height="400" fill="url(#hero-grid)" />

                {[70, 112, 154, 196, 238].map((radius, index) => (
                    <circle
                        key={radius}
                        cx="240"
                        cy="268"
                        r={radius}
                        fill="none"
                        stroke="var(--color-mint)"
                        strokeOpacity={0.22 - index * 0.04}
                        strokeWidth="1"
                    />
                ))}

                <g transform="translate(240 272)">
                    <circle cx="-14" r="40" fill="none" stroke="var(--color-mint)" strokeOpacity="0.5" strokeWidth="6" />
                    <circle cx="14" r="40" fill="var(--color-brand)" />
                    <path d="M0 -37.4A40 40 0 0 1 0 37.4A40 40 0 0 1 0 -37.4Z" fill="var(--color-mint)" opacity="0.9" />
                </g>

                <path
                    d="M-4 398C30 372 44 346 70 330C150 284 302 262 330 200C356 146 160 100 70 75C40 66 20 50 6 36"
                    pathLength="1"
                    fill="none"
                    stroke="var(--color-mint)"
                    strokeWidth="2"
                    strokeLinecap="round"
                    className="draw-line"
                />

                {[
                    [70, 330],
                    [330, 200],
                    [70, 75],
                ].map(([cx, cy]) => (
                    <g key={`${cx}-${cy}`}>
                        <circle cx={cx} cy={cy} r="15" fill="var(--color-mint)" className="halo" />
                        <circle cx={cx} cy={cy} r="6" fill="var(--color-brand-deep)" stroke="var(--color-mint)" strokeWidth="2.5" />
                    </g>
                ))}
            </svg>

            {chips.map(({ key, icon: Icon, position, delay }) => (
                <div
                    key={key}
                    style={{ ...position, animationDelay: delay }}
                    className="absolute flex max-w-[64%] animate-fade-up items-center gap-2.5 rounded-full bg-surface-raised/95 py-2 ps-2 pe-4 text-xs font-medium text-ink shadow-lift sm:text-sm"
                >
                    <span className="inline-flex size-7 shrink-0 items-center justify-center rounded-full bg-brand-soft text-brand">
                        <Icon className="size-4" />
                    </span>
                    <span className="truncate">{t(`landing.hero.${key}`)}</span>
                </div>
            ))}
        </div>
    );
}
