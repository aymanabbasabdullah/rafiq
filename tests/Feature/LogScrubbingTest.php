<?php

namespace Tests\Feature;

use App\Logging\ScrubSensitiveData;
use DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use Tests\TestCase;

class LogScrubbingTest extends TestCase
{
    public function test_sensitive_values_are_removed_from_log_context(): void
    {
        $record = new LogRecord(
            datetime: new DateTimeImmutable,
            channel: 'testing',
            level: Level::Error,
            message: 'Evaluation failed',
            context: [
                'answers' => ['ss_sudden_severe_pain' => 'yes'],
                'request' => ['Message' => 'I have a lump', 'locale' => 'en'],
                'journey' => 'concern',
            ],
            extra: ['password' => 'secret-value'],
        );

        $scrubbed = (new ScrubSensitiveData)->scrubRecord($record);

        $this->assertSame(ScrubSensitiveData::REDACTED, $scrubbed->context['answers']);
        $this->assertSame(ScrubSensitiveData::REDACTED, $scrubbed->context['request']['Message']);
        $this->assertSame('en', $scrubbed->context['request']['locale']);
        $this->assertSame('concern', $scrubbed->context['journey']);
        $this->assertSame(ScrubSensitiveData::REDACTED, $scrubbed->extra['password']);
    }

    public function test_file_log_channels_apply_the_scrubber(): void
    {
        $this->assertContains(ScrubSensitiveData::class, config('logging.channels.single.tap'));
        $this->assertContains(ScrubSensitiveData::class, config('logging.channels.daily.tap'));
    }
}
