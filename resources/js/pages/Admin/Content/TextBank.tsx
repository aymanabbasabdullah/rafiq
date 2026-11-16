import { Head, router, usePage } from '@inertiajs/react';
import { BookA, CheckCircle2, Eye, EyeOff, Filter, MessageSquareWarning, Pencil, Send, Undo2, X } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { PublishBadge, ReviewBadge } from '@/components/admin/ContentStatus';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { EmptyState } from '@/components/ui/EmptyState';
import { Field, SelectInput, TextInput, inputClasses } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { AdminLayout } from '@/layouts/AdminLayout';
import { useT } from '@/lib/i18n';

interface BankRow {
    id: number;
    locale: string;
    key: string;
    group: string | null;
    primary: string;
    secondary: string | null;
    reviewStatus: string;
    publishStatus: string;
    reviewComment: string | null;
    approvedAt: string | null;
    canApprove: boolean;
    updateUrl: string;
    actionUrl: string;
}

interface TextBankProps {
    bank: 'doctor-questions' | 'glossary';
    rows: BankRow[];
    filters: { locale: string; status: string };
    filterUrl: string;
    canEdit: boolean;
    canPublish: boolean;
}

const STATUSES = ['draft', 'pending_medical_review', 'approved'];

/**
 * Doctor question bank and glossary review (M11B step 5): edit → submit → medical approval (four-eyes) → publish.
 * Editing an approved text returns it to draft, so only reviewed wording is shown outside demo mode.
 */
