import { MessagesSquare } from 'lucide-react';
import { buttonClasses } from '@/components/ui/Button';
import { useAiChat } from '@/features/ai-chat/AiChatProvider';
import { useT } from '@/lib/i18n';

/**
 * "Explain this article" (M09-FR-01). Rendered inside PublicLayout so it can reach the chat provider.
 */
export function ExplainArticleButton({ articleId, title }: { articleId: number; title: string }) {
    const t = useT();
    const chat = useAiChat();

    return (
        <div className="flex flex-col gap-3 rounded-2xl bg-brand-mist p-4 sm:flex-row sm:items-center sm:justify-between">
            <p className="flex items-center gap-3 text-sm text-brand-strong">
                <MessagesSquare aria-hidden="true" className="size-4 shrink-0" />
                {t('ai.explain_article_hint')}
            </p>
            <button
                type="button"
                onClick={() => chat.open({ mode: 'explain_article', context: 'article', articleId, title })}
                className={buttonClasses('primary', 'sm', 'shrink-0')}
            >
                {t('ai.explain_article')}
            </button>
        </div>
    );
}
