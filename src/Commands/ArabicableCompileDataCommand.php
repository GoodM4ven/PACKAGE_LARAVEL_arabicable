<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Commands;

use GoodMaven\Arabicable\Arabic;
use GoodMaven\Arabicable\Facades\ArabicFilter;
use GoodMaven\Arabicable\Support\Data\DelimitedFileReader;
use Illuminate\Console\Command;

class ArabicableCompileDataCommand extends Command
{
    public $signature = 'arabicable:compile-data
        {--raw-data-path= : Absolute path to assets root (default: arabicable.raw_data_path)}
        {--without-extra-stopwords : Skip optional extra stopwords lists}';

    public $description = 'Compile core Arabicable datasets (stop words and vocalization map).';

    private Arabic $arabic;

    private string $rawDataPath;

    /**
     * @var array<int, array{dataset: string, source: string, imported: int}>
     */
    private array $sourceStats = [];

    public function handle(): int
    {
        $this->arabic = app(Arabic::class);
        $this->rawDataPath = $this->resolveRawDataPath();

        if (! is_dir($this->rawDataPath)) {
            $this->components->error("Raw data path [{$this->rawDataPath}] does not exist.");

            return self::FAILURE;
        }

        $this->prepareDirectories();

        $counts = [
            'stop_words_forms' => $this->compileStopWordsForms(),
            'stop_words_classified' => $this->compileStopWordsClassified(),
            'vocalizations' => $this->compileVocalizations(),
        ];

        $this->writeSourceReport();

        $this->newLine();
        $this->components->info("Compiled datasets created in [{$this->rawDataPath}]:");

        foreach ($counts as $dataset => $count) {
            $this->line(sprintf('- %s: %d rows', $dataset, $count));
        }

        $this->newLine();
        $this->line('Next step: run `php workbench/artisan arabicable:seed --all --truncate`');

        return self::SUCCESS;
    }

    private function resolveRawDataPath(): string
    {
        $optionPath = $this->option('raw-data-path');

        if (is_string($optionPath) && trim($optionPath) !== '') {
            return rtrim(trim($optionPath), '/');
        }

        $configuredPath = config('arabicable.raw_data_path', '');

        if (is_string($configuredPath) && trim($configuredPath) !== '') {
            return rtrim(trim($configuredPath), '/');
        }

        return basename(base_path()) === 'workbench'
            ? dirname(base_path()).'/resources/raw-data'
            : base_path('resources/raw-data');
    }

    private function prepareDirectories(): void
    {
        foreach ([
            $this->rawDataPath.'/stop-words',
            $this->rawDataPath.'/vocalizations',
            $this->rawDataPath.'/index',
        ] as $dir) {
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
        }
    }

