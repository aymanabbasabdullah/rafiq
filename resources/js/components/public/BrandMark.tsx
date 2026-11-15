/**
 * Interim Rafiq mark: two overlapping circles for "companion" (OQ-015 — final identity pending).
 */
export function BrandMark({ className = 'size-8' }: { className?: string }) {
    return (
        <svg viewBox="0 0 32 32" aria-hidden="true" className={className}>
            <circle cx="12" cy="16" r="9" fill="currentColor" opacity="0.35" />
            <circle cx="20" cy="16" r="9" fill="currentColor" opacity="0.85" />
        </svg>
    );
}
