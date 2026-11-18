<?php

namespace Tests\Feature;

use App\Services\Ai\FakeAiTextGenerator;
use App\Services\Ai\Gemini\GeminiConnectionCheck;
use App\Services\Ai\Gemini\GeminiTextGenerator;
use App\Support\BootChecks;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

/**
 * The live connection check (`rafiq:ai:ping`) uses the assistant's own Gemini generator and endpoint.
 * Http is faked here; the real request is made only by running the command by hand.
 */
class GeminiConnectionCheckTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    private function check(string $key = 'secret-test-key', ?string $model = 'configured-model'): GeminiConnectionCheck
    {
        return new GeminiConnectionCheck(new GeminiTextGenerator($key, $model, 'https://gemini.test', 20));
    }

    private function answer(string $text): array
    {
        return ['candidates' => [['content' => ['parts' => [['text' => json_encode(['answer' => $text, 'cited_chunk_ids' => [], 'out_of_scope' => false, 'personal_case' => false])]]], 'finishReason' => 'STOP']]];
    }

    public function test_it_uses_the_assistants_generate_content_endpoint_and_configured_model(): void
    {
        Http::fake(['https://gemini.test/v1beta/models/configured-model:generateContent' => Http::response($this->answer('Rafiq Gemini connection successful.'))]);

        $result = $this->check()->run();

        $this->assertTrue($result['ok']);
        $this->assertTrue($result['matches']);
        $this->assertSame('gemini', $result['provider']);
        $this->assertSame('configured-model', $result['model']);
        Http::assertSent(fn (Request $request): bool => $request->hasHeader('x-goog-api-key', 'secret-test-key')
            && str_contains(json_encode($request->data()), GeminiConnectionCheck::PROMPT)
            && ! array_key_exists('tools', $request->data()));
        $this->assertStringNotContainsString('secret-test-key', (string) json_encode($result));
    }

    public function test_google_errors_report_the_status_and_message_without_the_key(): void
    {
        Http::fake(['https://gemini.test/*' => Http::response(['error' => ['code' => 503, 'message' => 'The model is overloaded.']], 503)]);

        $result = $this->check()->run();

        $this->assertFalse($result['ok']);
        $this->assertSame(503, $result['status']);
        $this->assertStringContainsString('server_error: The model is overloaded.', (string) $result['error']);
        $this->assertStringNotContainsString('secret-test-key', (string) json_encode($result));
    }

    public function test_missing_configuration_is_reported_without_any_request(): void
    {
        $this->assertStringContainsString('not_configured', (string) $this->check(key: '')->run()['error']);
        $this->assertStringContainsString('not_configured', (string) $this->check(model: '')->run()['error']);

        Http::assertNothingSent();
    }

    public function test_it_refuses_to_report_a_fake_answer_as_a_live_one(): void
    {
        $result = (new GeminiConnectionCheck(new FakeAiTextGenerator))->run();

        $this->assertFalse($result['ok']);
        $this->assertSame('fake', $result['provider']);
        Http::assertNothingSent();
    }

    public function test_the_fake_provider_is_refused_in_production_and_unknown_providers_everywhere(): void
    {
        BootChecks::assertSafeConfiguration('local', false, false, 'fake');
        BootChecks::assertSafeConfiguration('production', false, false, 'gemini');

        try {
            BootChecks::assertSafeConfiguration('production', false, false, 'fake');
            $this->fail('The fake provider booted in production.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('RAFIQ_AI_PROVIDER', $exception->getMessage());
        }

        $this->expectException(RuntimeException::class);
        BootChecks::assertSafeConfiguration('local', false, false, 'openai');
    }
}
