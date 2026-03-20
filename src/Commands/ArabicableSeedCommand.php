<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Commands;

use GoodMaven\Arabicable\Database\Seeders\ImportArabicCommonTextsSeeder;
use GoodMaven\Arabicable\Database\Seeders\ImportArabicStopWordsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ArabicableSeedCommand extends Command
{
    public $signature = 'arabicable:seed
        {--all : Import all configured datasets}
        {--common-texts : Import common/connectors dataset}
        {--stop-words : Import stop words dataset}
        {--truncate : Truncate destination tables before importing}';

    public $description = 'Import Arabic dictionaries from configured local data sources with chunked upserts.';

    public function handle(): int
    {
        $importAll = (bool) $this->option('all');

        $importCommonTexts = $importAll || (bool) $this->option('common-texts');
        $importStopWords = $importAll || (bool) $this->option('stop-words');

        if (! $importCommonTexts && ! $importStopWords) {
            $importCommonTexts = true;
            $importStopWords = true;
        }

        if ((bool) $this->option('truncate')) {
            $this->truncateSelected(
                $importCommonTexts,
                $importStopWords,
            );
        }

        if ($importCommonTexts) {
            $this->components->info('Importing common texts...');
            $this->call('db:seed', [
                '--class' => ImportArabicCommonTextsSeeder::class,
                '--force' => true,
            ]);
        }

        if ($importStopWords) {
            $this->components->info('Importing stop words...');
            $this->call('db:seed', [
                '--class' => ImportArabicStopWordsSeeder::class,
                '--force' => true,
            ]);
        }

        $this->components->info('Arabicable dataset import completed.');

        return self::SUCCESS;
    }

    private function truncateSelected(
        bool $importCommonTexts,
        bool $importStopWords,
    ): void {
        if ($importCommonTexts && Schema::hasTable('common_arabic_texts')) {
            DB::table('common_arabic_texts')->truncate();
        }

        if ($importStopWords && Schema::hasTable('arabic_stop_words')) {
            DB::table('arabic_stop_words')->truncate();
        }
    }
}
