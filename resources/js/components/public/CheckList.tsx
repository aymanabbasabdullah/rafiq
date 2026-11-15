import { Check, X } from 'lucide-react';

/**
 * A list of statements marked as allowed (check) or not allowed (cross).
 */
export function CheckList({ items, variant }: { items: string[]; variant: 'yes' | 'no' }) {
    const Icon = variant === 'yes' ? Check : X;
    const iconClass = variant === 'yes' ? 'text-brand' : 'text-danger';

    return (
        <ul className="space-y-2">
            {items.map((item) => (
                <li key={item} className="flex items-start gap-2 text-ink">
                    <Icon aria-hidden="true" className={`mt-1 size-4 shrink-0 ${iconClass}`} />
                    <span>{item}</span>
                </li>
            ))}
        </ul>
    );
}
