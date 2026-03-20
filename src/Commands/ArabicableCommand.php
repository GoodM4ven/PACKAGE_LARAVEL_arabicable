<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Commands;

use Illuminate\Console\Command;

final class ArabicableCommand extends Command
{
    public $signature = 'arabicable:install
        {--testing : Skip interactive prompts for automated setup}
        {--seed : Import configured dictionaries after migration}';

    public $description = 'Publish Arabicable resources and prepare the package tables.';

    public function handle(): int
    {
        $this->call('vendor:publish', [
            '--tag' => 'arabicable-config',
            '--force' => true,
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'arabicable-migrations',
            '--force' => true,
        ]);

        if ($this->option('testing') || $this->confirm('Run migrations now?', true)) {
            $this->call('migrate');
        }

        if ((bool) $this->option('seed')) {
            $this->call('arabicable:seed', ['--all' => true]);
        }

        $this->info('Arabicable installation completed.');

        return self::SUCCESS;
    }
}
