<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ContentCategory;
use App\Models\Source;
use App\Models\User;
use App\Services\Content\ArticleWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Development-only Postman route: real provider, same gates and guardrails, assistant still off for visitors.
 */
class AiDevTestRouteTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/en/dev/ai/messages';

    private const QUESTION = 'Explain what an API is in one short sentence.';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config([
            'rafiq.ai.enabled' => false,
            'rafiq.ai.provider' => 'fake',
            'rafiq.ai.dev_test_route' => true,
            'services.gemini.api_key' => 'test-key',
            'services.gemini.model' => 'gemini-test',
            'services.gemini.base_url' => 'https://gemini.test',
        ]);

        $this->platformGuide();
        $this->withSession(['age_confirmed' => true, 'ai_notice_ack' => true]);
    }

    private function platformGuide(): void
    {
        $workflow = app(ArticleWorkflow::class);
        $editor = User::factory()->contentEditor()->create();
        $category = ContentCategory::factory()->translated()->create(['ai_enabled' => true]);
        $article = Article::factory()->platformGuide()->create(['content_category_id' => $category->id, 'ai_enabled' => true]);
        $article->sources()->attach(Source::factory()->create()->id, ['support_level' => 'direct']);

        $translation = $workflow->saveDraft($article, 'en', ['title' => 'How Rafiq works', 'slug' => 'how-rafiq-works', 'summary' => null, 'body_markdown' => "## Journeys\n\nRafiq offers four journeys and asks no identifying details."], $editor);
        $workflow->submit($translation, $editor);
        $workflow->approve($translation->refresh(), User::factory()->medicalReviewer()->create());
        $workflow->publish($translation->refresh(), $editor);
    }

    private function gemini(bool $outOfScope): array
    {
        return ['candidates' => [['content' => ['parts' => [['text' => json_encode(['answer' => '', 'cited_chunk_ids' => [], 'out_of_scope' => $outOfScope, 'personal_case' => false])]]], 'finishReason' => 'STOP']]];
    }

    public function test_it_is_off_unless_explicitly_enabled(): void
    {
        config(['rafiq.ai.dev_test_route' => false]);

        $this->postJson(self::URL, ['mode' => 'platform_guide', 'message' => self::QUESTION])->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_it_keeps_the_age_gate_and_the_ai_notice(): void
    {
        $this->flushSession();
        $this->postJson(self::URL, ['mode' => 'platform_guide', 'message' => self::QUESTION])->assertForbidden();

        $this->withSession(['age_confirmed' => true]);
        $this->postJson(self::URL, ['mode' => 'platform_guide', 'message' => self::QUESTION])->assertStatus(409);
        Http::assertNothingSent();
    }

    public function test_it_uses_the_real_provider_even_when_the_fake_is_configured_and_the_assistant_is_off(): void
    {
        Http::fake(['https://gemini.test/*' => Http::response($this->gemini(outOfScope: true))]);

        $this->postJson(self::URL, ['mode' => 'platform_guide', 'message' => self::QUESTION])
            ->assertOk()
            ->assertJsonPath('reply.outcome', 'refused_out_of_scope')
            ->assertJsonPath('meta.provider', 'gemini')
            ->assertJsonPath('meta.model', 'gemini-test')
            ->assertJsonPath('meta.model_called', true)
            ->assertJsonPath('meta.assistant_enabled_for_visitors', false)
            ->assertDontSee('test-key');

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'gemini-test:generateContent'));

        // The visitor endpoint stays switched off.
        $this->postJson('/en/ai/messages', ['mode' => 'platform_guide', 'message' => self::QUESTION])->assertStatus(503);
    }

    public function test_a_missing_key_fails_visibly_instead_of_using_the_fake(): void
    {
        config(['services.gemini.api_key' => null]);

        $this->postJson(self::URL, ['mode' => 'platform_guide', 'message' => self::QUESTION])
            ->assertJsonPath('reply.outcome', 'error')
            ->assertJsonPath('meta.model_called', false)
            ->assertJsonPath('meta.diagnostic', 'not_configured');

        Http::assertNothingSent();
    }

    public function test_guardrails_still_apply_and_only_platform_guide_is_accepted(): void
    {
        $this->postJson(self::URL, ['mode' => 'general_education', 'message' => self::QUESTION])->assertStatus(422);
        $this->postJson(self::URL, ['mode' => 'platform_guide', 'message' => 'I have sudden severe pain'])->assertJsonPath('reply.outcome', 'blocked_redflag');
        $this->postJson(self::URL, ['mode' => 'platform_guide', 'message' => 'My email is me@example.com'])->assertJsonPath('reply.outcome', 'blocked_prefilter');
        $this->postJson(self::URL, ['mode' => 'platform_guide', 'message' => self::QUESTION, 'answers' => ['x' => 'y']])->assertStatus(422);

        Http::assertNothingSent();
    }
}
