<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Support\Text;

use GoodMaven\Arabicable\Facades\ArabicFilter;
use GoodMaven\Arabicable\Support\Config\ArabicableConfig;
use GoodMaven\Arabicable\Support\Data\DelimitedFileReader;
use Illuminate\Support\Facades\Cache;

final class ArabicWordVariants
{
    private const CACHE_VERSION_KEY = 'arabicable.word_variants.cache.version';

    private const MODE_ALL = 'all';

    private const MODE_ROOTS = 'roots';

    private const MODE_STEMS = 'stems';

    private const MODE_ORIGINAL_WORDS = 'original_words';

    private string $activeIndexSignature = 'none';

    /**
     * @var array<string, array<string, true>>
     */
    private array $runtimeVariantsCache = [];

    private int $runtimeCacheVersion = -1;

    public function __construct(
        private readonly ArabicStemmer $stemmer,
        private readonly ArabicStopWords $stopWords,
    ) {}

    /**
     * @param  array<int, string>  $tokens
     * @return array<int, string>
     */
    public function expandTokens(
        array $tokens,
        ?int $maxVariantsPerToken = null,
        ?int $maxTerms = null,
        ?string $mode = null,
        ?bool $stripStopWords = null,
    ): array {
        $maxVariantsPerToken ??= (int) ArabicableConfig::get(
            'arabicable.search.comprehensive.max_word_variants_per_token',
            24,
        );
        $maxTerms ??= (int) ArabicableConfig::get(
            'arabicable.search.comprehensive.max_variant_terms',
            120,
        );
        $minLength = (int) ArabicableConfig::get(
            'arabicable.search.comprehensive.min_variant_term_length',
            2,
        );
        $mode = $this->normalizeMode($mode);
        $stripStopWords ??= (bool) ArabicableConfig::get(
            'arabicable.search.comprehensive.strip_stop_words_from_variants',
            true,
        );

        if ($maxVariantsPerToken < 1 || $maxTerms < 1) {
            return [];
        }

        $index = $this->index();
        $expanded = [];
        $stopWordLookup = $stripStopWords ? $this->stopWordLookup() : [];
        $preparedTokens = [];
        $seenTokens = [];

        foreach ($tokens as $token) {
            $key = $this->canonical($token);

            if ($key === '' || $this->isStopWord($key, $stopWordLookup) || isset($seenTokens[$key])) {
                continue;
            }

            $seenTokens[$key] = true;
            $preparedTokens[] = $key;
        }

        foreach ($preparedTokens as $key) {
            $variants = $this->variantsForTokenCached($key, $mode, $index);
            $limitedVariants = array_slice(array_keys($variants), 0, $maxVariantsPerToken);

            foreach ($limitedVariants as $variant) {
                if (mb_strlen($variant, 'UTF-8') < $minLength || $this->isStopWord($variant, $stopWordLookup)) {
                    continue;
                }

                $expanded[$variant] = true;

                if (count($expanded) >= $maxTerms) {
                    break 2;
                }
            }
        }

        return array_keys($expanded);
    }

    public function clearCache(): void
    {
        Cache::put(self::CACHE_VERSION_KEY, $this->cacheVersion() + 1);
        $this->clearSnapshotFiles();
    }

    /**
     * @return array{
     *   by_token: array<string, array<int, string>>,
     *   by_root: array<string, array<int, string>>,
     *   token_roots: array<string, array<int, string>>
     * }
     */
    private function index(): array
    {
        $variantsPath = (string) ArabicableConfig::get('arabicable.data_sources.word_variants', '');
        $quranIndexPath = (string) ArabicableConfig::get('arabicable.data_sources.quran_word_index', '');

        if (! is_file($variantsPath) && ! is_file($quranIndexPath)) {
            return $this->emptyIndex();
        }

        $signature = md5(implode('|', [
            $variantsPath,
            is_file($variantsPath) ? (string) filemtime($variantsPath) : '0',
            $quranIndexPath,
            is_file($quranIndexPath) ? (string) filemtime($quranIndexPath) : '0',
        ]));
        $this->activeIndexSignature = $signature;

        $cacheKey = sprintf('arabicable.word_variants.%d.%s', $this->cacheVersion(), $signature);
        $snapshotPath = $this->snapshotPath($signature);

        $snapshot = $this->loadSnapshot($snapshotPath);

        if ($snapshot !== null) {
            return $snapshot;
        }

        /** @var array{
         *   by_token: array<string, array<int, string>>,
         *   by_root: array<string, array<int, string>>,
         *   token_roots: array<string, array<int, string>>
         * } $index
         */
        $index = Cache::rememberForever($cacheKey, function () use ($variantsPath, $quranIndexPath): array {
            $index = [
                'by_token' => [],
                'by_root' => [],
                'token_roots' => [],
            ];

            if (is_file($variantsPath)) {
                $this->importVariants($index, $variantsPath);
            }

            if (is_file($quranIndexPath)) {
                $this->importQuranWordIndex($index, $quranIndexPath);
            }

            return $this->finalizeIndex($index);
        });

        $this->storeSnapshot($snapshotPath, $index);

        return $index;
    }

