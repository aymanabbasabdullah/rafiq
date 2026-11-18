/**
 * Small loading indicator. Pass a visible or screen-reader label so the state is announced.
 */
export function Spinner({ label, className = 'size-4' }: { label?: string; className?: string }) {
    return (
        <span role="status" className="inline-flex items-center gap-2">
            <svg viewBox="0 0 24 24" aria-hidden="true" className={`animate-spin motion-reduce:animate-none ${className}`}>
                <circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" strokeWidth="3" opacity="0.2" />
                <path d="M21 12a9 9 0 0 0-9-9" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" />
            </svg>
            {label !== undefined ? <span className="sr-only">{label}</span> : null}
        </span>
    );
}

/**
 * Pulsing placeholder lines for content that is still loading.
 */
export function SkeletonLines({ lines = 3 }: { lines?: number }) {
    return (
        <div aria-hidden="true" className="space-y-2.5">
            {Array.from({ length: lines }, (_, index) => (
                <div
                    key={index}
                    className="h-3 animate-pulse rounded-full bg-surface-sunken motion-reduce:animate-none"
                    style={{ inlineSize: `${100 - index * 14}%` }}
                />
            ))}
        </div>
    );
}
