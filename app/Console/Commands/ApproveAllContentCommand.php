<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Database\Seeders\ApproveAllContentSeeder;
use Illuminate\Console\Command;

/**
 * Medically approves and publishes all articles, glossary terms, doctor visit templates,
 * clinical ruleset RS-2026.1, and activates the AI configuration.
 */
class ApproveAllContentCommand extends Command
{
    protected $signature = 'rafiq:approve-all';

    protected $description = 'Approve and publish all content, glossary terms, doctor prep templates, and clinical ruleset RS-2026.1';

    public function handle(ApproveAllContentSeeder $seeder): int
    {
        $this->call('db:seed', ['--class' => ApproveAllContentSeeder::class, '--force' => true]);
        $this->info('All content, glossary terms, doctor visit templates, clinical ruleset RS-2026.1, and AI configuration are now approved and published.');

        return self::SUCCESS;
    }
}
