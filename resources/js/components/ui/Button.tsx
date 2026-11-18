import { Link } from '@inertiajs/react';
import type { ButtonHTMLAttributes, ReactNode } from 'react';

type Variant = 'primary' | 'secondary' | 'ghost' | 'danger' | 'destructive' | 'inverse' | 'quiet';
type Size = 'sm' | 'md' | 'lg';

const VARIANTS: Record<Variant, string> = {
    primary: 'bg-brand text-brand-ink hover:bg-brand-strong active:bg-brand-deep',
    secondary: 'border border-line-strong bg-surface-raised text-ink hover:border-brand/40 hover:text-brand-strong',
    ghost: 'text-ink-muted hover:bg-surface-sunken hover:text-ink',
    danger: 'border border-danger/30 bg-surface-raised text-danger hover:bg-danger-soft',
    /** Solid red, for confirming a destructive or urgent action. */
    destructive: 'bg-danger text-danger-ink hover:bg-danger/90',
    /** For use on deep teal surfaces. */
    inverse: 'bg-surface-raised text-brand-deep hover:bg-brand-soft',
    /** Outline for use on deep teal surfaces. */
    quiet: 'border border-white/25 text-white hover:border-white/50 hover:bg-white/5',
};

const SIZES: Record<Size, string> = {
    sm: 'min-h-9 gap-1.5 px-3.5 text-sm',
    md: 'min-h-11 gap-2 px-5 text-sm',
    lg: 'min-h-13 gap-2.5 px-7 text-base',
};

/**
 * Shared button styling (pill shape, 44px+ touch target from `md`). Also used for links styled as buttons.
 */
export function buttonClasses(variant: Variant = 'primary', size: Size = 'md', extra = ''): string {
    return `inline-flex cursor-pointer items-center justify-center rounded-full font-semibold whitespace-nowrap transition-colors duration-200 disabled:cursor-not-allowed disabled:opacity-50 ${VARIANTS[variant]} ${SIZES[size]} ${extra}`;
}

type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & { variant?: Variant; size?: Size };

export function Button({ variant = 'primary', size = 'md', className = '', type = 'button', ...props }: ButtonProps) {
    return <button type={type} className={buttonClasses(variant, size, className)} {...props} />;
}

export function ButtonLink({
    href,
    children,
    variant = 'primary',
    size = 'md',
    className = '',
}: {
    href: string;
    children: ReactNode;
    variant?: Variant;
    size?: Size;
    className?: string;
}) {
    return (
        <Link href={href} className={buttonClasses(variant, size, className)}>
            {children}
        </Link>
    );
}
