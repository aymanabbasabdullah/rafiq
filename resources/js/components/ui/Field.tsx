import type { InputHTMLAttributes, ReactNode, SelectHTMLAttributes } from 'react';

export const inputClasses =
    'block w-full rounded-lg border border-line bg-surface-raised px-3.5 py-2.5 text-sm text-ink shadow-xs transition-colors placeholder:text-ink-subtle hover:border-line-strong focus:border-brand focus:ring-2 focus:ring-brand/20 focus:outline-none aria-invalid:border-danger';

type FieldProps = {
    id: string;
    label: string;
    error?: string;
    help?: string;
    children: ReactNode;
};

/**
 * Label + control + help/error text, wired with aria-describedby and aria-invalid.
 */
export function Field({ id, label, error, help, children }: FieldProps) {
    return (
        <div>
            <label htmlFor={id} className="block text-sm font-medium text-ink">
                {label}
            </label>
            <div className="mt-1.5">{children}</div>
            {help !== undefined && error === undefined ? (
                <p id={`${id}-help`} className="mt-1.5 text-xs text-ink-subtle">
                    {help}
                </p>
            ) : null}
            {error !== undefined ? (
                <p id={`${id}-error`} role="alert" className="mt-1.5 text-xs font-medium text-danger">
                    {error}
                </p>
            ) : null}
        </div>
    );
}

export function TextInput({ invalid, ...props }: InputHTMLAttributes<HTMLInputElement> & { invalid?: boolean }) {
    return <input {...props} aria-invalid={invalid || undefined} className={`${inputClasses} ${props.className ?? ''}`} />;
}

export function SelectInput({ children, ...props }: SelectHTMLAttributes<HTMLSelectElement>) {
    return (
        <select {...props} className={`${inputClasses} cursor-pointer ${props.className ?? ''}`}>
            {children}
        </select>
    );
}

/**
 * Checkbox styled as a selectable pill (role pickers, filters).
 */
export function PillCheckbox({ checked, onChange, label }: { checked: boolean; onChange: () => void; label: string }) {
    return (
        <label
            className={`inline-flex min-h-9 cursor-pointer items-center gap-2 rounded-full border px-3.5 text-sm font-medium transition-colors duration-200 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand ${
                checked
                    ? 'border-brand bg-brand-soft text-brand-strong'
                    : 'border-line bg-surface-raised text-ink-muted hover:border-line-strong'
            }`}
        >
            <input type="checkbox" checked={checked} onChange={onChange} className="sr-only" />
            <span aria-hidden="true" className={`size-2 rounded-full ${checked ? 'bg-brand' : 'bg-line-strong'}`} />
            {label}
        </label>
    );
}
