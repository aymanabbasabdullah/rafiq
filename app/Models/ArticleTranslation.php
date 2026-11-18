<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PublishStatus;
use App\Enums\ReviewStatus;
use Database\Factories\ArticleTranslationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * The editable working copy of an article in one language, plus its review and publication state.
 *
 * Visitors never see this working copy directly: they see `publishedRevision` (Module-08 BR-08-3).
 *
 * @property int $id
 * @property int $article_id
 * @property string $locale
 * @property string $title
 * @property string $slug
 * @property string|null $summary
 * @property string $body_markdown
 * @property ReviewStatus $review_status
 * @property PublishStatus $publish_status
 * @property int $version
 * @property int|null $author_id
 * @property int|null $last_editor_id
 * @property int|null $medically_approved_by
 * @property Carbon|null $medically_approved_at
 * @property Carbon|null $review_due_at
 * @property string|null $review_comment
 * @property int|null $published_revision_id
 * @property Carbon|null $published_at
 * @property Carbon $updated_at
 */
#[Fillable([
    'article_id', 'locale', 'title', 'slug', 'summary', 'body_markdown', 'review_status', 'publish_status', 'version',
    'author_id', 'last_editor_id', 'medically_approved_by', 'medically_approved_at', 'review_due_at', 'review_comment',
    'published_revision_id', 'published_at',
])]
class ArticleTranslation extends Model
{
    /** @use HasFactory<ArticleTranslationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'review_status' => ReviewStatus::class,
            'publish_status' => PublishStatus::class,
            'version' => 'integer',
            'medically_approved_at' => 'datetime',
            'review_due_at' => 'date',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Article, $this>
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * @return HasMany<ArticleRevision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(ArticleRevision::class)->orderByDesc('version');
    }

    /**
     * @return BelongsTo<ArticleRevision, $this>
     */
    public function publishedRevision(): BelongsTo
    {
        return $this->belongsTo(ArticleRevision::class, 'published_revision_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'medically_approved_by');
    }

    /**
     * Translations visitors can read: published with an approved revision.
     *
     * @param  Builder<ArticleTranslation>  $query
     */
    public function scopeLive(Builder $query, string $locale): void
    {
        $query->where('locale', $locale)
            ->where('publish_status', PublishStatus::Published->value)
            ->whereNotNull('published_revision_id');
    }

    public function isLive(): bool
    {
        return $this->publish_status === PublishStatus::Published && $this->published_revision_id !== null;
    }

    public function latestRevision(): ?ArticleRevision
    {
        return $this->revisions()->first();
    }
}
