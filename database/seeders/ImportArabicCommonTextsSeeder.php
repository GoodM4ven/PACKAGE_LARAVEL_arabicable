<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Database\Seeders;

use GoodMaven\Arabicable\Enums\CommonArabicTextType;
use GoodMaven\Arabicable\Facades\ArabicFilter;
use GoodMaven\Arabicable\Support\Config\ArabicableConfig;
use GoodMaven\Arabicable\Support\Data\ChunkedUpsert;
use GoodMaven\Arabicable\Support\Data\DelimitedFileReader;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportArabicCommonTextsSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('common_arabic_texts')) {
            $this->command->warn('Skipping common-texts import: common_arabic_texts table does not exist.');

            return;
        }

        $path = (string) ArabicableConfig::get('arabicable.data_sources.stop_words_classified', '');

        if ($path === '' || ! is_file($path)) {
            $this->command->warn("Skipping common-texts import: source file not found at [{$path}].");

            return;
        }

        DB::connection()->disableQueryLog();

        $hasHarakat = Schema::hasColumn('common_arabic_texts', ar_with_harakat('content'));
        $hasSearchable = Schema::hasColumn('common_arabic_texts', ar_searchable('content'));
        $hasStemmed = Schema::hasColumn('common_arabic_texts', ar_stem('content'));

        $updateColumns = ['type', 'updated_at'];

        if ($hasHarakat) {
            $updateColumns[] = ar_with_harakat('content');
        }

        if ($hasSearchable) {
            $updateColumns[] = ar_searchable('content');
        }

        if ($hasStemmed) {
            $updateColumns[] = ar_stem('content');
        }

        $rows = [];
        $now = Carbon::now();

        foreach (DelimitedFileReader::readRows($path, "\t", skipHeader: true) as $cols) {
            $content = trim((string) ($cols[0] ?? ''));

            if ($content === '') {
                continue;
            }

            $typeWord = trim((string) ($cols[2] ?? ''));
            $classWord = trim((string) ($cols[3] ?? ''));
            $type = $this->mapType($typeWord, $classWord);

            $row = [
                'type' => $type->value,
                'content' => ArabicFilter::withoutHarakat($content),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($hasHarakat) {
                $row[ar_with_harakat('content')] = ArabicFilter::withHarakat($content);
            }

            if ($hasSearchable) {
                $row[ar_searchable('content')] = ArabicFilter::forSearch($content);
            }

            if ($hasStemmed) {
                $row[ar_stem('content')] = ArabicFilter::forStem($content);
            }

            $rows[] = $row;

            if (count($rows) === 500) {
                ChunkedUpsert::toTable(
                    'common_arabic_texts',
                    $rows,
                    ['content'],
                    $updateColumns,
                    500,
                );

                $rows = [];
            }
        }

        ChunkedUpsert::toTable(
            'common_arabic_texts',
            $rows,
            ['content'],
            $updateColumns,
            500,
        );
    }

    private function mapType(string $typeWord, string $classWord): CommonArabicTextType
    {
        if (str_contains($classWord, 'اسم علم')) {
            return CommonArabicTextType::Name;
        }

        if (str_contains($typeWord, 'فعل')) {
            return CommonArabicTextType::Verb;
        }

        if (str_contains($typeWord, 'اسم')) {
            return CommonArabicTextType::Noun;
        }

        if (str_contains($classWord, 'جملة')) {
            return CommonArabicTextType::Sentence;
        }

        return CommonArabicTextType::Separator;
    }
}
