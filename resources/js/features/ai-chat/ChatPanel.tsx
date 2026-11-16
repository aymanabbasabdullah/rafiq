import { Link, usePage } from '@inertiajs/react';
import { ArrowRight, Eraser, MessagesSquare, Send, ShieldAlert, Square, X } from 'lucide-react';
import { type FormEvent, useEffect, useRef, useState } from 'react';
import type { ChatTarget } from '@/features/ai-chat/AiChatProvider';
import { AiNotice } from '@/features/ai-chat/AiNotice';
import { QuickExitButton } from '@/components/public/QuickExitButton';
import { buttonClasses } from '@/components/ui/Button';
import { inputClasses } from '@/components/ui/Field';
import { Spinner } from '@/components/ui/Spinner';
import { getJson, JsonRequestError, postJson } from '@/lib/http';
import { useT } from '@/lib/i18n';

type Reply = {
    kind: 'answer' | 'emergency' | 'redirect' | 'refusal' | 'unavailable';
    outcome: string;
    text: string;
    citations: { title: string; url: string }[];
    action: { label: string; url: string } | null;
};

type Message = { id: number; role: 'user' | 'assistant'; text: string; reply?: Reply; status?: 'error' };

const TIMEOUT_MS = 15000;
const MAX_CHARS = 500;

/**
 * Rafiq AI conversation (Module-09 §7). Side panel on desktop, full sheet on mobile. Plain-text replies,
 * emergency replies styled apart, citations to approved articles and the fixed disclaimer under each reply.
 */
