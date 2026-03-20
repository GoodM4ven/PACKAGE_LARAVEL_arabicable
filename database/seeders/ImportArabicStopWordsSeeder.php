<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Database\Seeders;

use GoodMaven\Arabicable\Support\Config\ArabicableConfig;
use GoodMaven\Arabicable\Support\Data\ChunkedUpsert;
use GoodMaven\Arabicable\Support\Data\DelimitedFileReader;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportArabicStopWordsSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('arabic_stop_words')) {
            $this->command->warn('Skipping stop-words import: arabic_stop_words table does not exist.');

            return;
        }

        $path = (string) ArabicableConfig::get('arabicable.data_sources.stop_words_forms', '');

        if ($path === '' || ! is_file($path)) {
            $this->command->warn("Skipping stop-words import: source file not found at [{$path}].");

            return;
        }

        DB::connection()->disableQueryLog();

        $now = Carbon::now();
        $rows = [];
        $headerMap = null;

        foreach (DelimitedFileReader::readRows($path, "\t", skipHeader: false) as $cols) {
            if ($headerMap === null && $this->isHeaderRow($cols)) {
                $headerMap = $this->buildHeaderMap($cols);

                continue;
            }

            $word = $this->resolveValue($cols, $headerMap, 'word', [0]);

            if ($word === '') {
                continue;
            }

            $lemma = $this->resolveValue($cols, $headerMap, 'lemma', [4]);
            $stem = $this->resolveValue($cols, $headerMap, 'stem', [5, 6]);
            $tags = $this->resolveValue($cols, $headerMap, 'tags', [6, 8]);
            $source = $this->resolveValue($cols, $headerMap, 'source');

            $rows[] = [
                'word' => $word,
                'vocalized' => $this->resolveValue($cols, $headerMap, 'vocalized', [1]) ?: null,
                'lemma' => $lemma !== '' ? $lemma : $word,
                'type' => $this->resolveValue($cols, $headerMap, 'type', [2]) ?: null,
                'category' => $this->resolveValue($cols, $headerMap, 'category', [3]) ?: null,
                'stem' => $stem !== '' ? $stem : null,
                'tags' => $tags !== '' ? $tags : null,
                'source' => $source !== '' ? $source : 'arabicstopwords',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($rows) === 1000) {
                ChunkedUpsert::toTable(
                    'arabic_stop_words',
                    $rows,
                    ['word', 'source'],
                    ['vocalized', 'lemma', 'type', 'category', 'stem', 'tags', 'updated_at'],
                    1000,
                );

                $rows = [];
            }
        }

        ChunkedUpsert::toTable(
            'arabic_stop_words',
            $rows,
            ['word', 'source'],
            ['vocalized', 'lemma', 'type', 'category', 'stem', 'tags', 'updated_at'],
            1000,
        );
    }

    /**
     * @param  array<int, string>  $row
     */
    private function isHeaderRow(array $row): bool
    {
        $first = mb_strtolower(trim((string) ($row[0] ?? '')), 'UTF-8');
        $second = mb_strtolower(trim((string) ($row[1] ?? '')), 'UTF-8');

        return in_array($first, ['word', 'الكلمة', 'token'], true)
            || in_array($second, ['vocalized', 'lemma'], true);
    }

    /**
     * @param  array<int, string>  $row
     * @return array<string, int>
     */
    private function buildHeaderMap(array $row): array
    {
        $headers = [];

        foreach ($row as $index => $column) {
            $normalized = mb_strtolower(trim($column), 'UTF-8');

            if ($normalized === '') {
                continue;
            }

            $headers[$normalized] = $index;
        }

        return $headers;
    }

    /**
     * @param  array<int, string>  $row
     * @param  array<string, int>|null  $headerMap
     * @param  array<int, int>  $fallbackIndexes
     */
    private function resolveValue(array $row, ?array $headerMap, string $key, array $fallbackIndexes = []): string
    {
        if ($headerMap !== null && isset($headerMap[$key])) {
            return trim((string) ($row[$headerMap[$key]] ?? ''));
        }

        foreach ($fallbackIndexes as $index) {
            $value = trim((string) ($row[$index] ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}