    /**
     * @return array<string, true>
     */
    private function stopWordLookup(): array
    {
        $cacheKey = sprintf('arabicable.word_variants.stop_words.%d', $this->cacheVersion());

        /** @var array<string, true> $lookup */
        $lookup = Cache::rememberForever($cacheKey, function (): array {
            $lookup = [];

            foreach ($this->stopWords->all() as $word) {
                $this->addStopWord($lookup, $word);
            }

            return $lookup;
        });

        return $lookup;
    }

    /**
     * @param  array{
     *   by_token: array<string, array<string, true>>,
     *   by_root: array<string, array<string, true>>,
     *   token_roots: array<string, array<string, true>>
     * }  $index
     */
    private function importVariants(array &$index, string $path): void
    {
        $headerMap = [];
        $headerHandled = false;

        foreach (DelimitedFileReader::readRows($path, "\t", skipHeader: false) as $row) {
            if (! $headerHandled && $this->looksLikeVariantsHeader($row)) {
                $headerMap = $this->buildHeaderMap($row, [
                    'word_with_harakat' => 0,
                    'unvocalized' => 1,
                    'unmarked' => 2,
                    'root' => 3,
                ]);
                $headerHandled = true;

                continue;
            }

            $headerHandled = true;

            $surface = $this->canonical((string) ($row[$headerMap['unvocalized'] ?? 1] ?? ''));
            $unmarked = $this->canonical((string) ($row[$headerMap['unmarked'] ?? 2] ?? ''));
            $harakat = $this->canonical((string) ($row[$headerMap['word_with_harakat'] ?? 0] ?? ''));
            $root = $this->canonical((string) ($row[$headerMap['root'] ?? 3] ?? ''));

            $terms = [$surface, $unmarked, $harakat];
            $tokenCandidates = [$surface, $unmarked, $harakat];

            $this->addEntry($index, $tokenCandidates, $root, $terms);
        }
    }

    /**
     * @param  array{
     *   by_token: array<string, array<string, true>>,
     *   by_root: array<string, array<string, true>>,
     *   token_roots: array<string, array<string, true>>
     * }  $index
     */
    private function importQuranWordIndex(array &$index, string $path): void
    {
        $headerMap = [];
        $headerHandled = false;

        foreach (DelimitedFileReader::readRows($path, "\t", skipHeader: false) as $row) {
            if (! $headerHandled && $this->looksLikeQuranIndexHeader($row)) {
                $headerMap = $this->buildHeaderMap($row, [
                    'word' => 0,
                    'root' => 1,
                    'singular' => 2,
                    'plural' => 3,
                ]);
                $headerHandled = true;

                continue;
            }

            $headerHandled = true;

            $word = $this->canonical((string) ($row[$headerMap['word'] ?? 0] ?? ''));
            $root = $this->canonical((string) ($row[$headerMap['root'] ?? 1] ?? ''));
            $singular = $this->canonical((string) ($row[$headerMap['singular'] ?? 2] ?? ''));
            $plural = $this->canonical((string) ($row[$headerMap['plural'] ?? 3] ?? ''));

            $terms = [$word, $singular, $plural];
            $tokenCandidates = [$word, $singular, $plural];

            $this->addEntry($index, $tokenCandidates, $root, $terms);
        }
    }

    /**
     * @param  array{
     *   by_token: array<string, array<string, true>>,
     *   by_root: array<string, array<string, true>>,
     *   token_roots: array<string, array<string, true>>
     * }  $index
     * @param  array<int, string>  $tokenCandidates
     * @param  array<int, string>  $terms
     */
    private function addEntry(array &$index, array $tokenCandidates, string $root, array $terms): void
    {
        $normalizedTerms = [];
        $this->collect($normalizedTerms, $terms);

        if ($normalizedTerms === []) {
            return;
        }

        foreach (array_keys($normalizedTerms) as $candidate) {
            $this->collectBucket($index['by_token'], $candidate, array_keys($normalizedTerms));
        }

        if ($root !== '') {
            $this->collectBucket($index['by_root'], $root, array_keys($normalizedTerms));
        }

        foreach ($tokenCandidates as $candidateRaw) {
            $candidate = $this->canonical($candidateRaw);

            if ($candidate === '') {
                continue;
            }

            if ($root !== '') {
                $index['token_roots'][$candidate][$root] = true;
            }
        }
    }

