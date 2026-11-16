/**
 * Rafiq mark: an open ring (the visitor) held by a solid disc (the companion); their overlap is
 * the shared space where understanding happens. Interim identity until OQ-015 is closed.
 */
export function BrandMark({ className = 'size-8' }: { className?: string }) {
    return (
        <svg viewBox="0 0 32 32" aria-hidden="true" className={className}>
            <circle cx="12.5" cy="16" r="8.25" fill="none" stroke="currentColor" strokeWidth="2.5" opacity="0.45" />
            <circle cx="19.5" cy="16" r="8.25" fill="currentColor" />
            <path d="M16 8.53A8.25 8.25 0 0 1 16 23.47A8.25 8.25 0 0 1 16 8.53Z" fill="var(--color-mint)" />
        </svg>
    );
}

/**
 * Mark + wordmark in the display typeface.
 */
export function BrandLockup({ name, className = '' }: { name: string; className?: string }) {
    return (
        <span className={`inline-flex items-center gap-2.5 ${className}`}>
            <BrandMark className="size-8 text-brand" />
            <span className="font-display text-xl font-semibold text-ink">{name}</span>
        </span>
    );
}
