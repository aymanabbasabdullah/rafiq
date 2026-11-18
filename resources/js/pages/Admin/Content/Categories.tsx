import { Head, useForm } from '@inertiajs/react';
import { FolderTree, Plus, Save } from 'lucide-react';
import type { FormEvent } from 'react';
import { CategoryIcon } from '@/components/learn/CategoryIcon';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { EmptyState } from '@/components/ui/EmptyState';
import { Field, TextInput } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { AdminLayout } from '@/layouts/AdminLayout';
import { useT } from '@/lib/i18n';

type LocaleKey = 'ar' | 'en';
type CategoryTranslation = { name: string; slug: string; description: string };

type CategoryRow = {
    id: number;
    key: string;
    icon: string;
    sort_order: number;
    is_visible: boolean;
    ai_enabled: boolean;
    articlesCount: number;
    translations: Record<LocaleKey, CategoryTranslation>;
    updateUrl: string;
};

type CategoriesProps = {
    categories: CategoryRow[];
    icons: string[];
    storeUrl: string;
    canManage: boolean;
};

type CategoryForm = {
    key: string;
    icon: string;
    sort_order: number;
    is_visible: boolean;
    ai_enabled: boolean;
    translations: Record<LocaleKey, CategoryTranslation>;
};

const LOCALES: LocaleKey[] = ['ar', 'en'];
const EMPTY: CategoryTranslation = { name: '', slug: '', description: '' };

function CategoryFields({
    form,
    icons,
    prefix,
    isNew,
}: {
    form: ReturnType<typeof useForm<CategoryForm>>;
    icons: string[];
    prefix: string;
    isNew: boolean;
}) {
    const t = useT();
    const { data, setData, errors } = form;
    const error = (path: string) => (errors as Record<string, string | undefined>)[path];
    const setTranslation = (locale: LocaleKey, field: keyof CategoryTranslation, value: string) =>
        setData('translations', { ...data.translations, [locale]: { ...data.translations[locale], [field]: value } });

    return (
        <div className="space-y-4">
            {isNew ? (
                <Field
                    id={`${prefix}-key`}
                    label={t('admin.content.field.key')}
                    help={t('admin.content.field.key_help')}
                    error={errors.key}
                >
                    <TextInput
                        id={`${prefix}-key`}
                        dir="ltr"
                        value={data.key}
                        onChange={(event) => setData('key', event.target.value.toLowerCase())}
                    />
                </Field>
            ) : null}

            <fieldset>
                <legend className="text-sm font-medium text-ink">{t('admin.categories.icon')}</legend>
                <div className="mt-2 flex flex-wrap gap-2">
                    {icons.map((icon) => (
                        <label
                            key={icon}
                            className={`inline-flex size-11 cursor-pointer items-center justify-center rounded-xl border transition-colors has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand ${
                                data.icon === icon
                                    ? 'border-brand bg-brand-soft text-brand'
                                    : 'border-line text-ink-muted hover:border-line-strong'
                            }`}
                        >
                            <input
                                type="radio"
                                name={`${prefix}-icon`}
                                value={icon}
                                checked={data.icon === icon}
                                onChange={() => setData('icon', icon)}
                                className="sr-only"
                            />
                            <CategoryIcon icon={icon} />
                            <span className="sr-only">{icon}</span>
                        </label>
                    ))}
                </div>
            </fieldset>

            <div className="grid gap-4 md:grid-cols-2">
                {LOCALES.map((locale) => (
                    <div key={locale} className="space-y-3 rounded-xl bg-surface p-4" dir={locale === 'ar' ? 'rtl' : 'ltr'}>
                        <p className="text-xs font-bold text-ink-subtle uppercase">{t(`admin.locale.${locale}`)}</p>
                        <Field
                            id={`${prefix}-${locale}-name`}
                            label={t('admin.categories.name')}
                            error={error(`translations.${locale}.name`)}
                        >
                            <TextInput
                                id={`${prefix}-${locale}-name`}
                                value={data.translations[locale].name}
                                onChange={(event) => setTranslation(locale, 'name', event.target.value)}
                            />
                        </Field>
                        <Field
                            id={`${prefix}-${locale}-slug`}
                            label={t('admin.content.field.slug')}
                            error={error(`translations.${locale}.slug`)}
                        >
                            <TextInput
                                id={`${prefix}-${locale}-slug`}
                                dir="ltr"
                                value={data.translations[locale].slug}
                                onChange={(event) => setTranslation(locale, 'slug', event.target.value)}
                            />
                        </Field>
                        <Field
                            id={`${prefix}-${locale}-description`}
                            label={t('admin.categories.description')}
                            error={error(`translations.${locale}.description`)}
                        >
                            <TextInput
                                id={`${prefix}-${locale}-description`}
                                value={data.translations[locale].description}
                                onChange={(event) => setTranslation(locale, 'description', event.target.value)}
                            />
                        </Field>
                    </div>
                ))}
            </div>

            <div className="flex flex-wrap gap-5 text-sm text-ink">
                <label className="flex cursor-pointer items-center gap-2">
                    <input
                        type="checkbox"
                        checked={data.is_visible}
                        onChange={(event) => setData('is_visible', event.target.checked)}
                        className="size-4 accent-brand"
                    />
                    {t('admin.categories.visible')}
                </label>
                <label className="flex cursor-pointer items-center gap-2">
                    <input
                        type="checkbox"
                        checked={data.ai_enabled}
                        onChange={(event) => setData('ai_enabled', event.target.checked)}
                        className="size-4 accent-brand"
                    />
                    {t('admin.content.field.ai_enabled')}
                </label>
            </div>
        </div>
    );
}

