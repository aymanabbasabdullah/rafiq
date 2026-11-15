<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ContentCategory;
use App\Models\Source;
use App\Models\User;
use App\Services\Content\ArticleWorkflow;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Exercises the real MySQL FULLTEXT (ngram) search path. InnoDB FULLTEXT indexes ignore
 * uncommitted rows, so this class commits data and truncates tables instead of using transactions.
 */
#[Group('mysql')]
class KnowledgeSearchMysqlTest extends TestCase
{
    use DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Runs only against MySQL.');
        }
    }

    private function publish(string $locale, string $title, string $slug, string $body): void
    {
        $workflow = app(ArticleWorkflow::class);
        $editor = User::factory()->contentEditor()->create();
        $reviewer = User::factory()->medicalReviewer()->create();
        $article = Article::factory()->create(['content_category_id' => ContentCategory::factory()->translated()->create()->id]);
        $article->sources()->attach(Source::factory()->create()->id, ['support_level' => 'direct']);

        $translation = $workflow->saveDraft($article, $locale, ['title' => $title, 'slug' => $slug, 'summary' => null, 'body_markdown' => $body], $editor);
        $workflow->submit($translation, $editor);
        $workflow->approve($translation->refresh(), $reviewer);
        $workflow->publish($translation->refresh(), $editor);
    }

    public function test_fulltext_search_finds_arabic_variants_and_english_words_containing_stopword_ngrams(): void
    {
        $this->publish('ar', 'ألم الخصية المفاجئ', 'الم-مفاجئ', 'إذا كان الألمُ شديداً في الخصيةِ فاذهب إلى الطوارئ.');
        $this->publish('en', 'Testicular pain', 'testicular-pain', 'Sudden severe pain needs emergency care.');
        $this->publish('en', 'Smoking', 'smoking', 'Smoking is linked to poorer sperm DNA quality.');

        $this->withSession(['age_confirmed' => true]);

        $this->postJson('/ar/learn/search', ['query' => 'الخصيه'])->assertJsonCount(1, 'results');
        // "pain" contains the ngram "in", a default InnoDB stopword: must still match.
        $this->postJson('/en/learn/search', ['query' => 'pain'])->assertJsonCount(1, 'results')->assertJsonPath('results.0.title', 'Testicular pain');
        $this->postJson('/en/learn/search', ['query' => 'emergency pain'])->assertJsonCount(1, 'results');
        $this->postJson('/en/learn/search', ['query' => 'smoking'])->assertJsonCount(1, 'results')->assertJsonPath('results.0.title', 'Smoking');
        $this->postJson('/en/learn/search', ['query' => 'xyzq'])->assertJsonCount(0, 'results');
    }
}
