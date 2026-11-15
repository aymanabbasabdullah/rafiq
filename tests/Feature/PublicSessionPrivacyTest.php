<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PublicSessionPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_session_contains_only_allowed_keys(): void
    {
        $this->get('/', ['Accept-Language' => 'ar']);
        $this->get('/ar');
        $this->get('/en');

        $unexpectedKeys = array_diff(array_keys(session()->all()), config('rafiq.public_session_keys'));

        $this->assertSame([], array_values($unexpectedKeys));
    }

    public function test_file_session_store_records_no_ip_address_or_user_agent(): void
    {
        $sessionPath = storage_path('framework/testing/sessions');
        File::ensureDirectoryExists($sessionPath);
        File::cleanDirectory($sessionPath);

        config([
            'session.driver' => 'file',
            'session.files' => $sessionPath,
            'session.encrypt' => false,
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.77'])
            ->get('/ar', ['User-Agent' => 'RafiqPrivacyProbe/1.0'])
            ->assertOk();

        $files = File::files($sessionPath);
        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $contents = File::get($file->getPathname());

            $this->assertStringNotContainsString('203.0.113.77', $contents);
            $this->assertStringNotContainsString('RafiqPrivacyProbe', $contents);
        }

        File::deleteDirectory($sessionPath);
    }
}