function CategoryItem({ category, icons, canManage }: { category: CategoryRow; icons: string[]; canManage: boolean }) {
    const t = useT();
    const form = useForm<CategoryForm>({
        key: category.key,
        icon: category.icon,
        sort_order: category.sort_order,
        is_visible: category.is_visible,
        ai_enabled: category.ai_enabled,
        translations: category.translations,
    });

    const save = (event: FormEvent) => {
        event.preventDefault();
        form.put(category.updateUrl, { preserveScroll: true });
    };

    return (
        <Card as="article" className="p-5">
            <div className="flex flex-wrap items-center gap-4">
                <span className="inline-flex size-12 items-center justify-center rounded-2xl bg-brand-soft text-brand">
                    <CategoryIcon icon={category.icon} className="size-6" />
                </span>
                <div className="min-w-0 flex-1">
                    <p className="font-semibold text-ink">
                        {category.translations.ar.name}
                        <span aria-hidden="true" className="mx-2 inline-block size-1 rounded-full bg-ink-subtle align-middle" />
                        <span dir="ltr">{category.translations.en.name}</span>
                    </p>
                    <code dir="ltr" className="text-xs text-ink-subtle">
                        {category.key}
                    </code>
                </div>
                <div className="flex flex-wrap gap-1.5">
                    <Badge>{t('learn.article_count', { count: category.articlesCount })}</Badge>
                    {category.is_visible ? null : <Badge tone="warning">{t('admin.categories.hidden')}</Badge>}
                </div>
            </div>
            {canManage ? (
                <details className="mt-4">
                    <summary className="cursor-pointer text-sm font-medium text-brand">{t('admin.content.edit')}</summary>
                    <form onSubmit={save} className="mt-4 space-y-4" noValidate>
                        <CategoryFields form={form} icons={icons} prefix={`category-${category.id}`} isNew={false} />
                        <Button type="submit" size="sm" disabled={form.processing || !form.isDirty}>
                            <Save aria-hidden="true" className="size-4" />
                            {t('admin.content.save')}
                        </Button>
                    </form>
                </details>
            ) : null}
        </Card>
    );
}

/**
 * Knowledge Center topics (M11B-FR-01).
 */
export default function Categories({ categories, icons, storeUrl, canManage }: CategoriesProps) {
    const t = useT();
    const form = useForm<CategoryForm>({
        key: '',
        icon: icons[0] ?? 'book-open',
        sort_order: 0,
        is_visible: true,
        ai_enabled: true,
        translations: { ar: { ...EMPTY }, en: { ...EMPTY } },
    });

    const create = (event: FormEvent) => {
        event.preventDefault();
        form.post(storeUrl, { preserveScroll: true, onSuccess: () => form.reset() });
    };

    return (
        <AdminLayout>
            <Head title={t('admin.categories.title')} />
            <div className="space-y-8">
                <div className="animate-fade-up">
                    <PageHeader title={t('admin.categories.title')} description={t('admin.categories.subtitle')} />
                </div>

                {categories.length === 0 ? (
                    <EmptyState icon={FolderTree} title={t('admin.categories.empty')} />
                ) : (
                    <div className="stagger space-y-4">
                        {categories.map((category) => (
                            <CategoryItem key={category.id} category={category} icons={icons} canManage={canManage} />
                        ))}
                    </div>
                )}

                {canManage ? (
                    <Card as="section" className="p-5">
                        <h2 className="flex items-center gap-2 font-semibold text-ink">
                            <Plus aria-hidden="true" className="size-5 text-brand" />
                            {t('admin.categories.add')}
                        </h2>
                        <form onSubmit={create} className="mt-5 space-y-4" noValidate>
                            <CategoryFields form={form} icons={icons} prefix="new-category" isNew />
                            <Button type="submit" disabled={form.processing}>
                                {t('admin.categories.add')}
                            </Button>
                        </form>
                    </Card>
                ) : null}
            </div>
        </AdminLayout>
    );
}