    /**
     * @param  array<string, array<string, true>>  $bucket
     * @param  array<int, string>  $terms
     */
    private function collectBucket(array &$bucket, string $key, array $terms): void
    {
        if ($key === '' || $terms === []) {
            return;
        }

        if (! array_key_exists($key, $bucket)) {
            $bucket[$key] = [];
        }

        foreach ($terms as $term) {
            $normalizedTerm = $this->canonical($term);

            if ($normalizedTerm === '') {
                continue;
            }

            $bucket[$key][$normalizedTerm] = true;
        }
    }

    /**
     * @param  array<string, true>  $target
     * @param  array<int, string>  $values
     */
    private function collect(array &$target, array $values): void
    {
        foreach ($values as $value) {
            $normalized = $this->canonical($value);

            if ($normalized === '') {
                continue;
            }

            $target[$normalized] = true;
        }
    }

    /**
     * @param  array{
     *   by_token: array<string, array<string, true>>,
     *   by_root: array<string, array<string, true>>,
     *   token_roots: array<string, array<string, true>>
     * }  $index
     * @return array{
     *   by_token: array<string, array<int, string>>,
     *   by_root: array<string, array<int, string>>,
     *   token_roots: array<string, array<int, string>>
     * }
     */
    private function finalizeIndex(array $index): array
    {
        $finalized = $this->emptyIndex();

        foreach (array_keys($finalized) as $section) {
            foreach ($index[$section] as $key => $set) {
                $finalized[$section][$key] = array_keys($set);
            }
        }

        return $finalized;
    }

    /**
     * @return array{
     *   by_token: array<string, array<int, string>>,
     *   by_root: array<string, array<int, string>>,
     *   token_roots: array<string, array<int, string>>
     * }
     */
    private function emptyIndex(): array
    {
        return [
            'by_token' => [],
            'by_root' => [],
            'token_roots' => [],
        ];
    }

    /**
     * @param  array<int, string>  $row
     */
    private function looksLikeVariantsHeader(array $row): bool
    {
        return isset($row[0], $row[1], $row[2], $row[3])
            && mb_strtolower($row[0], 'UTF-8') === 'word_with_harakat'
            && mb_strtolower($row[1], 'UTF-8') === 'unvocalized'
            && mb_strtolower($row[2], 'UTF-8') === 'unmarked'
            && mb_strtolower($row[3], 'UTF-8') === 'root';
    }

    /**
     * @param  array<int, string>  $row
     */
    private function looksLikeQuranIndexHeader(array $row): bool
    {
        return isset($row[0], $row[1])
            && mb_strtolower($row[0], 'UTF-8') === 'word'
            && mb_strtolower($row[1], 'UTF-8') === 'root';
    }

    /**
     * @param  array<int, string>  $row
     * @param  array<string, int>  $defaults
     * @return array<string, int>
     */
    private function buildHeaderMap(array $row, array $defaults): array
    {
        $map = $defaults;

        foreach ($row as $index => $column) {
            $normalized = mb_strtolower(trim($column), 'UTF-8');

            if (array_key_exists($normalized, $map)) {
                $map[$normalized] = $index;
            }
        }

        return $map;
    }

    /**
     * @param  array{
     *   by_token: array<string, array<int, string>>,
     *   by_root: array<string, array<int, string>>,
     *   token_roots: array<string, array<int, string>>
     * }  $index
     * @return array<string, true>
     */
    private function variantsForToken(string $token, string $mode, array $index): array
    {
        $stem = $this->canonical($this->stemmer->stem($token));
        $roots = [];

        $this->collect($roots, $index['token_roots'][$token] ?? []);

        if ($stem !== '') {
            $this->collect($roots, $index['token_roots'][$stem] ?? []);
        }

        if (array_key_exists($token, $index['by_root'])) {
            $roots[$token] = true;
        }

        if ($stem !== '' && array_key_exists($stem, $index['by_root'])) {
            $roots[$stem] = true;
        }

        $variants = [];

        if ($mode === self::MODE_ROOTS || $mode === self::MODE_ALL) {
            $this->collect($variants, array_keys($roots));
        }

        if ($mode === self::MODE_ORIGINAL_WORDS || $mode === self::MODE_ALL) {
            $this->collect($variants, [$token]);
            $this->collect($variants, $index['by_token'][$token] ?? []);

            if ($stem !== '') {
                $this->collect($variants, $index['by_token'][$stem] ?? []);
            }

            foreach (array_keys($roots) as $root) {
                $this->collect($variants, $index['by_root'][$root] ?? []);
            }
        }

        if ($mode === self::MODE_STEMS || $mode === self::MODE_ALL) {
            $stemmedVariants = [];
            $this->collect($stemmedVariants, [$stem]);
            $this->collectStems($stemmedVariants, $index['by_token'][$token] ?? []);

            if ($stem !== '') {
                $this->collectStems($stemmedVariants, $index['by_token'][$stem] ?? []);
            }

            foreach (array_keys($roots) as $root) {
                $this->collectStems($stemmedVariants, $index['by_root'][$root] ?? []);
            }

            $this->collect($variants, array_keys($stemmedVariants));
        }

        return $variants;
    }

