import { useEffect, useRef } from 'react';

/**
 * Adds `is-visible` to every `.reveal` element inside the returned container ref once it scrolls into view.
 *
 * Uses IntersectionObserver (no scroll listeners), unobserves after revealing, and reveals
 * everything immediately when reduced motion is preferred or the API is unavailable.
 */
export function useReveal<T extends HTMLElement>() {
    const container = useRef<T>(null);

    useEffect(() => {
        const root = container.current;

        if (root === null) {
            return;
        }

        const items = Array.from(root.querySelectorAll<HTMLElement>('.reveal'));
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (reduceMotion || !('IntersectionObserver' in window)) {
            items.forEach((item) => item.classList.add('is-visible'));

            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            },
            { rootMargin: '0px 0px -10% 0px', threshold: 0.1 },
        );

        items.forEach((item) => observer.observe(item));

        return () => observer.disconnect();
    }, []);

    return container;
}
