import { MessagesSquare } from 'lucide-react';
import { useAiChat } from '@/features/ai-chat/AiChatProvider';
import { useT } from '@/lib/i18n';

/**
 * "Ask Rafiq AI to explain" for one approved glossary term (explain_term, M09). Only the term's id is sent;
 * the AI answers from that approved definition. Rendered inside PublicLayout so it can reach the chat provider.
 */
export function ExplainTermButton({ termId, term }: { termId: number; term: string }) {
    const t = useT();
    const chat = useAiChat();

    return (
        <button
            type="button"
            onClick={() => chat.open({ mode: 'explain_term', context: 'glossary', termId, title: term })}
            className="mt-2 inline-flex min-h-10 cursor-pointer items-center gap-1.5 text-sm font-medium text-brand hover:underline"
        >
            <MessagesSquare aria-hidden="true" className="size-4" />
            {t('ai.explain_term')}
        </button>
    );
}