    /**
     * @param  array{
     *   by_token: array<string, array<int, string>>,
     *   by_root: array<string, array<int, string>>,
     *   token_roots: array<string, array<int, string>>
     * }  $index
     * @return array<string, true>
     */
    private function variantsForTokenCached(string $token, string $mode, array $index): array
    {
        $version = $this->cacheVersion();

        if ($this->runtimeCacheVersion !== $version) {
            $this->runtimeCacheVersion = $version;
            $this->runtimeVariantsCache = [];
        }

        $runtimeKey = sprintf('%s|%s|%s', $this->activeIndexSignature, $mode, $token);

        if (array_key_exists($runtimeKey, $this->runtimeVariantsCache)) {
            return $this->runtimeVariantsCache[$runtimeKey];
        }

        $cacheKey = sprintf(
            'arabicable.word_variants.expanded.%d.%s.%s.%s',
            $version,
            $this->activeIndexSignature,
            $mode,
            md5($token),
        );

        /** @var array<string, true> $variants */
        $variants = Cache::rememberForever(
            $cacheKey,
            fn (): array => $this->variantsForToken($token, $mode, $index),
        );

        $this->runtimeVariantsCache[$runtimeKey] = $variants;

        return $variants;
    }

    private function cacheVersion(): int
    {
        /** @var int $version */
        $version = Cache::rememberForever(self::CACHE_VERSION_KEY, static fn (): int => 1);

        return $version;
    }

    private function canonical(string $term): string
    {
        $normalized = ArabicFilter::forSearch($term);

        return trim($normalized);
    }

    private function normalizeMode(?string $mode): string
    {
        $resolved = $mode ?? (string) ArabicableConfig::get('arabicable.search.comprehensive.variant_mode', self::MODE_ALL);
        $resolved = str_replace('-', '_', mb_strtolower(trim($resolved), 'UTF-8'));

        return match ($resolved) {
            self::MODE_ROOTS,
            self::MODE_STEMS,
            self::MODE_ORIGINAL_WORDS,
            self::MODE_ALL => $resolved,
            default => self::MODE_ALL,
        };
    }

    /**
     * @param  array<string, true>  $target
     * @param  array<int, string>  $terms
     */
    private function collectStems(array &$target, array $terms): void
    {
        foreach ($terms as $term) {
            $this->collect($target, [$this->stemmer->stem($term)]);
        }
    }

    /**
     * @param  array<string, true>  $lookup
     */
    private function isStopWord(string $term, array $lookup): bool
    {
        return $term !== '' && array_key_exists($term, $lookup);
    }

    /**
     * @param  array<string, true>  $lookup
     */
    private function addStopWord(array &$lookup, string $word): void
    {
        $normalized = $this->canonical($word);

        if ($normalized === '') {
            return;
        }

        $lookup[$normalized] = true;
    }

    private function snapshotPath(string $signature): string
    {
        return rtrim($this->snapshotDirectory(), DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR
            .'arabicable-word-variants-'.$signature.'.php';
    }

    private function snapshotDirectory(): string
    {
        if (function_exists('storage_path')) {
            return storage_path('framework/cache');
        }

        return sys_get_temp_dir();
    }

    /**
     * @return array{
     *   by_token: array<string, array<int, string>>,
     *   by_root: array<string, array<int, string>>,
     *   token_roots: array<string, array<int, string>>
     * }|null
     */
    private function loadSnapshot(string $path): ?array
    {
        if (! is_file($path)) {
            return null;
        }

        $loaded = require $path;

        if (! is_array($loaded)) {
            return null;
        }

        if (! isset($loaded['by_token'], $loaded['by_root'], $loaded['token_roots'])) {
            return null;
        }

        return $loaded;
    }

    /**
     * @param  array{
     *   by_token: array<string, array<int, string>>,
     *   by_root: array<string, array<int, string>>,
     *   token_roots: array<string, array<int, string>>
     * }  $index
     */
    private function storeSnapshot(string $path, array $index): void
    {
        $directory = dirname($path);

        if (! is_dir($directory)) {
            @mkdir($directory, 0777, true);
        }

        $content = '<?php return '.var_export($index, true).';'.PHP_EOL;
        @file_put_contents($path, $content, LOCK_EX);
    }

    private function clearSnapshotFiles(): void
    {
        $directory = $this->snapshotDirectory();

        if (! is_dir($directory)) {
            return;
        }

        $paths = glob(rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'arabicable-word-variants-*.php') ?: [];

        foreach ($paths as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}
