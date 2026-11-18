import { createContext, lazy, Suspense, useCallback, useContext, useMemo, useState, type ReactNode } from 'react';

export type ChatTarget = {
    mode: 'platform_guide' | 'explain_article' | 'explain_term' | 'general_education';
    context: 'home' | 'learn' | 'article' | 'glossary';
    articleId?: number;
    termId?: number;
    title?: string;
};

type AiChat = { open: (target?: Partial<ChatTarget>) => void };

const AiChatContext = createContext<AiChat>({ open: () => undefined });

// The panel (and everything it imports) loads only after the visitor asks for it (FR-080, GR-12).
const ChatPanel = lazy(() => import('@/features/ai-chat/ChatPanel'));

const DEFAULT_TARGET: ChatTarget = { mode: 'general_education', context: 'learn' };

/**
 * Holds whether Rafiq AI is open and for what. The conversation itself lives only inside the panel's
 * memory and disappears when the panel closes or the tab is closed (FR-083).
 */
export function AiChatProvider({ children }: { children: ReactNode }) {
    const [target, setTarget] = useState<ChatTarget | null>(null);

    const open = useCallback((next?: Partial<ChatTarget>) => setTarget({ ...DEFAULT_TARGET, ...next }), []);
    const value = useMemo(() => ({ open }), [open]);

    return (
        <AiChatContext.Provider value={value}>
            {children}
            {target !== null ? (
                <Suspense fallback={null}>
                    <ChatPanel target={target} onClose={() => setTarget(null)} />
                </Suspense>
            ) : null}
        </AiChatContext.Provider>
    );
}

export function useAiChat(): AiChat {
    return useContext(AiChatContext);
}
