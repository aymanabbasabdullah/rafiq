import { Link } from '@inertiajs/react';
import type { ButtonHTMLAttributes, ReactNode } from 'react';

type Variant = 'primary' | 'secondary' | 'ghost' | 'danger';
type Size = 'sm' | 'md';

const VARIANTS: Record<Variant, string> = {
    primary: 'bg-brand text-brand-ink shadow-sm hover:bg-brand-strong',
    secondary: 'border border-line bg-surface-raised text-ink hover:border-line-strong hover:bg-surface',
    ghost: 'text-ink-muted hover:bg-surface-sunken hover:text-ink',
    danger: 'border border-danger/30 bg-surface-raised text-danger hover:bg-danger-soft',
};

const SIZES: Record<Size, string> = {
    sm: 'min-h-9 gap-1.5 px-3 text-sm',
    md: 'min-h-11 gap-2 px-4 text-sm',
};

export function buttonClasses(variant: Variant = 'primary', size: Size = 'md', extra = ''): string {
    return `inline-flex cursor-pointer items-center justify-center rounded-lg font-semibold transition-colors duration-200 disabled:cursor-not-allowed disabled:opacity-50 ${VARIANTS[variant]} ${SIZES[size]} ${extra}`;
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