    private function compileStopWordsForms(): int
    {
        /** @var array<string, array{word: string, vocalized: string, type: string, category: string, lemma: string, stem: string, tags: string, source: string}> $rowsByKey */
        $rowsByKey = [];

        $add = function (
            string $word,
            ?string $vocalized,
            ?string $type,
            ?string $category,
            ?string $lemma,
            ?string $stem,
            ?string $tags,
            string $source,
        ) use (&$rowsByKey): void {
            $normalizedWord = $this->normalizeWord($word);

            if ($normalizedWord === '') {
                return;
            }

            $normalizedVocalized = $this->normalizeVocalized((string) ($vocalized ?? $normalizedWord));
            $normalizedLemma = $this->normalizeWord((string) ($lemma ?? $normalizedWord));
            $normalizedStem = $this->normalizeWord((string) ($stem ?? $normalizedWord));
            $key = $normalizedWord.'|'.$source;

            $rowsByKey[$key] = [
                'word' => $normalizedWord,
                'vocalized' => $normalizedVocalized,
                'type' => trim((string) ($type ?? '')),
                'category' => trim((string) ($category ?? '')),
                'lemma' => $normalizedLemma !== '' ? $normalizedLemma : $normalizedWord,
                'stem' => $normalizedStem !== '' ? $normalizedStem : $normalizedWord,
                'tags' => trim((string) ($tags ?? '')),
                'source' => $source,
            ];
        };

        $formsPaths = [
            $this->rawDataPath.'/stop-words/stop-words-all-forms-01.tsv' => 'stopwords-kalimat-forms',
            $this->rawDataPath.'/arabicstopwords-master/releases/latest/csv/stopwordsallforms.csv' => 'arabicstopwords-forms',
        ];

        foreach ($formsPaths as $path => $source) {
            if (! is_file($path)) {
                continue;
            }

            $imported = 0;

            foreach (DelimitedFileReader::readRows($path, "\t", skipHeader: true) as $cols) {
                $add(
                    word: (string) ($cols[0] ?? ''),
                    vocalized: (string) ($cols[1] ?? ''),
                    type: (string) ($cols[2] ?? ''),
                    category: (string) ($cols[3] ?? ''),
                    lemma: (string) ($cols[4] ?? ''),
                    stem: (string) ($cols[6] ?? ''),
                    tags: (string) ($cols[8] ?? ''),
                    source: $source,
                );
                $imported++;
            }

            $this->trackSource('stop_words_forms', $path, $imported);
        }

        $classifiedPaths = [
            $this->rawDataPath.'/stop-words/stop-words-classified.tsv' => 'stopwords-kalimat-classified',
            $this->rawDataPath.'/arabicstopwords-master/releases/latest/csv/stopwords_classified.csv' => 'arabicstopwords-classified',
        ];

        foreach ($classifiedPaths as $path => $source) {
            if (! is_file($path)) {
                continue;
            }

            $imported = 0;

            foreach (DelimitedFileReader::readRows($path, "\t", skipHeader: true) as $cols) {
                $word = (string) ($cols[0] ?? '');

                $add(
                    word: $word,
                    vocalized: (string) ($cols[1] ?? $word),
                    type: (string) ($cols[2] ?? ''),
                    category: (string) ($cols[3] ?? ''),
                    lemma: $word,
                    stem: $word,
                    tags: '',
                    source: $source,
                );
                $imported++;
            }

            $this->trackSource('stop_words_forms', $path, $imported);
        }

        $jsonPaths = [
            $this->rawDataPath.'/stop-words/stop-words-main-01.json' => 'stopwords-kalimat-json',
        ];

        if (! (bool) $this->option('without-extra-stopwords')) {
            $jsonPaths[$this->rawDataPath.'/stop-words/stop-words-extra-02.json'] = 'stopwords-kalimat-extra';
        }

        foreach ($jsonPaths as $path => $source) {
            if (! is_file($path)) {
                continue;
            }

            $data = json_decode((string) file_get_contents($path), true);

            if (! is_array($data)) {
                continue;
            }

            $imported = 0;

            foreach ($data as $item) {
                if (! is_string($item)) {
                    continue;
                }

                $add(
                    word: $item,
                    vocalized: $item,
                    type: 'حرف',
                    category: 'كلمات شائعة',
                    lemma: $item,
                    stem: $item,
                    tags: '',
                    source: $source,
                );
                $imported++;
            }

            $this->trackSource('stop_words_forms', $path, $imported);
        }

        $plainPaths = [
            $this->rawDataPath.'/stop-words/stop-words-plain-01.txt' => 'stopwords-kalimat-plain',
            $this->rawDataPath.'/stop-words/stop-words-list-01.txt' => 'stopwords-kalimat-list',
        ];

        foreach ($plainPaths as $path => $source) {
            if (! is_file($path)) {
                continue;
            }

            $imported = 0;
            $file = new \SplFileObject($path, 'r');

            while (! $file->eof()) {
                $line = trim((string) $file->fgets());

                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }

                $add(
                    word: $line,
                    vocalized: $line,
                    type: 'حرف',
                    category: 'كلمات شائعة',
                    lemma: $line,
                    stem: $line,
                    tags: '',
                    source: $source,
                );
                $imported++;
            }

            $this->trackSource('stop_words_forms', $path, $imported);
        }

