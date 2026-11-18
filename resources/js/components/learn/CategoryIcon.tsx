import { BookOpen, HeartPulse, Info, Leaf, Pill, Sprout, TriangleAlert, type LucideIcon } from 'lucide-react';

const ICONS: Record<string, LucideIcon> = {
    'book-open': BookOpen,
    'heart-pulse': HeartPulse,
    info: Info,
    leaf: Leaf,
    pill: Pill,
    sprout: Sprout,
    'triangle-alert': TriangleAlert,
};

/**
 * Maps a category's stored icon key to a Lucide icon (unknown keys fall back to a book).
 */
export function CategoryIcon({ icon, className = 'size-5' }: { icon: string; className?: string }) {
    const Icon = ICONS[icon] ?? BookOpen;

    return <Icon aria-hidden="true" className={className} />;
}
