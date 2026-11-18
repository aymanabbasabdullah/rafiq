import { useEffect } from 'react';

const DOUBLE_PRESS_WINDOW_MS = 1000;

/**
 * Leaves Rafiq immediately for a neutral site (FR-005).
 *
 * `location.replace` swaps the current history entry, so pressing Back does not return to Rafiq's page.
 * Tab-scoped storage is cleared; Rafiq keeps no other state in the browser by default.
 */
export function quickExit(url: string): void {
    try {
        window.sessionStorage.clear();
    } catch {
        // Storage can be unavailable (private mode or blocked); leaving matters more.
    }

    document.body.style.visibility = 'hidden';
    window.location.replace(url);
}

/**
 * Pressing Escape twice within one second triggers Quick exit.
 */
export function useQuickExitShortcut(url: string): void {
    useEffect(() => {
        let lastEscapeAt = 0;

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key !== 'Escape') {
                return;
            }

            const now = Date.now();

            if (now - lastEscapeAt <= DOUBLE_PRESS_WINDOW_MS) {
                quickExit(url);

                return;
            }

            lastEscapeAt = now;
        };

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, [url]);
}