        $rows = array_values($rowsByKey);
        usort($rows, static function (array $left, array $right): int {
            return strcmp($left['word'].'|'.$left['source'], $right['word'].'|'.$right['source']);
        });

        $this->writeTsv(
            $this->rawDataPath.'/stop-words/compiled-stop-words-forms.tsv',
            ['word', 'vocalized', 'type', 'category', 'lemma', 'stem', 'tags', 'source'],
            array_map(static fn (array $row): array => [
                $row['word'],
                $row['vocalized'],
                $row['type'],
                $row['category'],
                $row['lemma'],
                $row['stem'],
                $row['tags'],
                $row['source'],
            ], $rows),
        );

        return count($rows);
    }

    private function compileStopWordsClassified(): int
    {
        /** @var array<string, array{word: string, vocalized: string, type: string, category: string, source: string}> $rowsByKey */
        $rowsByKey = [];

        $add = function (string $word, ?string $vocalized, ?string $type, ?string $category, string $source) use (&$rowsByKey): void {
            $normalizedWord = $this->normalizeWord($word);

            if ($normalizedWord === '') {
                return;
            }

            $rowsByKey[$normalizedWord] = [
                'word' => $normalizedWord,
                'vocalized' => $this->normalizeVocalized((string) ($vocalized ?? $normalizedWord)),
                'type' => trim((string) ($type ?? '')),
                'category' => trim((string) ($category ?? '')),
                'source' => $source,
            ];
        };

        $classifiedPath = $this->rawDataPath.'/stop-words/stop-words-classified.tsv';

        if (is_file($classifiedPath)) {
            $imported = 0;

            foreach (DelimitedFileReader::readRows($classifiedPath, "\t", skipHeader: true) as $cols) {
                $add(
                    word: (string) ($cols[0] ?? ''),
                    vocalized: (string) ($cols[1] ?? ''),
                    type: (string) ($cols[2] ?? ''),
                    category: (string) ($cols[3] ?? ''),
                    source: 'stopwords-kalimat-classified',
                );
                $imported++;
            }

            $this->trackSource('stop_words_classified', $classifiedPath, $imported);
        }

        $formsPath = $this->rawDataPath.'/stop-words/compiled-stop-words-forms.tsv';

        if (is_file($formsPath)) {
            $imported = 0;

            foreach (DelimitedFileReader::readRows($formsPath, "\t", skipHeader: true) as $cols) {
                $add(
                    word: (string) ($cols[0] ?? ''),
                    vocalized: (string) ($cols[1] ?? ''),
                    type: (string) ($cols[2] ?? ''),
                    category: (string) ($cols[3] ?? ''),
                    source: 'compiled-forms-derived',
                );
                $imported++;
            }

            $this->trackSource('stop_words_classified', $formsPath, $imported);
        }

        $rows = array_values($rowsByKey);
        usort($rows, static fn (array $left, array $right): int => strcmp($left['word'], $right['word']));

        $this->writeTsv(
            $this->rawDataPath.'/stop-words/compiled-stop-words-classified.tsv',
            ['word', 'vocalized', 'type', 'category', 'source'],
            array_map(static fn (array $row): array => [
                $row['word'],
                $row['vocalized'],
                $row['type'],
                $row['category'],
                $row['source'],
            ], $rows),
        );

        return count($rows);
    }

    private function compileVocalizations(): int
    {
        /** @var array<string, array{word: string, vocalized: string, source: string}> $rowsByKey */
        $rowsByKey = [];

        $add = function (string $word, string $vocalized, string $source) use (&$rowsByKey): void {
            $normalizedWord = $this->normalizeWord($word);
            $normalizedVocalized = $this->normalizeVocalized($vocalized);

            if ($normalizedWord === '' || $normalizedVocalized === '') {
                return;
            }

            $rowsByKey[$normalizedWord] = [
                'word' => $normalizedWord,
                'vocalized' => $normalizedVocalized,
                'source' => $source,
            ];
        };

        $pairedVocalizedPath = $this->rawDataPath.'/vocalizations/source-vocalized-words-01.txt';
        $pairedUnvocalizedPath = $this->rawDataPath.'/vocalizations/source-unvocalized-words-02.txt';

        if (is_file($pairedVocalizedPath) && is_file($pairedUnvocalizedPath)) {
            $vocalizedLines = file($pairedVocalizedPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            $unvocalizedLines = file($pairedUnvocalizedPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            $count = min(count($vocalizedLines), count($unvocalizedLines));

            for ($index = 0; $index < $count; $index++) {
                $add(
                    word: (string) $unvocalizedLines[$index],
                    vocalized: (string) $vocalizedLines[$index],
                    source: 'vocalizations-paired-words',
                );
            }

            $this->trackSource('vocalizations', $pairedVocalizedPath, $count);
            $this->trackSource('vocalizations', $pairedUnvocalizedPath, $count);
        }

        $jsonPath = $this->rawDataPath.'/vocalizations/source-vocalizations-map-01.json';

        if (is_file($jsonPath)) {
            $payload = json_decode((string) file_get_contents($jsonPath), true);

            if (is_array($payload)) {
                $imported = 0;

                foreach ($payload as $word => $vocalized) {
                    if (! is_string($word) || ! is_string($vocalized)) {
                        continue;
                    }

                    $add($word, $vocalized, 'vocalizations-json-map');
                    $imported++;
                }

                $this->trackSource('vocalizations', $jsonPath, $imported);
            }
        }

        $rows = array_values($rowsByKey);
        usort($rows, static fn (array $left, array $right): int => strcmp($left['word'], $right['word']));

        $this->writeTsv(
            $this->rawDataPath.'/vocalizations/compiled-vocalizations.tsv',
            ['word', 'vocalized', 'source'],
            array_map(static fn (array $row): array => [
                $row['word'],
                $row['vocalized'],
                $row['source'],
            ], $rows),
        );

        return count($rows);
    }

    private function writeSourceReport(): void
    {
        usort($this->sourceStats, static function (array $left, array $right): int {
            $leftKey = $left['dataset'].'|'.$left['source'];
            $rightKey = $right['dataset'].'|'.$right['source'];

            return strcmp($leftKey, $rightKey);
        });

        $rows = array_map(static fn (array $row): array => [
            $row['dataset'],
            $row['source'],
            (string) $row['imported'],
        ], $this->sourceStats);

        $this->writeTsv(
            $this->rawDataPath.'/index/compiled-source-report.tsv',
            ['dataset', 'source', 'imported'],
            $rows,
        );
    }

    private function trackSource(string $dataset, string $source, int $imported): void
    {
        $relative = $source;

        if (str_starts_with($source, $this->rawDataPath)) {
            $relative = ltrim(substr($source, strlen($this->rawDataPath)), '/');
        }

        $this->sourceStats[] = [
            'dataset' => $dataset,
            'source' => $relative,
            'imported' => $imported,
        ];
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, string>>  $rows
     */
    private function writeTsv(string $path, array $headers, array $rows): void
    {
        $lines = [implode("\t", $headers)];

        foreach ($rows as $row) {
            $lines[] = implode("\t", $row);
        }

        file_put_contents($path, implode(PHP_EOL, $lines).PHP_EOL);
    }

    private function normalizeWord(string $value): string
    {
        $normalized = ArabicFilter::forSearch(trim($value));

        return $this->arabic->normalizeSpaces($normalized);
    }

    private function normalizeVocalized(string $value): string
    {
        return $this->arabic->normalizeSpaces(trim($value));
    }
}
