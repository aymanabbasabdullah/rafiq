<?php

namespace Tests\Feature;

use App\Models\AiUsageLog;
use App\Models\Article;
use App\Models\ContentCategory;
use App\Models\ContentChunk;
use App\Models\Source;
use App\Models\User;
use App\Services\Ai\Gemini\GeminiTextGenerator;
use App\Services\Content\ArticleWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Module-09: gates, guardrails and the Gemini call, with Http faked and stray requests forbidden.
 */
class AiCompanionTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/en/ai/messages';

    private const GEMINI = 'https://gemini.test/*';

    private Article $article;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config([
            'rafiq.ai.enabled' => true,
            'rafiq.ai.provider' => 'gemini',
            'services.gemini.api_key' => 'test-key',
            'services.gemini.model' => 'gemini-test',
            'services.gemini.base_url' => 'https://gemini.test',
        ]);

        $this->article = $this->publishedArticle('Testicular pain', "## When to act\n\nSudden testicular pain needs urgent hospital care. Pain with vomiting also needs urgent care.");
        $this->withSession(['age_confirmed' => true, 'ai_notice_ack' => true]);
    }

    private function publishedArticle(string $title, string $body, bool $restricted = false): Article
    {
        $workflow = app(ArticleWorkflow::class);
        $editor = User::factory()->contentEditor()->create();
        $reviewer = User::factory()->medicalReviewer()->create();
        $category = ContentCategory::factory()->translated()->create(['ai_enabled' => true]);
        $article = Article::factory()->create(['content_category_id' => $category->id, 'ai_enabled' => true]);
        $article->sources()->attach(Source::factory()->create(['ai_use_restricted' => $restricted])->id, ['support_level' => 'direct']);

        $translation = $workflow->saveDraft($article, 'en', ['title' => $title, 'slug' => str()->slug($title).'-'.$article->id, 'summary' => null, 'body_markdown' => $body], $editor);
        $workflow->submit($translation, $editor);
        $workflow->approve($translation->refresh(), $reviewer);
        $workflow->publish($translation->refresh(), $editor);

        return $article;
    }

    /**
     * @param  list<string>  $cited
     */
    private function geminiAnswer(string $answer, array $cited, bool $outOfScope = false): array
    {
        return [
            'candidates' => [['content' => ['parts' => [['text' => json_encode(['answer' => $answer, 'cited_chunk_ids' => $cited, 'out_of_scope' => $outOfScope, 'personal_case' => false])]]], 'finishReason' => 'STOP']],
            'usageMetadata' => ['promptTokenCount' => 120, 'candidatesTokenCount' => 40],
        ];
    }

    private function chunkId(): string
    {
        return 'c'.ContentChunk::query()->where('locale', 'en')->value('id');
    }

    public function test_messages_need_the_age_gate_and_the_ai_notice(): void
    {
        $this->flushSession();
        $this->postJson(self::URL, ['mode' => 'general_education', 'message' => 'What is testicular pain?'])->assertForbidden();

        $this->withSession(['age_confirmed' => true]);
        $this->postJson(self::URL, ['mode' => 'general_education', 'message' => 'What is testicular pain?'])
            ->assertStatus(409)->assertJson(['code' => 'ai_notice_required']);
    }

    public function test_the_assistant_can_be_switched_off(): void
    {
        config(['rafiq.ai.enabled' => false]);

        $this->postJson(self::URL, ['mode' => 'general_education', 'message' => 'What is testicular pain?'])
            ->assertStatus(503)->assertJson(['code' => 'ai_disabled']);
    }

    public function test_unexpected_fields_and_invalid_input_are_rejected(): void
    {
        $this->postJson(self::URL, ['mode' => 'general_education', 'message' => 'Hi there', 'answers' => ['ss_sudden_severe_pain' => 'yes']])
            ->assertStatus(422)->assertJsonValidationErrors('answers');
        $this->postJson(self::URL, ['mode' => 'diagnose', 'message' => 'Hi there'])->assertStatus(422);
        $this->postJson(self::URL, ['mode' => 'general_education', 'message' => str_repeat('a', 501)])->assertStatus(422);
        $this->postJson(self::URL, ['mode' => 'explain_article', 'message' => 'Summarise', 'article_id' => 999])->assertStatus(422)->assertJsonValidationErrors('article_id');
        $this->postJson(self::URL, ['mode' => 'general_education', 'message' => 'Hi there', 'history' => array_fill(0, 7, ['role' => 'user', 'text' => 'x'])])->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_warning_signs_get_the_emergency_reply_without_calling_the_model(): void
    {
        $this->postJson(self::URL, ['mode' => 'general_education', 'message' => 'I have sudden severe pain since this morning'])
            ->assertOk()
            ->assertJsonPath('reply.kind', 'emergency')
            ->assertJsonPath('reply.outcome', 'blocked_redflag')
            ->assertJsonPath('reply.text', __('ai.reply.redflag', [], 'en'));

        Http::assertNothingSent();
        $this->assertDatabaseHas('ai_usage_logs', ['outcome' => 'blocked_redflag', 'model' => null]);
    }

    public function test_personal_details_and_health_disclosures_never_reach_the_model(): void
    {
        $this->postJson(self::URL, ['mode' => 'general_education', 'message' => 'My email is me@example.com'])->assertJsonPath('reply.outcome', 'blocked_prefilter');
        $this->postJson(self::URL, ['mode' => 'general_education', 'message' => 'My sperm count was low'])->assertJsonPath('reply.outcome', 'refused_personal_case');
        $this->postJson(self::URL, ['mode' => 'general_education', 'message' => 'Is my semen analysis normal?'])->assertJsonPath('reply.outcome', 'blocked_prefilter');

        Http::assertNothingSent();
    }

    public function test_questions_without_approved_content_are_refused_without_calling_the_model(): void
    {
        $this->postJson(self::URL, ['mode' => 'general_education', 'message' => 'What is the capital of France?'])
            ->assertJsonPath('reply.outcome', 'refused_out_of_scope');

        Http::assertNothingSent();
    }

    public function test_content_from_ai_restricted_sources_is_never_sent(): void
    {
        $this->publishedArticle('Guideline summary', "## Varicocele\n\nVaricocele details from a restricted guideline.", restricted: true);

        $this->postJson(self::URL, ['mode' => 'general_education', 'message' => 'What is a varicocele?'])
            ->assertJsonPath('reply.outcome', 'refused_out_of_scope');

        Http::assertNothingSent();
    }

    public function test_a_grounded_answer_is_returned_with_citations_and_a_clean_payload(): void
    {
        Http::fake([self::GEMINI => Http::response($this->geminiAnswer('Sudden testicular pain needs urgent care in hospital.', [$this->chunkId()]))]);

        $this->postJson(self::URL, ['mode' => 'general_education', 'message' => 'When does testicular pain need urgent care?'])
            ->assertOk()
            ->assertJsonPath('reply.kind', 'answer')
            ->assertJsonPath('reply.text', 'Sudden testicular pain needs urgent care in hospital.')
            ->assertJsonPath('reply.citations.0.title', 'Testicular pain')
            ->assertJsonPath('disclaimer', __('ai.disclaimer', [], 'en'));

        Http::assertSent(function (Request $request): bool {
            $body = $request->data();
            $encoded = json_encode($body);

            return $request->hasHeader('x-goog-api-key', 'test-key')
                && str_contains($request->url(), '/v1beta/models/gemini-test:generateContent')
                && ! array_key_exists('tools', $body)
                && ! array_key_exists('cachedContent', $body)
                && ! array_key_exists('labels', $body)
                && str_contains($encoded, 'approved_content')
                && ! str_contains($encoded, '127.0.0.1')
                && ! str_contains($encoded, session()->getId());
        });

        $log = AiUsageLog::query()->sole();
        $this->assertSame('answered', $log->outcome->value);
        $this->assertSame(120, $log->input_tokens);
        $this->assertSame(['id', 'created_at', 'mode', 'locale', 'outcome', 'ai_configuration_version', 'core_prompt_version', 'model', 'input_tokens', 'output_tokens', 'latency_ms', 'error_code', 'estimated_cost_micros', 'article_id'], Schema::getColumnListing('ai_usage_logs'));
    }

    public function test_an_answer_with_an_invented_citation_is_replaced_by_the_fallback(): void
    {
        Http::fake([self::GEMINI => Http::response($this->geminiAnswer('Sudden testicular pain needs urgent care.', ['c999']))]);

        $this->postJson(self::URL, ['mode' => 'general_education', 'message' => 'When does testicular pain need urgent care?'])
            ->assertJsonPath('reply.outcome', 'blocked_postfilter')
            ->assertJsonPath('reply.text', __('ai.reply.fallback', [], 'en'));
    }

    public function test_rate_limits_and_server_errors_from_google_degrade_gracefully(): void
    {
        Http::fake([self::GEMINI => Http::response([], 429)]);

        $this->postJson(self::URL, ['mode' => 'general_education', 'message' => 'When does testicular pain need urgent care?'])
            ->assertJsonPath('reply.outcome', 'error')
            ->assertJsonPath('reply.citations.0.title', 'Testicular pain');
        $this->assertTrue(Cache::has(GeminiTextGenerator::BACKOFF_KEY));
        $this->assertDatabaseHas('ai_usage_logs', ['outcome' => 'error', 'error_code' => 'rate_limited']);

        Cache::forget(GeminiTextGenerator::BACKOFF_KEY);
        Http::fake([self::GEMINI => Http::response([], 503)]);
        $this->postJson(self::URL, ['mode' => 'general_education', 'message' => 'When does testicular pain need urgent care?'])->assertJsonPath('reply.outcome', 'error');
    }

    public function test_explain_article_uses_only_that_articles_chunks(): void
    {
        Http::fake([self::GEMINI => Http::response($this->geminiAnswer('Sudden testicular pain needs urgent hospital care.', [$this->chunkId()]))]);

        $this->postJson(self::URL, ['mode' => 'explain_article', 'message' => 'Summarise this article', 'article_id' => $this->article->id, 'preset' => 'summarize'])
            ->assertJsonPath('reply.outcome', 'answered');

        Http::assertSent(fn (Request $request): bool => str_contains(json_encode($request->data()), 'Summarise the approved content'));
        $this->assertDatabaseHas('ai_usage_logs', ['mode' => 'explain_article', 'article_id' => $this->article->id]);
        $this->assertDatabaseHas('daily_metrics', ['metric' => 'article_ai_explain']);
    }

    public function test_the_fake_provider_quotes_approved_content_when_chosen_explicitly(): void
    {
        config(['rafiq.ai.provider' => 'fake']);

        $this->postJson(self::URL, ['mode' => 'general_education', 'message' => 'When does testicular pain need urgent care?'])
            ->assertJsonPath('reply.outcome', 'answered')
            ->assertJsonPath('reply.citations.0.title', 'Testicular pain');

        Http::assertNothingSent();
    }

    public function test_gemini_without_a_key_fails_visibly_and_never_falls_back_to_the_fake(): void
    {
        config(['services.gemini.api_key' => null]);

        $this->postJson(self::URL, ['mode' => 'general_education', 'message' => 'When does testicular pain need urgent care?'])
            ->assertJsonPath('reply.outcome', 'error')
            ->assertJsonPath('reply.text', __('ai.reply.unavailable', [], 'en'));

        Http::assertNothingSent();
        $this->assertDatabaseHas('ai_usage_logs', ['outcome' => 'error', 'error_code' => 'not_configured', 'model' => 'gemini-test']);
    }

    public function test_the_api_key_never_reaches_a_page_a_reply_or_the_usage_log(): void
    {
        config(['services.gemini.api_key' => 'secret-test-key-123']);
        Http::fake([self::GEMINI => Http::response($this->geminiAnswer('Sudden testicular pain needs urgent care in hospital.', [$this->chunkId()]))]);

        $this->get('/en')->assertDontSee('secret-test-key-123', false);
        $this->get('/en/learn')->assertDontSee('secret-test-key-123', false);
        $this->postJson(self::URL, ['mode' => 'general_education', 'message' => 'When does testicular pain need urgent care?'])
            ->assertJsonPath('reply.outcome', 'answered')
            ->assertDontSee('secret-test-key-123', false);
        $this->assertStringNotContainsString('secret-test-key-123', (string) json_encode(AiUsageLog::query()->get()->toArray()));
    }

    public function test_browsing_pages_never_calls_the_model(): void
    {
        $this->get('/en')->assertOk();
        $this->get('/en/learn')->assertOk();

        Http::assertNothingSent();
    }

    public function test_suggestions_are_approved_texts_for_known_contexts_only(): void
    {
        $this->getJson('/en/ai/suggestions?context=learn')
            ->assertOk()
            ->assertJsonPath('welcome', __('ai.welcome', [], 'en'))
            ->assertJsonCount(3, 'suggestions');
        $this->getJson('/en/ai/suggestions?context=anything')->assertStatus(422);
    }
}