export default function TextBank({ bank, rows, filters, filterUrl, canEdit, canPublish }: TextBankProps) {
    const t = useT();
    const { errors } = usePage<{ errors: Record<string, string> }>().props;
    const [form, setForm] = useState(filters);
    const prefix = bank === 'glossary' ? 'admin.bank.glossary' : 'admin.bank.doctor';

    const submitFilter = (event: FormEvent) => {
        event.preventDefault();
        router.get(filterUrl, form, { preserveState: true, preserveScroll: true });
    };

    return (
        <AdminLayout>
            <Head title={t(`${prefix}.title`)} />
            <div className="space-y-6">
                <div className="animate-fade-up">
                    <PageHeader title={t(`${prefix}.title`)} description={t(`${prefix}.subtitle`)} />
                </div>

                <Alert tone="info" title={t('admin.bank.review_rule_title')}>
                    {t('admin.bank.review_rule_body')}
                </Alert>

                {errors.workflow !== undefined ? (
                    <Alert tone="danger" role="alert">
                        {errors.workflow}
                    </Alert>
                ) : null}

                <Card as="section" className="p-5">
                    <form onSubmit={submitFilter} className="grid gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                        <Field id="bank-locale" label={t('admin.bank.filter_locale')}>
                            <SelectInput
                                id="bank-locale"
                                value={form.locale}
                                onChange={(e) => setForm({ ...form, locale: e.target.value })}
                            >
                                <option value="">{t('admin.bank.all')}</option>
                                <option value="ar">{t('admin.analytics.segment.ar')}</option>
                                <option value="en">{t('admin.analytics.segment.en')}</option>
                            </SelectInput>
                        </Field>
                        <Field id="bank-status" label={t('admin.bank.filter_status')}>
                            <SelectInput
                                id="bank-status"
                                value={form.status}
                                onChange={(e) => setForm({ ...form, status: e.target.value })}
                            >
                                <option value="">{t('admin.bank.all')}</option>
                                {STATUSES.map((status) => (
                                    <option key={status} value={status}>
                                        {t(`admin.content.review.${status}`)}
                                    </option>
                                ))}
                            </SelectInput>
                        </Field>
                        <Button type="submit">
                            <Filter aria-hidden="true" className="size-4" />
                            {t('admin.audit.filter_submit')}
                        </Button>
                    </form>
                </Card>

                {rows.length === 0 ? (
                    <EmptyState icon={BookA} title={t('admin.bank.empty')} />
                ) : (
                    <ul className="space-y-3">
                        {rows.map((row) => (
                            <li key={row.id}>
                                <BankItem row={row} prefix={prefix} canEdit={canEdit} canPublish={canPublish} />
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </AdminLayout>
    );
}

function BankItem({ row, prefix, canEdit, canPublish }: { row: BankRow; prefix: string; canEdit: boolean; canPublish: boolean }) {
    const t = useT();
    const [editing, setEditing] = useState(false);
    const [primary, setPrimary] = useState(row.primary);
    const [secondary, setSecondary] = useState(row.secondary ?? '');
    const [rejecting, setRejecting] = useState(false);
    const [comment, setComment] = useState('');
    const pending = row.reviewStatus === 'pending_medical_review';

    const act = (action: string, data: Record<string, string> = {}) =>
        router.post(row.actionUrl.replace('__action__', action), data, {
            preserveScroll: true,
            onSuccess: () => setRejecting(false),
        });

    const save = (event: FormEvent) => {
        event.preventDefault();
        router.put(row.updateUrl, { primary, secondary }, { preserveScroll: true, onSuccess: () => setEditing(false) });
    };

    return (
        <Card className="p-5">
            <div className="flex flex-wrap items-center gap-2 text-xs">
                <Badge tone="neutral">{row.locale.toUpperCase()}</Badge>
                {row.group !== null ? <Badge tone="info">{row.group}</Badge> : null}
                <ReviewBadge status={row.reviewStatus} />
                <PublishBadge
                    status={row.publishStatus}
                    live={row.publishStatus === 'published' && row.reviewStatus === 'approved'}
                />
                <code className="text-ink-subtle" dir="ltr">
                    {row.key}
                </code>
            </div>

            {editing ? (
                <form onSubmit={save} className="mt-4 space-y-3">
                    <Field id={`primary-${row.id}`} label={t(`${prefix}.primary`)}>
                        <TextInput
                            id={`primary-${row.id}`}
                            value={primary}
                            onChange={(e) => setPrimary(e.target.value)}
                            dir="auto"
                        />
                    </Field>
                    <Field id={`secondary-${row.id}`} label={t(`${prefix}.secondary`)}>
                        <textarea
                            id={`secondary-${row.id}`}
                            className={`${inputClasses} min-h-24`}
                            value={secondary}
                            onChange={(e) => setSecondary(e.target.value)}
                            dir="auto"
                        />
                    </Field>
                    <p className="text-xs text-warning">{t('admin.bank.edit_resets')}</p>
                    <div className="flex gap-2">
                        <Button type="submit" size="sm">
                            {t('admin.bank.save')}
                        </Button>
                        <Button size="sm" variant="ghost" onClick={() => setEditing(false)}>
                            <X aria-hidden="true" className="size-4" />
                            {t('clear.cancel')}
                        </Button>
                    </div>
                </form>
            ) : (
                <div className="mt-3">
                    <p className="font-medium text-ink" dir="auto">
                        {row.primary}
                    </p>
                    {row.secondary !== null && row.secondary !== '' ? (
                        <p className="mt-1 text-sm text-ink-muted" dir="auto">
                            {row.secondary}
                        </p>
                    ) : null}
                </div>
            )}

            {row.reviewComment !== null ? (
                <p className="mt-3 flex items-start gap-2 rounded-xl bg-warning-soft p-3 text-sm text-ink">
                    <MessageSquareWarning aria-hidden="true" className="mt-0.5 size-4 shrink-0 text-warning" />
                    {row.reviewComment}
                </p>
            ) : null}

            {rejecting ? (
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        act('reject', { comment });
                    }}
                    className="mt-3 space-y-2"
                >
                    <Field id={`comment-${row.id}`} label={t('admin.bank.reject_comment')}>
                        <textarea
                            id={`comment-${row.id}`}
                            required
                            className={`${inputClasses} min-h-20`}
                            value={comment}
                            onChange={(e) => setComment(e.target.value)}
                        />
                    </Field>
                    <Button type="submit" size="sm" variant="danger">
                        {t('admin.bank.reject')}
                    </Button>
                </form>
            ) : null}

            {!editing ? (
                <div className="mt-4 flex flex-wrap gap-2 border-t border-line pt-4">
                    {canEdit && !pending ? (
                        <Button size="sm" variant="secondary" onClick={() => setEditing(true)}>
                            <Pencil aria-hidden="true" className="size-4" />
                            {t('admin.bank.edit')}
                        </Button>
                    ) : null}
                    {canEdit && row.reviewStatus === 'draft' ? (
                        <Button size="sm" variant="secondary" onClick={() => act('submit')}>
                            <Send aria-hidden="true" className="size-4 rtl:-scale-x-100" />
                            {t('admin.bank.submit')}
                        </Button>
                    ) : null}
                    {row.canApprove ? (
                        <>
                            <Button size="sm" onClick={() => act('approve')}>
                                <CheckCircle2 aria-hidden="true" className="size-4" />
                                {t('admin.bank.approve')}
                            </Button>
                            <Button size="sm" variant="ghost" onClick={() => setRejecting(!rejecting)}>
                                <Undo2 aria-hidden="true" className="size-4" />
                                {t('admin.bank.return')}
                            </Button>
                        </>
                    ) : null}
                    {canPublish && row.reviewStatus === 'approved' && row.publishStatus !== 'published' ? (
                        <Button size="sm" variant="secondary" onClick={() => act('publish')}>
                            <Eye aria-hidden="true" className="size-4" />
                            {t('admin.bank.publish')}
                        </Button>
                    ) : null}
                    {canPublish && row.publishStatus === 'published' ? (
                        <Button size="sm" variant="ghost" onClick={() => act('hide')}>
                            <EyeOff aria-hidden="true" className="size-4" />
                            {t('admin.bank.hide')}
                        </Button>
                    ) : null}
                </div>
            ) : null}
        </Card>
    );
}