export default function ChatPanel({ target, onClose }: { target: ChatTarget; onClose: () => void }) {
    const { ai, gates, links } = usePage().props;
    const t = useT();
    const [messages, setMessages] = useState<Message[]>([]);
    const [draft, setDraft] = useState('');
    const [busy, setBusy] = useState(false);
    const [noticeAccepted, setNoticeAccepted] = useState(gates.aiNoticeAcknowledged);
    const [noticeOpen, setNoticeOpen] = useState(false);
    const [pending, setPending] = useState<{ text: string; preset?: 'summarize' | 'simplify' } | null>(null);
    const [simplification, setSimplification] = useState(2);
    const [intro, setIntro] = useState<{ welcome: string; suggestions: string[] } | null>(null);
    const [disclaimer, setDisclaimer] = useState<string | null>(null);
    const controller = useRef<AbortController | null>(null);
    const list = useRef<HTMLDivElement>(null);
    const nextId = useRef(1);
    const input = useRef<HTMLTextAreaElement>(null);

    useEffect(() => {
        input.current?.focus();
        const onKey = (event: KeyboardEvent) => event.key === 'Escape' && onClose();
        window.addEventListener('keydown', onKey);

        return () => {
            window.removeEventListener('keydown', onKey);
            controller.current?.abort();
        };
    }, [onClose]);

    useEffect(() => {
        if (!gates.ageConfirmed) {
            return;
        }

        const abort = new AbortController();
        getJson<{ welcome: string; suggestions: string[] }>(`${ai.suggestionsUrl}?context=${target.context}`, abort.signal)
            .then(setIntro)
            .catch(() => setIntro(null));

        return () => abort.abort();
    }, [ai.suggestionsUrl, gates.ageConfirmed, target.context]);

    useEffect(() => {
        list.current?.scrollTo({ top: list.current.scrollHeight, behavior: 'smooth' });
    }, [messages, busy]);

    const add = (message: Omit<Message, 'id'>) => setMessages((current) => [...current, { ...message, id: nextId.current++ }]);

    const history = () =>
        messages
            .filter((message) => message.status !== 'error' && (message.role === 'user' || message.reply?.kind === 'answer'))
            .slice(-6)
            .map((message) => ({ role: message.role, text: message.text }));

    const send = async (text: string, preset?: 'summarize' | 'simplify', noticeJustAccepted = false) => {
        const message = text.trim();

        if (message.length < 2 || busy) {
            return;
        }

        if (!noticeAccepted && !noticeJustAccepted) {
            setPending({ text: message, preset });
            setNoticeOpen(true);

            return;
        }

        const previous = history();
        add({ role: 'user', text: message });
        setDraft('');
        setBusy(true);

        const abort = new AbortController();
        controller.current = abort;
        const timer = window.setTimeout(() => abort.abort('timeout'), TIMEOUT_MS);

        try {
            const response = await postJson<{ reply: Reply; disclaimer: string }>(
                ai.messagesUrl,
                {
                    mode: target.mode,
                    message,
                    article_id: target.articleId ?? null,
                    term_id: target.termId ?? null,
                    preset: preset ?? null,
                    history: previous,
                    simplification,
                },
                abort.signal,
            );
            setDisclaimer(response.disclaimer);
            add({ role: 'assistant', text: response.reply.text, reply: response.reply });
        } catch (error) {
            const status = error instanceof JsonRequestError ? error.status : 0;

            if (status === 409) {
                setNoticeAccepted(false);
                setPending({ text: message, preset });
                setNoticeOpen(true);
            }

            const key = abort.signal.aborted
                ? abort.signal.reason === 'timeout'
                    ? 'ai.timeout'
                    : null
                : status === 429
                  ? 'ai.rate_limited'
                  : status === 503
                    ? 'ai.disabled'
                    : status === 409
                      ? null
                      : 'ai.error';

            if (key !== null) {
                add({ role: 'assistant', text: t(key), status: 'error' });
            }
        } finally {
            window.clearTimeout(timer);
            setBusy(false);
            controller.current = null;
        }
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        void send(draft);
    };

    const suggestions =
        target.mode === 'explain_article' ? [t('ai.suggest.article.1'), t('ai.suggest.article.2')] : (intro?.suggestions ?? []);

    return (
        <div className="fixed inset-0 z-50 flex justify-end" role="dialog" aria-modal="true" aria-labelledby="ai-panel-title">
            <button
                type="button"
                aria-label={t('ai.close')}
                onClick={onClose}
                className="absolute inset-0 animate-fade-in cursor-default bg-brand-deep/40 backdrop-blur-[2px]"
            />

            <section className="relative flex h-full w-full animate-fade-in flex-col bg-surface shadow-lift sm:w-[28rem]">
                <header className="flex items-center justify-between gap-3 border-b border-line bg-surface-raised px-5 py-4">
                    <div className="flex min-w-0 items-center gap-3">
                        <span className="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-deep text-mint">
                            <MessagesSquare aria-hidden="true" className="size-5" />
                        </span>
                        <div className="min-w-0">
                            <h2 id="ai-panel-title" className="font-display text-lg font-semibold text-ink">
                                {t('ai.panel_title')}
                            </h2>
                            {target.title !== undefined ? (
                                <p className="truncate text-xs text-ink-subtle">
                                    {t('ai.mode_article', { title: target.title })}
                                </p>
                            ) : null}
                        </div>
                    </div>
                    <div className="flex items-center gap-1">
                        {/* Quick exit stays reachable while the panel covers the page (FR-005). */}
                        <QuickExitButton />
                        {messages.length > 0 ? (
                            <button
                                type="button"
                                onClick={() => setMessages([])}
                                aria-label={t('ai.clear')}
                                title={t('ai.clear')}
                                className="inline-flex size-10 cursor-pointer items-center justify-center rounded-full text-ink-muted hover:bg-surface-sunken"
                            >
                                <Eraser aria-hidden="true" className="size-4" />
                            </button>
                        ) : null}
                        <button
                            type="button"
                            onClick={onClose}
                            aria-label={t('ai.close')}
                            className="inline-flex size-10 cursor-pointer items-center justify-center rounded-full text-ink-muted hover:bg-surface-sunken"
                        >
                            <X aria-hidden="true" className="size-5" />
                        </button>
                    </div>
                </header>

                {!gates.ageConfirmed ? (
                    <div className="flex flex-1 flex-col items-center justify-center gap-5 p-8 text-center">
                        <p className="text-ink">{t('gate.age.why')}</p>
                        <Link href={links.ageGate} className={buttonClasses('primary')}>
                            {t('gate.age.question')}
                        </Link>
                    </div>
                ) : (
                    <>
                        <div ref={list} className="flex-1 space-y-4 overflow-y-auto px-5 py-5" aria-live="polite">
                            <div className="rounded-2xl bg-brand-soft p-4 text-sm text-ink">
                                {intro?.welcome ?? t('ai.welcome')}
                            </div>

                            {messages.length === 0 && suggestions.length > 0 ? (
                                <div>
                                    <p className="mb-2 text-xs font-semibold text-ink-subtle">{t('ai.suggestions_label')}</p>
                                    <div className="flex flex-wrap gap-2">
                                        {suggestions.map((suggestion, index) => (
                                            <button
                                                key={suggestion}
                                                type="button"
                                                onClick={() =>
                                                    void send(
                                                        suggestion,
                                                        target.mode === 'explain_article'
                                                            ? index === 0
                                                                ? 'summarize'
                                                                : 'simplify'
                                                            : undefined,
                                                    )
                                                }
                                                className="cursor-pointer rounded-full border border-line-strong bg-surface-raised px-3.5 py-2 text-start text-sm text-ink transition-colors hover:border-brand hover:text-brand-strong"
                                            >
                                                {suggestion}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            ) : null}

                            {messages.map((message) => (
                                <ChatMessage key={message.id} message={message} disclaimer={disclaimer} />
                            ))}

                            {busy ? (
                                <p className="flex items-center gap-2 text-sm text-ink-muted">
                                    <Spinner className="size-4 text-brand" />
                                    {t('ai.thinking')}
                                </p>
                            ) : null}
                        </div>

                        <form onSubmit={submit} className="border-t border-line bg-surface-raised px-5 pt-3 pb-4">
                            <p className="mb-2 flex items-center gap-1.5 text-xs font-medium text-warning">
                                <ShieldAlert aria-hidden="true" className="size-3.5 shrink-0" />
                                {t('ai.input_warning')}
                            </p>
                            <label htmlFor="ai-message" className="sr-only">
                                {t('ai.input_label')}
                            </label>
                            <textarea
                                id="ai-message"
                                ref={input}
                                rows={2}
                                maxLength={MAX_CHARS}
                                value={draft}
                                onChange={(event) => setDraft(event.target.value)}
                                onKeyDown={(event) => {
                                    if (event.key === 'Enter' && !event.shiftKey) {
                                        event.preventDefault();
                                        void send(draft);
                                    }
                                }}
                                placeholder={t('ai.input_placeholder')}
                                dir="auto"
                                className={`${inputClasses} resize-none rounded-2xl`}
                            />
                            <div className="mt-2 flex items-center justify-between gap-3">
                                <div className="flex items-center gap-2 text-xs text-ink-subtle">
                                    <label htmlFor="ai-simplification">{t('ai.simplification')}</label>
                                    <select
                                        id="ai-simplification"
                                        value={simplification}
                                        onChange={(event) => setSimplification(Number(event.target.value))}
                                        className="cursor-pointer rounded-full border border-line bg-surface px-2 py-1 text-xs text-ink"
                                    >
                                        {[1, 2, 3].map((level) => (
                                            <option key={level} value={level}>
                                                {t(`ai.simplification_${level}`)}
                                            </option>
                                        ))}
                                    </select>
                                    <span aria-hidden="true">
                                        <bdi dir="ltr">{t('ai.char_count', { count: draft.length, max: MAX_CHARS })}</bdi>
                                    </span>
                                </div>
                                {busy ? (
                                    <button
                                        type="button"
                                        onClick={() => controller.current?.abort('stop')}
                                        className={buttonClasses('secondary', 'sm')}
                                    >
                                        <Square aria-hidden="true" className="size-3.5" />
                                        {t('ai.stop')}
                                    </button>
                                ) : (
                                    <button
                                        type="submit"
                                        disabled={draft.trim().length < 2}
                                        className={buttonClasses('primary', 'sm')}
                                    >
                                        <Send aria-hidden="true" className="size-3.5 rtl:-scale-x-100" />
                                        {t('ai.send')}
                                    </button>
                                )}
                            </div>
                        </form>
                    </>
                )}
            </section>

            <AiNotice
                open={noticeOpen}
                onCancel={() => {
                    setNoticeOpen(false);
                    setPending(null);
                }}
                onAcknowledged={() => {
                    setNoticeOpen(false);
                    setNoticeAccepted(true);

                    if (pending !== null) {
                        const queued = pending;
                        setPending(null);
                        // The notice is now acknowledged on the server; send the queued question.
                        void send(queued.text, queued.preset, true);
                    }
                }}
            />
        </div>
    );
}

function ChatMessage({ message, disclaimer }: { message: Message; disclaimer: string | null }) {
    const t = useT();

    if (message.role === 'user') {
        return (
            <div className="flex justify-end">
                <p
                    dir="auto"
                    className="max-w-[85%] rounded-2xl rounded-ee-md bg-brand px-4 py-2.5 text-sm whitespace-pre-line text-brand-ink"
                >
                    {message.text}
                </p>
            </div>
        );
    }

    const reply = message.reply;

    if (reply?.kind === 'emergency') {
        return (
            <div role="alert" className="rounded-2xl border-s-4 border-danger bg-danger-soft p-4">
                <p className="flex items-center gap-2 font-semibold text-danger">
                    <ShieldAlert aria-hidden="true" className="size-4" />
                    {t('ai.emergency_title')}
                </p>
                <p className="mt-2 text-sm text-ink">{reply.text}</p>
                <p className="mt-2 text-sm font-semibold text-danger">{t('emergency.call_local')}</p>
                {reply.action !== null ? (
                    <Link href={reply.action.url} className={buttonClasses('destructive', 'sm', 'mt-3')}>
                        {reply.action.label}
                        <ArrowRight aria-hidden="true" className="size-3.5 rtl:-scale-x-100" />
                    </Link>
                ) : null}
            </div>
        );
    }

    return (
        <div className="max-w-[92%]">
            <div
                className={`rounded-2xl rounded-es-md px-4 py-3 text-sm ${message.status === 'error' ? 'bg-surface-sunken text-ink-muted' : 'bg-surface-raised text-ink shadow-card'}`}
            >
                <p dir="auto" className="whitespace-pre-line">
                    {message.text}
                </p>
                {reply !== undefined && reply.citations.length > 0 ? (
                    <div className="mt-3 border-t border-line pt-3">
                        <p className="text-xs font-semibold text-ink-subtle">{t('ai.sources')}</p>
                        <ul className="mt-1.5 space-y-1">
                            {reply.citations.map((citation) => (
                                <li key={citation.url}>
                                    <Link
                                        href={citation.url}
                                        className="text-sm font-medium text-brand underline-offset-4 hover:underline"
                                    >
                                        {citation.title}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                ) : null}
                {reply?.action != null ? (
                    <Link href={reply.action.url} className={buttonClasses('secondary', 'sm', 'mt-3')}>
                        {reply.action.label}
                    </Link>
                ) : null}
            </div>
            {reply !== undefined && disclaimer !== null ? (
                <p className="mt-1.5 px-1 text-xs text-ink-subtle">{disclaimer}</p>
            ) : null}
        </div>
    );
}
