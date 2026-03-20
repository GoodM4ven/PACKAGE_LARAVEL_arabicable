<?php

declare(strict_types=1);

namespace Workbench\App\Livewire;

use GoodMaven\Arabicable\Facades\ArabicFilter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class QuranReader extends Component
{
    public string $query = '';

    public int $pageNumber = 1;

    public int $activeAyahIndex = 0;

    public int $searchSelectionAyahIndex = 0;

    public string $sourceKey = '';

    public bool $showTafsir = true;

    public bool $showIrab = true;

    public int $searchLimit = 40;

    public function updatedQuery(): void
    {
        $this->searchSelectionAyahIndex = 0;

        if (trim($this->query) === '') {
            return;
        }

        $singleMatchAyahIndex = $this->findSingleSearchMatchAyahIndex($this->query);

        if ($singleMatchAyahIndex !== null) {
            $this->focusAyahByIndex($singleMatchAyahIndex);
        }
    }

    public function updatedSearchSelectionAyahIndex(): void
    {
        if ($this->searchSelectionAyahIndex > 0) {
            $this->focusAyahByIndex($this->searchSelectionAyahIndex);
        }
    }

    public function updatedPageNumber(): void
    {
        if ($this->pageNumber < 1) {
            $this->pageNumber = 1;
        }
    }

    public function goToPage(int $pageNumber): void
    {
        $this->pageNumber = max(1, $pageNumber);
    }

    public function nextPage(): void
    {
        $this->pageNumber++;
    }

    public function previousPage(): void
    {
        $this->pageNumber = max(1, $this->pageNumber - 1);
    }

    public function selectAyah(int $ayahIndex): void
    {
        $this->focusAyahByIndex($ayahIndex);
    }

    public function clearFilters(): void
    {
        $this->query = '';
        $this->sourceKey = '';
        $this->showTafsir = true;
        $this->showIrab = true;
        $this->searchSelectionAyahIndex = 0;
    }

    public function render(): View
    {
        $quranFeaturesEnabled = (bool) config('arabicable.features.quran', true);
        $hasVersesTable = Schema::hasTable('quran_verses');
        $hasWordsTable = Schema::hasTable('quran_words');
        $hasMushafLinesTable = Schema::hasTable('quran_mushaf_lines');
        $hasExplanationsTable = Schema::hasTable('quran_verse_explanations');

        $hasTypedSearchColumn = $hasVersesTable && Schema::hasColumn('quran_verses', 'text_searchable_typed');
        $hasTypedWordColumn = $hasWordsTable && Schema::hasColumn('quran_words', 'token_searchable_typed');

        $ready = $quranFeaturesEnabled && $hasVersesTable && $hasWordsTable && $hasMushafLinesTable && $hasTypedSearchColumn;

        if (! $ready) {
            return view('livewire.quran-reader', [
                'ready' => false,
                'searchQuery' => '',
                'searchMatches' => [],
                'mushafLines' => [],
                'sourceOptions' => [],
                'selectedVerse' => null,
                'selectedExplanations' => [],
                'pageNumber' => 1,
                'maxPage' => 0,
                'activeAyahIndex' => 0,
                'selectedSurahTitle' => '',
                'qpcPageFontFamily' => null,
                'qpcPageFontUrl' => null,
                'useCenteredAyahLayout' => true,
            ]);
        }

        $maxPage = (int) DB::table('quran_mushaf_lines')->max('page_number');
        $pageNumber = $this->pageNumber;

        if ($maxPage > 0 && $pageNumber > $maxPage) {
            $pageNumber = $maxPage;
            $this->pageNumber = $pageNumber;
        }

        if ($pageNumber < 1) {
            $pageNumber = 1;
            $this->pageNumber = 1;
        }

        $qpcPageFont = $this->resolveQpcPageFont($pageNumber);

        $searchQuery = trim($this->normalizeQuranSearchQuery($this->query));
        $searchMatches = $searchQuery !== ''
            ? $this->buildSearchMatches($searchQuery, $this->searchLimit, $hasTypedWordColumn)
            : [];

        $mushafLines = $this->buildPageLines($pageNumber);
        $useCenteredAyahLayout = $this->shouldUseCenteredAyahLayout($pageNumber, $mushafLines);

        $effectiveAyahIndex = $this->activeAyahIndex;

        if ($effectiveAyahIndex < 1) {
            $effectiveAyahIndex = $this->firstAyahIndexInPage($mushafLines) ?? 0;
        }

        $selectedVerse = null;

        if ($effectiveAyahIndex > 0) {
            $selectedVerse = DB::table('quran_verses')
                ->select([
                    'id',
                    'surah_number',
                    'ayah_number',
                    'ayah_index',
                    'mushaf_page',
                    'mushaf_line',
                    'text_uthmani',
                    'text_searchable_typed',
                ])
                ->where('ayah_index', $effectiveAyahIndex)
                ->first();
        }

        $selectedSurahTitle = '';

        if (is_object($selectedVerse)) {
            $selectedSurahTitle = $this->formatSurahTitle((int) ($selectedVerse->surah_number ?? 0));
        }

        $sourceOptions = [];
        $selectedExplanations = [];

        if ($hasExplanationsTable) {
            $sourceOptions = DB::table('quran_verse_explanations')
                ->select('source_key', 'source_label', 'content_kind')
                ->whereNotIn('content_text', ['', '-', '—', '–'])
                ->distinct()
                ->orderBy('content_kind')
                ->orderBy('source_label')
                ->get()
                ->map(static fn (object $row): array => [
                    'source_key' => (string) $row->source_key,
                    'source_label' => (string) $row->source_label,
                    'content_kind' => (string) $row->content_kind,
                ])
                ->all();

            $enabledKinds = [];

            if ($this->showTafsir) {
                $enabledKinds[] = 'tafsir';
            }

            if ($this->showIrab) {
                $enabledKinds[] = 'irab';
            }

            if (is_object($selectedVerse) && $enabledKinds !== []) {
                $explanationsBuilder = DB::table('quran_verse_explanations')
                    ->select([
                        'source_key',
                        'source_label',
                        'content_kind',
                        'content_text',
                    ])
                    ->where('verse_id', (int) $selectedVerse->id)
                    ->whereIn('content_kind', $enabledKinds)
                    ->whereNotIn('content_text', ['', '-', '—', '–']);

                if ($this->sourceKey !== '') {
                    $explanationsBuilder->where('source_key', $this->sourceKey);
                }

                $rows = $explanationsBuilder
                    ->orderBy('content_kind')
                    ->orderBy('source_label')
                    ->get();

                foreach ($rows as $row) {
                    $contentText = trim((string) $row->content_text);

                    if (! $this->hasMeaningfulExplanation($contentText)) {
                        continue;
                    }

                    $selectedExplanations[] = [
                        'source_key' => (string) $row->source_key,
                        'source_label' => (string) $row->source_label,
                        'content_kind' => (string) $row->content_kind,
                        'content_text' => $contentText,
                    ];
                }
            }
        }

        return view('livewire.quran-reader', [
            'ready' => true,
            'searchQuery' => $searchQuery,
            'searchMatches' => $searchMatches,
            'mushafLines' => $mushafLines,
            'sourceOptions' => $sourceOptions,
            'selectedVerse' => $selectedVerse,
            'selectedExplanations' => $selectedExplanations,
            'pageNumber' => $pageNumber,
            'maxPage' => $maxPage,
            'activeAyahIndex' => $effectiveAyahIndex,
            'selectedSurahTitle' => $selectedSurahTitle,
            'qpcPageFontFamily' => $qpcPageFont['family'] ?? null,
            'qpcPageFontUrl' => $qpcPageFont['url'] ?? null,
            'useCenteredAyahLayout' => $useCenteredAyahLayout,
        ]);
    }

    private function focusAyahByIndex(int $ayahIndex): void
    {
        if ($ayahIndex < 1) {
            return;
        }

        $verse = DB::table('quran_verses')
            ->select(['ayah_index', 'mushaf_page', 'surah_number', 'ayah_number'])
            ->where('ayah_index', $ayahIndex)
            ->first();

        if (! is_object($verse)) {
            return;
        }

        $this->activeAyahIndex = (int) $verse->ayah_index;
        $this->searchSelectionAyahIndex = (int) $verse->ayah_index;

        $mushafPage = (int) $this->resolveDisplayedMushafPage(
            (int) ($verse->surah_number ?? 0),
            (int) ($verse->ayah_number ?? 0),
            $verse->mushaf_page !== null ? (int) $verse->mushaf_page : null,
        );

        if ($mushafPage > 0) {
            $this->pageNumber = $mushafPage;
        }
    }

    private function findSingleSearchMatchAyahIndex(string $query): ?int
    {
        $normalizedQuery = trim($this->normalizeQuranSearchQuery($query));

        if ($normalizedQuery === '') {
            return null;
        }

        $matches = $this->buildSearchMatches(
            $normalizedQuery,
            2,
            Schema::hasTable('quran_words') && Schema::hasColumn('quran_words', 'token_searchable_typed'),
        );

        if (count($matches) !== 1) {
            return null;
        }

        return (int) ($matches[0]['ayah_index'] ?? 0);
    }

    /**
     * @return array<int, array{id: int, ayah_index: int, surah_number: int, surah_title: string, ayah_number: int, mushaf_page: int|null, text_uthmani: string, search_snippet: string}>
     */
    private function buildSearchMatches(string $searchQuery, int $limit, bool $hasTypedWordColumn): array
    {
        $tokens = array_values(array_unique(array_filter(
            preg_split('/\s+/u', trim($searchQuery)) ?: [],
            static fn (string $token): bool => $token !== '',
        )));

        if ($tokens === []) {
            return [];
        }

        $matches = [];
        $seenAyahIndexes = [];
        $exactPhraseVerseIds = $this->collectVerseIdsByExactPhrase($searchQuery, $limit);

        $this->appendVerseMatches($matches, $seenAyahIndexes, $exactPhraseVerseIds, $limit, $searchQuery);

        if (count($matches) >= $limit) {
            return $matches;
        }

        $lemmaVerseIds = $this->collectVerseIdsByExactTokens($tokens, $limit);

        $this->appendVerseMatches($matches, $seenAyahIndexes, $lemmaVerseIds, $limit, $searchQuery);

        if (count($matches) >= $limit) {
            return $matches;
        }

        $shouldUseExpandedRoots = count($tokens) <= 6;
        $shouldUseRootStage = count($tokens) <= 4;

        if ($shouldUseExpandedRoots) {
            $stemVerseIds = $this->collectVerseIdsByStemTokens($tokens, $limit);

            $this->appendVerseMatches($matches, $seenAyahIndexes, $stemVerseIds, $limit, $searchQuery);
        }

        if (count($matches) >= $limit) {
            return $matches;
        }

        if ($shouldUseExpandedRoots && $shouldUseRootStage) {
            $rootVerseIds = $this->collectVerseIdsByRootTokens($tokens, $limit);

            $this->appendVerseMatches($matches, $seenAyahIndexes, $rootVerseIds, $limit, $searchQuery);
        }

        if (count($matches) >= $limit || ! $hasTypedWordColumn) {
            return $matches;
        }

        $wordLikeVerseIds = $this->collectVerseIdsByWordLikeFallback($searchQuery, $limit);

        $this->appendVerseMatches($matches, $seenAyahIndexes, $wordLikeVerseIds, $limit, $searchQuery);

        return $matches;
    }

    /**
     * @return array<int, int>
     */
    private function collectVerseIdsByExactPhrase(string $searchQuery, int $limit): array
    {
        $queryVariants = $this->expandSearchTextVariants($searchQuery);
        $builder = DB::table('quran_verses');

        $builder->where(function (Builder $whereBuilder) use ($queryVariants): void {
            foreach ($queryVariants as $variant) {
                $this->addBoundedPhraseConditions($whereBuilder, 'text_searchable_typed', $variant);
                $this->addBoundedPhraseConditions($whereBuilder, 'text_searchable', $variant);
            }
        });

        return $builder
            ->orderBy('ayah_index')
            ->limit($limit * 6)
            ->pluck('id')
            ->map(static fn (mixed $value): int => (int) $value)
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function collectVerseIdsByWordLikeFallback(string $searchQuery, int $limit): array
    {
        $queryVariants = $this->expandSearchTextVariants($searchQuery);
        $builder = DB::table('quran_words');

        $builder->where(function (Builder $whereBuilder) use ($queryVariants): void {
            foreach ($queryVariants as $variant) {
                $this->addTokenPrefixConditions($whereBuilder, 'token_searchable_typed', $variant);
                $this->addTokenPrefixConditions($whereBuilder, 'token_searchable', $variant);
            }
        });

        return $builder
            ->distinct()
            ->orderBy('ayah_index')
            ->limit($limit * 4)
            ->pluck('verse_id')
            ->map(static fn (mixed $value): int => (int) $value)
            ->all();
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array<int, int>
     */
    private function collectVerseIdsByExactTokens(array $tokens, int $limit): array
    {
        return $this->intersectVerseIdSets(
            array_map(function (string $token) use ($limit): array {
                $tokenVariants = $this->expandSearchTextVariants($token);

                $ids = DB::table('quran_words')
                    ->where(function ($builder) use ($tokenVariants): void {
                        $builder
                            ->whereIn('token_searchable_typed', $tokenVariants)
                            ->orWhereIn('token_searchable', $tokenVariants)
                            ->orWhereIn('token_lemma', $tokenVariants);
                    })
                    ->distinct()
                    ->orderBy('ayah_index')
                    ->limit($limit * 18)
                    ->pluck('verse_id')
                    ->map(static fn (mixed $value): int => (int) $value)
                    ->all();

                return $ids;
            }, $tokens),
            $limit,
        );
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array<int, int>
     */
    private function collectVerseIdsByStemTokens(array $tokens, int $limit): array
    {
        $verseIdSets = [];

        foreach ($tokens as $token) {
            $stemCandidates = $this->resolveStemCandidatesForToken($token);

            if ($stemCandidates === []) {
                return [];
            }

            $verseIdSets[] = DB::table('quran_words')
                ->whereIn('token_stem', $stemCandidates)
                ->distinct()
                ->orderBy('ayah_index')
                ->limit($limit * 18)
                ->pluck('verse_id')
                ->map(static fn (mixed $value): int => (int) $value)
                ->all();
        }

        return $this->intersectVerseIdSets($verseIdSets, $limit);
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array<int, int>
     */
    private function collectVerseIdsByRootTokens(array $tokens, int $limit): array
    {
        $verseIdSets = [];

        foreach ($tokens as $token) {
            $rootCandidates = $this->resolveRootCandidatesForToken($token);

            if ($rootCandidates === []) {
                return [];
            }

            $verseIdSets[] = DB::table('quran_words')
                ->whereIn('token_root', $rootCandidates)
                ->distinct()
                ->orderBy('ayah_index')
                ->limit($limit * 16)
                ->pluck('verse_id')
                ->map(static fn (mixed $value): int => (int) $value)
                ->all();
        }

        return $this->intersectVerseIdSets($verseIdSets, $limit);
    }

    /**
     * @param  array<int, array<int, int>>  $verseIdSets
     * @return array<int, int>
     */
    private function intersectVerseIdSets(array $verseIdSets, int $limit): array
    {
        if ($verseIdSets === []) {
            return [];
        }

        $intersection = null;

        foreach ($verseIdSets as $set) {
            $normalized = array_values(array_unique(array_map(static fn (int $value): int => (int) $value, $set)));

            if ($normalized === []) {
                return [];
            }

            if ($intersection === null) {
                $intersection = $normalized;

                continue;
            }

            $intersection = array_values(array_intersect($intersection, $normalized));

            if ($intersection === []) {
                return [];
            }
        }

        return DB::table('quran_verses')
            ->whereIn('id', $intersection)
            ->orderBy('ayah_index')
            ->limit($limit * 6)
            ->pluck('id')
            ->map(static fn (mixed $value): int => (int) $value)
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function resolveStemCandidatesForToken(string $token): array
    {
        $tokenVariants = $this->expandSearchTextVariants($token);
        $seedCandidates = array_map(static fn (string $value): string => ArabicFilter::forSearch($value), $tokenVariants);

        $dbCandidates = DB::table('quran_words')
            ->where(function ($builder) use ($tokenVariants): void {
                $builder
                    ->whereIn('token_searchable_typed', $tokenVariants)
                    ->orWhereIn('token_lemma', $tokenVariants)
                    ->orWhereIn('token_searchable', $tokenVariants);
            })
            ->whereNotNull('token_stem')
            ->pluck('token_stem')
            ->map(static fn (mixed $value): string => ArabicFilter::forSearch((string) $value))
            ->all();

        return array_values(array_filter(array_unique(array_merge($seedCandidates, $dbCandidates))));
    }

    /**
     * @return array<int, string>
     */
    private function resolveRootCandidatesForToken(string $token): array
    {
        $stemCandidates = $this->resolveStemCandidatesForToken($token);
        $tokenVariants = $this->expandSearchTextVariants($token);

        $dbCandidates = DB::table('quran_words')
            ->where(function ($builder) use ($tokenVariants, $stemCandidates): void {
                $builder
                    ->whereIn('token_searchable_typed', $tokenVariants)
                    ->orWhereIn('token_lemma', $tokenVariants)
                    ->orWhereIn('token_searchable', $tokenVariants);

                if ($stemCandidates !== []) {
                    $builder->orWhereIn('token_stem', $stemCandidates);
                }
            })
            ->whereNotNull('token_root')
            ->pluck('token_root')
            ->map(static fn (mixed $value): string => ArabicFilter::forSearch((string) $value))
            ->all();

        return array_values(array_filter(array_unique($dbCandidates)));
    }

    /**
     * @param  array<int, array{id: int, ayah_index: int, surah_number: int, surah_title: string, ayah_number: int, mushaf_page: int|null, text_uthmani: string, search_snippet: string}>  $matches
     * @param  array<int, true>  $seenAyahIndexes
     * @param  array<int, int>  $verseIds
     */
    private function appendVerseMatches(
        array &$matches,
        array &$seenAyahIndexes,
        array $verseIds,
        int $limit,
        string $searchQuery,
    ): void {
        if ($verseIds === [] || count($matches) >= $limit) {
            return;
        }

        $rows = DB::table('quran_verses')
            ->select(['id', 'ayah_index', 'surah_number', 'ayah_number', 'mushaf_page', 'text_uthmani', 'text_searchable_typed'])
            ->whereIn('id', $verseIds)
            ->orderBy('ayah_index')
            ->get();

        foreach ($rows as $row) {
            $ayahIndex = (int) $row->ayah_index;

            if (isset($seenAyahIndexes[$ayahIndex])) {
                continue;
            }

            $seenAyahIndexes[$ayahIndex] = true;

            $surahNumber = (int) $row->surah_number;
            $ayahNumber = (int) $row->ayah_number;
            $displayPage = $this->resolveDisplayedMushafPage(
                $surahNumber,
                $ayahNumber,
                $row->mushaf_page !== null ? (int) $row->mushaf_page : null,
            );

            $matches[] = [
                'id' => (int) $row->id,
                'ayah_index' => $ayahIndex,
                'surah_number' => $surahNumber,
                'surah_title' => $this->formatSurahTitle($surahNumber),
                'ayah_number' => $ayahNumber,
                'mushaf_page' => $displayPage,
                'text_uthmani' => (string) $row->text_uthmani,
                'search_snippet' => $this->buildSearchSnippet((string) ($row->text_searchable_typed ?? ''), $searchQuery),
            ];

            if (count($matches) >= $limit) {
                return;
            }
        }
    }

    /**
     * @return array<int, array{line_number: int, line_type: string, is_centered: bool, surah_number: int|null, segments: array<int, array{verse_id: int, ayah_index: int, surah_number: int, ayah_number: int, text: string, ends_ayah: bool}>, words: array<int, array{ayah_index: int, surah_number: int, ayah_number: int, text: string, ends_ayah: bool}>, text: string}>
     */
    private function buildPageLines(int $pageNumber): array
    {
        $lineRows = DB::table('quran_mushaf_lines')
            ->select([
                'line_number',
                'line_type',
                'is_centered',
                'first_word_index',
                'last_word_index',
                'surah_number',
            ])
            ->where('page_number', $pageNumber)
            ->orderBy('line_number')
            ->get()
            ->all();

        if ($lineRows === []) {
            return [];
        }

        $wordRangeStart = null;
        $wordRangeEnd = null;

        foreach ($lineRows as $lineRow) {
            if ($lineRow->first_word_index === null || $lineRow->last_word_index === null) {
                continue;
            }

            $lineStart = (int) $lineRow->first_word_index;
            $lineEnd = (int) $lineRow->last_word_index;

            $wordRangeStart = $wordRangeStart === null ? $lineStart : min($wordRangeStart, $lineStart);
            $wordRangeEnd = $wordRangeEnd === null ? $lineEnd : max($wordRangeEnd, $lineEnd);
        }

        $displayWordsByIndex = [];

        if ($wordRangeStart !== null && $wordRangeEnd !== null) {
            $displayWordsByIndex = $this->loadQpcDisplayWordsByIndex($wordRangeStart, $wordRangeEnd + 1);
        }

        if ($displayWordsByIndex === [] && $wordRangeStart !== null && $wordRangeEnd !== null) {
            $fallbackWords = DB::table('quran_words')
                ->select([
                    'global_word_index',
                    'surah_number',
                    'ayah_number',
                    'token_uthmani',
                ])
                ->whereBetween('global_word_index', [$wordRangeStart, $wordRangeEnd + 1])
                ->orderBy('global_word_index')
                ->get();

            foreach ($fallbackWords as $word) {
                $displayWordsByIndex[(int) $word->global_word_index] = [
                    'global_word_index' => (int) $word->global_word_index,
                    'surah_number' => (int) $word->surah_number,
                    'ayah_number' => (int) $word->ayah_number,
                    'text' => trim((string) $word->token_uthmani),
                    'is_glyph' => false,
                ];
            }
        }

        $verseMetaByPair = [];
        $surahNumbers = [];
        $ayahNumbers = [];

        foreach ($displayWordsByIndex as $word) {
            $surahNumbers[(int) $word['surah_number']] = true;
            $ayahNumbers[(int) $word['ayah_number']] = true;
        }

        unset($surahNumbers[0], $ayahNumbers[0]);

        if ($surahNumbers !== [] && $ayahNumbers !== []) {
            $verseRows = DB::table('quran_verses')
                ->select(['id', 'ayah_index', 'surah_number', 'ayah_number'])
                ->whereIn('surah_number', array_keys($surahNumbers))
                ->whereIn('ayah_number', array_keys($ayahNumbers))
                ->get();

            foreach ($verseRows as $verseRow) {
                $pairKey = (int) $verseRow->surah_number.':'.(int) $verseRow->ayah_number;
                $verseMetaByPair[$pairKey] = [
                    'id' => (int) $verseRow->id,
                    'ayah_index' => (int) $verseRow->ayah_index,
                    'surah_number' => (int) $verseRow->surah_number,
                    'ayah_number' => (int) $verseRow->ayah_number,
                ];
            }
        }

        $lines = [];

        foreach ($lineRows as $lineRow) {
            $lineType = (string) $lineRow->line_type;
            $lineNumber = (int) $lineRow->line_number;

            $segments = [];
            $words = [];
            $lineText = '';
            $firstWordIndex = $lineRow->first_word_index !== null ? (int) $lineRow->first_word_index : null;
            $lastWordIndex = $lineRow->last_word_index !== null ? (int) $lineRow->last_word_index : null;

            if ($firstWordIndex !== null && $lastWordIndex !== null) {
                $currentPairKey = null;
                $currentSegmentTokens = [];
                $currentSegmentMeta = null;
                $currentSegmentJoiner = '';
                $currentSegmentEndsAyah = false;

                for ($wordIndex = $firstWordIndex; $wordIndex <= $lastWordIndex; $wordIndex++) {
                    $word = $displayWordsByIndex[$wordIndex] ?? null;

                    if (! is_array($word)) {
                        continue;
                    }

                    $wordSurahNumber = (int) $word['surah_number'];
                    $wordAyahNumber = (int) $word['ayah_number'];
                    $wordText = (string) $word['text'];
                    $pairKey = $wordSurahNumber.':'.$wordAyahNumber;
                    $verseMeta = $verseMetaByPair[$pairKey] ?? null;
                    $nextWord = $displayWordsByIndex[$wordIndex + 1] ?? null;
                    $wordEndsAyah = ! is_array($nextWord)
                        || (int) $nextWord['surah_number'] !== $wordSurahNumber
                        || (int) $nextWord['ayah_number'] !== $wordAyahNumber;

                    $words[] = [
                        'verse_id' => (int) ($verseMeta['id'] ?? 0),
                        'word_index' => (int) $word['global_word_index'],
                        'ayah_index' => (int) ($verseMeta['ayah_index'] ?? 0),
                        'surah_number' => $wordSurahNumber,
                        'ayah_number' => $wordAyahNumber,
                        'text' => $wordText,
                        'is_glyph' => (bool) $word['is_glyph'],
                        'ends_ayah' => $wordEndsAyah,
                    ];

                    if ($currentSegmentMeta !== null && $currentPairKey !== null && $currentPairKey !== $pairKey) {
                        $segments[] = [
                            'verse_id' => (int) $currentSegmentMeta['verse_id'],
                            'ayah_index' => (int) $currentSegmentMeta['ayah_index'],
                            'surah_number' => (int) $currentSegmentMeta['surah_number'],
                            'ayah_number' => (int) $currentSegmentMeta['ayah_number'],
                            'text' => trim(implode($currentSegmentJoiner, $currentSegmentTokens)),
                            'ends_ayah' => $currentSegmentEndsAyah,
                        ];

                        $currentSegmentTokens = [];
                        $currentSegmentMeta = null;
                        $currentSegmentEndsAyah = false;
                    }

                    if ($currentSegmentMeta === null) {
                        $currentSegmentMeta = [
                            'verse_id' => (int) ($verseMeta['id'] ?? 0),
                            'ayah_index' => (int) ($verseMeta['ayah_index'] ?? 0),
                            'surah_number' => $wordSurahNumber,
                            'ayah_number' => $wordAyahNumber,
                        ];
                        $currentSegmentJoiner = ((bool) $word['is_glyph']) ? '' : ' ';
                    }

                    $currentPairKey = $pairKey;
                    $currentSegmentTokens[] = $wordText;
                    $currentSegmentEndsAyah = $wordEndsAyah;
                }

                if ($currentSegmentMeta !== null && $currentSegmentTokens !== []) {
                    $segments[] = [
                        'verse_id' => (int) $currentSegmentMeta['verse_id'],
                        'ayah_index' => (int) $currentSegmentMeta['ayah_index'],
                        'surah_number' => (int) $currentSegmentMeta['surah_number'],
                        'ayah_number' => (int) $currentSegmentMeta['ayah_number'],
                        'text' => trim(implode($currentSegmentJoiner, $currentSegmentTokens)),
                        'ends_ayah' => $currentSegmentEndsAyah,
                    ];
                }

                $lineText = trim(implode(' ', array_map(static fn (array $segment): string => $segment['text'], $segments)));
            }

            if ($lineText === '' && $lineType === 'basmallah') {
                $lineText = 'بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ';
            }

            if ($lineText === '' && $lineType === 'surah_name') {
                $surahNumber = $lineRow->surah_number !== null ? (int) $lineRow->surah_number : null;
                $lineText = $this->formatSurahTitle($surahNumber ?? 0);
            }

            $lines[] = [
                'line_number' => $lineNumber,
                'line_type' => $lineType,
                'is_centered' => ((int) $lineRow->is_centered) === 1,
                'surah_number' => $lineRow->surah_number !== null ? (int) $lineRow->surah_number : null,
                'segments' => $segments,
                'words' => $words,
                'text' => $lineText,
            ];
        }

        return $this->applyOpeningSpreadCorrections($lines);
    }

    /**
     * @param  array<int, array{line_number: int, line_type: string, is_centered: bool, surah_number: int|null, segments: array<int, array{verse_id: int, ayah_index: int, surah_number: int, ayah_number: int, text: string, ends_ayah: bool}>, words: array<int, array{ayah_index: int, surah_number: int, ayah_number: int, text: string, ends_ayah: bool}>, text: string}>  $lines
     * @return array<int, array{line_number: int, line_type: string, is_centered: bool, surah_number: int|null, segments: array<int, array{verse_id: int, ayah_index: int, surah_number: int, ayah_number: int, text: string, ends_ayah: bool}>, words: array<int, array{ayah_index: int, surah_number: int, ayah_number: int, text: string, ends_ayah: bool}>, text: string}>
     */
    protected function injectSurahTransitions(array $lines): array
    {
        $result = [];
        $lineCursor = 0;
        $currentSurahNumber = null;

        foreach ($lines as $line) {
            if ($line['line_type'] === 'surah_name') {
                $currentSurahNumber = $line['surah_number'];
                $result[] = $line;

                continue;
            }

            $lineSurahNumber = null;

            if ($line['segments'] !== []) {
                $lineSurahNumber = (int) $line['segments'][0]['surah_number'];
                $lineSurahNumber = $lineSurahNumber > 0 ? $lineSurahNumber : null;
            }

            if ($lineSurahNumber !== null) {
                if ($currentSurahNumber !== null && $lineSurahNumber !== $currentSurahNumber) {
                    $lineCursor++;
                    $result[] = [
                        'line_number' => -1000 - $lineCursor,
                        'line_type' => 'surah_name',
                        'is_centered' => true,
                        'surah_number' => $lineSurahNumber,
                        'segments' => [],
                        'words' => [],
                        'text' => $this->formatSurahTitle($lineSurahNumber),
                    ];

                    if ($lineSurahNumber !== 9) {
                        $lineCursor++;
                        $result[] = [
                            'line_number' => -1000 - $lineCursor,
                            'line_type' => 'basmallah',
                            'is_centered' => true,
                            'surah_number' => $lineSurahNumber,
                            'segments' => [],
                            'words' => [],
                            'text' => 'بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ',
                        ];
                    }
                }

                $currentSurahNumber = $lineSurahNumber;
            }

            $result[] = $line;
        }

        return $result;
    }

    /**
     * @param  array<int, array{line_number: int, line_type: string, is_centered: bool, surah_number: int|null, segments: array<int, array{verse_id: int, ayah_index: int, surah_number: int, ayah_number: int, text: string, ends_ayah: bool}>, words: array<int, array{ayah_index: int, surah_number: int, ayah_number: int, text: string, ends_ayah: bool}>, text: string}>  $lines
     * @return array<int, array{line_number: int, line_type: string, is_centered: bool, surah_number: int|null, segments: array<int, array{verse_id: int, ayah_index: int, surah_number: int, ayah_number: int, text: string, ends_ayah: bool}>, words: array<int, array{ayah_index: int, surah_number: int, ayah_number: int, text: string, ends_ayah: bool}>, text: string}>
     */
    private function applyOpeningSpreadCorrections(array $lines): array
    {
        return array_values(array_filter(
            $lines,
            static fn (array $line): bool => $line['line_type'] !== 'ayah' || $line['segments'] !== [],
        ));
    }

    /**
     * @param  array<int, array{line_number: int, line_type: string, is_centered: bool, surah_number: int|null, segments: array<int, array{verse_id: int, ayah_index: int, surah_number: int, ayah_number: int, text: string, ends_ayah: bool}>, words: array<int, array{ayah_index: int, surah_number: int, ayah_number: int, text: string, ends_ayah: bool}>, text: string}>  $lines
     * @return array<int, array{line_number: int, line_type: string, is_centered: bool, surah_number: int|null, segments: array<int, array{verse_id: int, ayah_index: int, surah_number: int, ayah_number: int, text: string, ends_ayah: bool}>, words: array<int, array{ayah_index: int, surah_number: int, ayah_number: int, text: string, ends_ayah: bool}>, text: string}>
     */
    protected function filterLinesToSingleSurah(array $lines, int $surahNumber): array
    {
        $filtered = [];

        foreach ($lines as $line) {
            if (
                in_array($line['line_type'], ['surah_name', 'basmallah'], true)
                && $line['surah_number'] !== null
                && (int) $line['surah_number'] !== $surahNumber
            ) {
                continue;
            }

            if ($line['segments'] !== []) {
                $segments = array_values(array_filter(
                    $line['segments'],
                    static fn (array $segment): bool => (int) $segment['surah_number'] === $surahNumber,
                ));

                if ($segments === []) {
                    continue;
                }

                $line['segments'] = $segments;
            }

            if ($line['words'] !== []) {
                $line['words'] = array_values(array_filter(
                    $line['words'],
                    static fn (array $word): bool => (int) $word['surah_number'] === $surahNumber,
                ));
            }

            if ($line['segments'] !== []) {
                $line['text'] = trim(implode(' ', array_map(static fn (array $segment): string => $segment['text'], $line['segments'])));
            } elseif ($line['words'] !== []) {
                $line['text'] = trim(implode(' ', array_map(static fn (array $word): string => $word['text'], $line['words'])));
            }

            $filtered[] = $line;
        }

        return $filtered;
    }

    /**
     * @return array<int, array{line_number: int, line_type: string, is_centered: bool, surah_number: int|null, segments: array<int, array{verse_id: int, ayah_index: int, surah_number: int, ayah_number: int, text: string, ends_ayah: bool}>, words: array<int, array{ayah_index: int, surah_number: int, ayah_number: int, text: string, ends_ayah: bool}>, text: string}>
     */
    protected function buildPageTwoCarryOverLines(): array
    {
        $currentMinWordIndex = DB::table('quran_mushaf_lines')
            ->where('page_number', 2)
            ->whereNotNull('first_word_index')
            ->min('first_word_index');

        if (! is_numeric($currentMinWordIndex)) {
            return [];
        }

        $carryRows = DB::table('quran_mushaf_lines')
            ->select(['line_number', 'first_word_index', 'last_word_index'])
            ->where('page_number', 1)
            ->whereNotNull('first_word_index')
            ->whereNotNull('last_word_index')
            ->orderBy('line_number')
            ->get()
            ->all();

        if ($carryRows === []) {
            return [];
        }

        $words = DB::table('quran_words')
            ->select([
                'global_word_index',
                'verse_id',
                'surah_number',
                'ayah_number',
                'ayah_index',
                'token_uthmani',
            ])
            ->where('surah_number', 2)
            ->where('global_word_index', '<', (int) $currentMinWordIndex)
            ->orderBy('global_word_index')
            ->get();

        if ($words->isEmpty()) {
            return [];
        }

        $wordsByIndex = [];
        $verseIds = [];

        foreach ($words as $word) {
            $index = (int) $word->global_word_index;
            $wordsByIndex[$index] = $word;
            $verseIds[(int) $word->verse_id] = true;
        }

        $verseEndWordIndexes = DB::table('quran_words')
            ->selectRaw('verse_id, MAX(global_word_index) AS last_word_index')
            ->whereIn('verse_id', array_keys($verseIds))
            ->groupBy('verse_id')
            ->pluck('last_word_index', 'verse_id')
            ->mapWithKeys(static fn (mixed $value, mixed $key): array => [(int) $key => (int) $value])
            ->all();

        $carryLines = [];
        $lineOffset = 0;

        foreach ($carryRows as $row) {
            $firstWordIndex = (int) $row->first_word_index;
            $lastWordIndex = (int) $row->last_word_index;
            $segments = [];
            $lineWords = [];
            $currentSegmentMeta = null;
            $currentSegmentTokens = [];
            $currentSegmentLastWordIndex = null;

            for ($wordIndex = $firstWordIndex; $wordIndex <= $lastWordIndex; $wordIndex++) {
                $word = $wordsByIndex[$wordIndex] ?? null;

                if (! is_object($word)) {
                    continue;
                }

                $wordVerseId = (int) $word->verse_id;
                $verseLastWordIndex = $verseEndWordIndexes[$wordVerseId] ?? 0;
                $wordEndsAyah = $verseLastWordIndex > 0 && $wordIndex >= $verseLastWordIndex;

                $lineWords[] = [
                    'ayah_index' => (int) $word->ayah_index,
                    'surah_number' => (int) $word->surah_number,
                    'ayah_number' => (int) $word->ayah_number,
                    'text' => trim((string) $word->token_uthmani),
                    'ends_ayah' => $wordEndsAyah,
                ];

                if (
                    $currentSegmentMeta !== null
                    && (int) $currentSegmentMeta['verse_id'] !== $wordVerseId
                ) {
                    $segmentVerseId = (int) $currentSegmentMeta['verse_id'];
                    $segmentLastWordIndex = $currentSegmentLastWordIndex ?? 0;
                    $verseLastWordIndex = $verseEndWordIndexes[$segmentVerseId] ?? 0;

                    $segments[] = [
                        'verse_id' => $segmentVerseId,
                        'ayah_index' => (int) $currentSegmentMeta['ayah_index'],
                        'surah_number' => (int) $currentSegmentMeta['surah_number'],
                        'ayah_number' => (int) $currentSegmentMeta['ayah_number'],
                        'text' => trim(implode(' ', $currentSegmentTokens)),
                        'ends_ayah' => $segmentLastWordIndex > 0 && $segmentLastWordIndex >= $verseLastWordIndex,
                    ];

                    $currentSegmentMeta = null;
                    $currentSegmentTokens = [];
                    $currentSegmentLastWordIndex = null;
                }

                if ($currentSegmentMeta === null) {
                    $currentSegmentMeta = [
                        'verse_id' => $wordVerseId,
                        'ayah_index' => (int) $word->ayah_index,
                        'surah_number' => (int) $word->surah_number,
                        'ayah_number' => (int) $word->ayah_number,
                    ];
                }

                $currentSegmentTokens[] = trim((string) $word->token_uthmani);
                $currentSegmentLastWordIndex = $wordIndex;
            }

            if ($currentSegmentMeta !== null && $currentSegmentTokens !== []) {
                $segmentVerseId = (int) $currentSegmentMeta['verse_id'];
                $segmentLastWordIndex = $currentSegmentLastWordIndex ?? 0;
                $verseLastWordIndex = $verseEndWordIndexes[$segmentVerseId] ?? 0;

                $segments[] = [
                    'verse_id' => $segmentVerseId,
                    'ayah_index' => (int) $currentSegmentMeta['ayah_index'],
                    'surah_number' => (int) $currentSegmentMeta['surah_number'],
                    'ayah_number' => (int) $currentSegmentMeta['ayah_number'],
                    'text' => trim(implode(' ', $currentSegmentTokens)),
                    'ends_ayah' => $segmentLastWordIndex > 0 && $segmentLastWordIndex >= $verseLastWordIndex,
                ];
            }

            if ($segments === []) {
                continue;
            }

            $lineOffset++;
            $carryLines[] = [
                'line_number' => -3000 - $lineOffset,
                'line_type' => 'ayah',
                'is_centered' => true,
                'surah_number' => 2,
                'segments' => $segments,
                'words' => $lineWords,
                'text' => trim(implode(' ', array_map(static fn (array $segment): string => $segment['text'], $segments))),
            ];
        }

        return $carryLines;
    }

    /**
     * @return array<int, array{line_number: int, line_type: string, is_centered: bool, surah_number: int|null, segments: array<int, array{verse_id: int, ayah_index: int, surah_number: int, ayah_number: int, text: string, ends_ayah: bool}>, words: array<int, array{ayah_index: int, surah_number: int, ayah_number: int, text: string, ends_ayah: bool}>, text: string}>
     */
    protected function buildFixedPageThreeLines(): array
    {
        return $this->buildAyahLinesFromWordRanges([
            [66, 74],
            [75, 86],
            [87, 96],
            [97, 104],
            [105, 114],
            [115, 122],
            [123, 131],
            [132, 141],
            [142, 152],
            [153, 161],
            [162, 170],
            [171, 180],
            [181, 192],
            [193, 203],
            [204, 215],
        ]);
    }

    /**
     * @param  array<int, array{line_number: int, line_type: string, is_centered: bool, surah_number: int|null, segments: array<int, array{verse_id: int, ayah_index: int, surah_number: int, ayah_number: int, text: string, ends_ayah: bool}>, words: array<int, array{ayah_index: int, surah_number: int, ayah_number: int, text: string, ends_ayah: bool}>, text: string}>  $lines
     * @return array<int, array{line_number: int, line_type: string, is_centered: bool, surah_number: int|null, segments: array<int, array{verse_id: int, ayah_index: int, surah_number: int, ayah_number: int, text: string, ends_ayah: bool}>, words: array<int, array{ayah_index: int, surah_number: int, ayah_number: int, text: string, ends_ayah: bool}>, text: string}>
     */
    protected function filterLinesToAyahRange(array $lines, int $surahNumber, int $fromAyah, int $toAyah): array
    {
        $filtered = [];

        foreach ($lines as $line) {
            if ($line['segments'] !== []) {
                $segments = array_values(array_filter(
                    $line['segments'],
                    static fn (array $segment): bool => (int) $segment['surah_number'] === $surahNumber
                        && (int) $segment['ayah_number'] >= $fromAyah
                        && (int) $segment['ayah_number'] <= $toAyah,
                ));

                if ($segments === []) {
                    continue;
                }

                $line['segments'] = $segments;
            }

            if ($line['words'] !== []) {
                $line['words'] = array_values(array_filter(
                    $line['words'],
                    static fn (array $word): bool => (int) $word['surah_number'] === $surahNumber
                        && (int) $word['ayah_number'] >= $fromAyah
                        && (int) $word['ayah_number'] <= $toAyah,
                ));
            }

            if ($line['segments'] !== []) {
                $line['text'] = trim(implode(' ', array_map(static fn (array $segment): string => $segment['text'], $line['segments'])));
            } elseif ($line['words'] !== []) {
                $line['text'] = trim(implode(' ', array_map(static fn (array $word): string => $word['text'], $line['words'])));
            }

            $filtered[] = $line;
        }

        return $filtered;
    }

    /**
     * @param  array<int, array{0: int, 1: int}>  $wordRanges
     * @return array<int, array{line_number: int, line_type: string, is_centered: bool, surah_number: int|null, segments: array<int, array{verse_id: int, ayah_index: int, surah_number: int, ayah_number: int, text: string, ends_ayah: bool}>, words: array<int, array{ayah_index: int, surah_number: int, ayah_number: int, text: string, ends_ayah: bool}>, text: string}>
     */
    private function buildAyahLinesFromWordRanges(array $wordRanges): array
    {
        if ($wordRanges === []) {
            return [];
        }

        $start = min(array_map(static fn (array $range): int => (int) $range[0], $wordRanges));
        $end = max(array_map(static fn (array $range): int => (int) $range[1], $wordRanges));

        if ($start < 1 || $end < $start) {
            return [];
        }

        $wordRows = DB::table('quran_words')
            ->select([
                'verse_id',
                'surah_number',
                'ayah_number',
                'ayah_index',
                'global_word_index',
                'token_uthmani',
            ])
            ->whereBetween('global_word_index', [$start, $end])
            ->orderBy('global_word_index')
            ->get()
            ->all();

        if ($wordRows === []) {
            return [];
        }

        $wordsByIndex = [];
        $verseIds = [];

        foreach ($wordRows as $wordRow) {
            $wordIndex = (int) $wordRow->global_word_index;
            $wordsByIndex[$wordIndex] = $wordRow;
            $verseIds[(int) $wordRow->verse_id] = true;
        }

        $verseEndWordIndexes = DB::table('quran_words')
            ->selectRaw('verse_id, MAX(global_word_index) AS last_word_index')
            ->whereIn('verse_id', array_keys($verseIds))
            ->groupBy('verse_id')
            ->pluck('last_word_index', 'verse_id')
            ->mapWithKeys(static fn (mixed $value, mixed $key): array => [(int) $key => (int) $value])
            ->all();

        $lines = [];
        $lineNumber = 0;

        foreach ($wordRanges as $range) {
            $firstWordIndex = (int) $range[0];
            $lastWordIndex = (int) $range[1];

            if ($firstWordIndex < 1 || $lastWordIndex < $firstWordIndex) {
                continue;
            }

            $lineNumber++;
            $segments = [];
            $lineWords = [];
            $currentSegmentMeta = null;
            $currentSegmentTokens = [];
            $currentSegmentLastWordIndex = null;

            for ($wordIndex = $firstWordIndex; $wordIndex <= $lastWordIndex; $wordIndex++) {
                $word = $wordsByIndex[$wordIndex] ?? null;

                if (! is_object($word)) {
                    continue;
                }

                $wordVerseId = (int) $word->verse_id;
                $verseLastWordIndex = $verseEndWordIndexes[$wordVerseId] ?? 0;
                $wordEndsAyah = $verseLastWordIndex > 0 && $wordIndex >= $verseLastWordIndex;

                $lineWords[] = [
                    'ayah_index' => (int) $word->ayah_index,
                    'surah_number' => (int) $word->surah_number,
                    'ayah_number' => (int) $word->ayah_number,
                    'text' => trim((string) $word->token_uthmani),
                    'ends_ayah' => $wordEndsAyah,
                ];

                if ($currentSegmentMeta !== null && (int) $currentSegmentMeta['verse_id'] !== $wordVerseId) {
                    $segmentVerseId = (int) $currentSegmentMeta['verse_id'];
                    $segmentLastWordIndex = $currentSegmentLastWordIndex ?? 0;
                    $segmentVerseLastWordIndex = $verseEndWordIndexes[$segmentVerseId] ?? 0;

                    $segments[] = [
                        'verse_id' => $segmentVerseId,
                        'ayah_index' => (int) $currentSegmentMeta['ayah_index'],
                        'surah_number' => (int) $currentSegmentMeta['surah_number'],
                        'ayah_number' => (int) $currentSegmentMeta['ayah_number'],
                        'text' => trim(implode(' ', $currentSegmentTokens)),
                        'ends_ayah' => $segmentLastWordIndex > 0 && $segmentLastWordIndex >= $segmentVerseLastWordIndex,
                    ];

                    $currentSegmentMeta = null;
                    $currentSegmentTokens = [];
                    $currentSegmentLastWordIndex = null;
                }

                if ($currentSegmentMeta === null) {
                    $currentSegmentMeta = [
                        'verse_id' => $wordVerseId,
                        'ayah_index' => (int) $word->ayah_index,
                        'surah_number' => (int) $word->surah_number,
                        'ayah_number' => (int) $word->ayah_number,
                    ];
                }

                $currentSegmentTokens[] = trim((string) $word->token_uthmani);
                $currentSegmentLastWordIndex = $wordIndex;
            }

            if ($currentSegmentMeta !== null && $currentSegmentTokens !== []) {
                $segmentVerseId = (int) $currentSegmentMeta['verse_id'];
                $segmentLastWordIndex = $currentSegmentLastWordIndex ?? 0;
                $segmentVerseLastWordIndex = $verseEndWordIndexes[$segmentVerseId] ?? 0;

                $segments[] = [
                    'verse_id' => $segmentVerseId,
                    'ayah_index' => (int) $currentSegmentMeta['ayah_index'],
                    'surah_number' => (int) $currentSegmentMeta['surah_number'],
                    'ayah_number' => (int) $currentSegmentMeta['ayah_number'],
                    'text' => trim(implode(' ', $currentSegmentTokens)),
                    'ends_ayah' => $segmentLastWordIndex > 0 && $segmentLastWordIndex >= $segmentVerseLastWordIndex,
                ];
            }

            if ($lineWords === []) {
                continue;
            }

            $lines[] = [
                'line_number' => $lineNumber,
                'line_type' => 'ayah',
                'is_centered' => false,
                'surah_number' => 2,
                'segments' => $segments,
                'words' => $lineWords,
                'text' => trim(implode(' ', array_map(static fn (array $word): string => $word['text'], $lineWords))),
            ];
        }

        return $lines;
    }

    private function firstAyahIndexInPage(array $mushafLines): ?int
    {
        foreach ($mushafLines as $line) {
            $segments = $line['segments'] ?? [];

            if ($segments === []) {
                continue;
            }

            return (int) ($segments[0]['ayah_index'] ?? 0);
        }

        return null;
    }

    private function normalizeQuranSearchQuery(string $text): string
    {
        $prepared = strtr($text, [
            'ٱ' => 'ا',
            'ٲ' => 'ا',
            'ٳ' => 'ا',
            'ٵ' => 'ا',
            'ی' => 'ي',
            'ى' => 'ي',
            'ے' => 'ي',
            'ۍ' => 'ي',
            'ې' => 'ي',
            'ۑ' => 'ي',
            'ک' => 'ك',
        ]);

        $prepared = preg_replace('/([\p{Arabic}])\x{0670}/u', '$1ا', $prepared) ?? $prepared;
        $prepared = preg_replace('/\x{0670}/u', 'ا', $prepared) ?? $prepared;

        $normalized = ArabicFilter::forSearch($prepared);

        return strtr($normalized, [
            'الرحمان' => 'الرحمن',
            'رحمان' => 'رحمن',
            'الصلوة' => 'الصلاة',
            'صلوة' => 'صلاة',
            'الزكوة' => 'الزكاة',
            'زكوة' => 'زكاة',
            'الحيوة' => 'الحياة',
            'حيوة' => 'حياة',
            'الربوا' => 'الربا',
            'ربوا' => 'ربا',
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function expandSearchTextVariants(string $text): array
    {
        $trimmed = trim($text);

        if ($trimmed === '') {
            return [];
        }

        $withoutConjunctions = $this->stripLeadingConjunctionsFromPhrase($trimmed);

        $variants = [
            $trimmed,
            strtr($trimmed, ['ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک']),
            strtr($trimmed, ['ی' => 'ي', 'ى' => 'ي', 'ک' => 'ك']),
            strtr($trimmed, ['الرحمن' => 'الرحمان', 'رحمن' => 'رحمان']),
            strtr($trimmed, ['الرحمان' => 'الرحمن', 'رحمان' => 'رحمن']),
            $withoutConjunctions,
            $this->normalizeQuestionVerbSpellingsInPhrase($trimmed),
            $this->normalizeQuestionVerbSpellingsInPhrase($withoutConjunctions),
        ];

        $normalized = [];

        foreach ($variants as $variant) {
            $value = trim($variant);

            if ($value === '') {
                continue;
            }

            $normalized[$value] = true;
        }

        return array_keys($normalized);
    }

    private function normalizeQuestionVerbSpellingsInPhrase(string $text): string
    {
        $tokens = preg_split('/\s+/u', trim($text)) ?: [];

        if ($tokens === []) {
            return '';
        }

        $normalized = [];

        foreach ($tokens as $token) {
            $normalized[] = $this->normalizeQuestionVerbToken($token);
        }

        return trim(implode(' ', $normalized));
    }

    private function normalizeQuestionVerbToken(string $token): string
    {
        $trimmed = trim($token);

        if ($trimmed === '') {
            return '';
        }

        $patterns = [
            '/^فاسال/u' => 'فسل',
            '/^فسال/u' => 'فسل',
            '/^واسال/u' => 'وسل',
            '/^وسال/u' => 'وسل',
            '/^اسال/u' => 'سل',
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (preg_match($pattern, $trimmed) !== 1) {
                continue;
            }

            return preg_replace($pattern, $replacement, $trimmed) ?? $trimmed;
        }

        return $trimmed;
    }

    private function addBoundedPhraseConditions(Builder $builder, string $column, string $variant): void
    {
        $builder
            ->orWhere($column, $variant)
            ->orWhere($column, 'like', $variant.' %')
            ->orWhere($column, 'like', '% '.$variant)
            ->orWhere($column, 'like', '% '.$variant.' %');
    }

    private function addTokenPrefixConditions(Builder $builder, string $column, string $variant): void
    {
        $builder
            ->orWhere($column, $variant)
            ->orWhere($column, 'like', $variant.'%');
    }

    /**
     * @param  array<int, array{line_number: int, line_type: string, is_centered: bool, surah_number: int|null, segments: array<int, array{verse_id: int, ayah_index: int, surah_number: int, ayah_number: int, text: string, ends_ayah: bool}>, words: array<int, array{ayah_index: int, surah_number: int, ayah_number: int, text: string, ends_ayah: bool}>, text: string}>  $mushafLines
     */
    private function shouldUseCenteredAyahLayout(int $pageNumber, array $mushafLines): bool
    {
        if ($pageNumber <= 2) {
            return true;
        }

        $surahHeaderCount = count(array_filter(
            $mushafLines,
            static fn (array $line): bool => $line['line_type'] === 'surah_name',
        ));

        return $surahHeaderCount >= 2 && $pageNumber >= 587;
    }

    private function stripLeadingConjunctionsFromPhrase(string $text): string
    {
        $tokens = preg_split('/\s+/u', trim($text)) ?: [];
        $normalized = [];

        foreach ($tokens as $token) {
            $normalized[] = $this->stripLeadingConjunction($token);
        }

        return trim(implode(' ', $normalized));
    }

    private function stripLeadingConjunction(string $token): string
    {
        $trimmed = trim($token);

        if (mb_strlen($trimmed) < 3) {
            return $trimmed;
        }

        if (preg_match('/^[وف][\p{Arabic}]/u', $trimmed) !== 1) {
            return $trimmed;
        }

        return mb_substr($trimmed, 1);
    }

    private function formatSurahTitle(int $surahNumber): string
    {
        if ($surahNumber < 1) {
            return 'سورة';
        }

        $arabicName = $this->resolveSurahArabicName($surahNumber);

        if ($arabicName === null || $arabicName === '') {
            return 'سورة ('.$surahNumber.')';
        }

        return 'سورة '.$arabicName.' ('.$surahNumber.')';
    }

    private function resolveSurahArabicName(int $surahNumber): ?string
    {
        $surahNames = $this->loadSurahArabicNames();

        return $surahNames[$surahNumber] ?? null;
    }

    /**
     * @return array<int, string>
     */
    private function loadSurahArabicNames(): array
    {
        static $cached = null;

        if (is_array($cached)) {
            return $cached;
        }

        $candidates = [
            base_path('resources/raw-data/quran/layouts/quran-metadata-surah-name.json'),
            dirname(base_path()).'/resources/raw-data/quran/layouts/quran-metadata-surah-name.json',
            base_path('vendor/goodm4ven/arabicable/resources/raw-data/quran/layouts/quran-metadata-surah-name.json'),
        ];

        foreach ($candidates as $candidate) {
            if (! is_file($candidate)) {
                continue;
            }

            $decoded = json_decode((string) file_get_contents($candidate), true);

            if (! is_array($decoded)) {
                continue;
            }

            $surahNames = [];

            foreach ($decoded as $key => $value) {
                $surahNumber = is_numeric($key) ? (int) $key : 0;
                $nameArabic = is_array($value) ? trim((string) ($value['name_arabic'] ?? '')) : '';

                if ($surahNumber < 1 || $nameArabic === '') {
                    continue;
                }

                $surahNames[$surahNumber] = $nameArabic;
            }

            if ($surahNames !== []) {
                $cached = $surahNames;

                return $cached;
            }
        }

        $cached = [];

        return $cached;
    }

    /**
     * @return array{family: string, url: string}|null
     */
    private function resolveQpcPageFont(int $pageNumber): ?array
    {
        if ($pageNumber < 1 || $pageNumber > 604) {
            return null;
        }

        $candidates = [
            base_path('resources/raw-data/quran/fonts/qpc-v2/p'.$pageNumber.'.ttf'),
            dirname(base_path()).'/resources/raw-data/quran/fonts/qpc-v2/p'.$pageNumber.'.ttf',
            base_path('vendor/goodm4ven/arabicable/resources/raw-data/quran/fonts/qpc-v2/p'.$pageNumber.'.ttf'),
        ];

        $fontPath = null;

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                $fontPath = $candidate;

                break;
            }
        }

        if (! is_string($fontPath) || $fontPath === '') {
            return null;
        }

        return [
            'family' => 'QpcPage'.$pageNumber,
            'url' => route('qpc-v2-font', ['page' => $pageNumber]),
        ];
    }

    /**
     * @return array<int, array{global_word_index: int, surah_number: int, ayah_number: int, text: string, is_glyph: bool}>
     */
    private function loadQpcDisplayWordsByIndex(int $startIndex, int $endIndex): array
    {
        $databasePath = $this->resolveQpcDisplayWordsDatabasePath();

        if ($databasePath === null) {
            return [];
        }

        if ($startIndex < 1 || $endIndex < $startIndex) {
            return [];
        }

        $database = new \SQLite3($databasePath, SQLITE3_OPEN_READONLY);
        $statement = $database->prepare('SELECT id, surah, ayah, text FROM words WHERE id BETWEEN :start AND :end ORDER BY id');

        if (! $statement instanceof \SQLite3Stmt) {
            $database->close();

            return [];
        }

        $statement->bindValue(':start', $startIndex, SQLITE3_INTEGER);
        $statement->bindValue(':end', $endIndex, SQLITE3_INTEGER);

        $result = $statement->execute();

        if (! $result instanceof \SQLite3Result) {
            $statement->close();
            $database->close();

            return [];
        }

        $wordsByIndex = [];

        while (true) {
            $row = $result->fetchArray(SQLITE3_ASSOC);

            if (! is_array($row)) {
                break;
            }

            $wordIndex = (int) ($row['id'] ?? 0);
            $wordText = (string) ($row['text'] ?? '');

            if ($wordIndex < 1 || $wordText === '') {
                continue;
            }

            $wordsByIndex[$wordIndex] = [
                'global_word_index' => $wordIndex,
                'surah_number' => (int) ($row['surah'] ?? 0),
                'ayah_number' => (int) ($row['ayah'] ?? 0),
                'text' => $wordText,
                'is_glyph' => true,
            ];
        }

        $result->finalize();
        $statement->close();
        $database->close();

        return $wordsByIndex;
    }

    private function resolveQpcDisplayWordsDatabasePath(): ?string
    {
        $candidates = [
            base_path('resources/raw-data/quran/layouts/qpc-v2.db'),
            dirname(base_path()).'/resources/raw-data/quran/layouts/qpc-v2.db',
            base_path('vendor/goodm4ven/arabicable/resources/raw-data/quran/layouts/qpc-v2.db'),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function resolveDisplayedMushafPage(int $surahNumber, int $ayahNumber, ?int $mushafPage): ?int
    {
        $qpcPage = $this->resolveMushafPageFromQpcWords($surahNumber, $ayahNumber);

        if ($qpcPage !== null) {
            return $qpcPage;
        }

        return $mushafPage;
    }

    private function resolveMushafPageFromQpcWords(int $surahNumber, int $ayahNumber): ?int
    {
        if ($surahNumber < 1 || $ayahNumber < 1) {
            return null;
        }

        $databasePath = $this->resolveQpcDisplayWordsDatabasePath();

        if ($databasePath === null) {
            return null;
        }

        $database = new \SQLite3($databasePath, SQLITE3_OPEN_READONLY);
        $statement = $database->prepare('SELECT MIN(id) AS first_word_index FROM words WHERE surah = :surah AND ayah = :ayah');

        if (! $statement instanceof \SQLite3Stmt) {
            $database->close();

            return null;
        }

        $statement->bindValue(':surah', $surahNumber, SQLITE3_INTEGER);
        $statement->bindValue(':ayah', $ayahNumber, SQLITE3_INTEGER);
        $result = $statement->execute();

        if (! $result instanceof \SQLite3Result) {
            $statement->close();
            $database->close();

            return null;
        }

        $row = $result->fetchArray(SQLITE3_ASSOC);
        $firstWordIndex = is_array($row) ? (int) ($row['first_word_index'] ?? 0) : 0;

        $result->finalize();
        $statement->close();
        $database->close();

        if ($firstWordIndex < 1) {
            return null;
        }

        $pageNumber = DB::table('quran_mushaf_lines')
            ->whereNotNull('first_word_index')
            ->whereNotNull('last_word_index')
            ->where('first_word_index', '<=', $firstWordIndex)
            ->where('last_word_index', '>=', $firstWordIndex)
            ->orderBy('page_number')
            ->value('page_number');

        return is_numeric($pageNumber) ? (int) $pageNumber : null;
    }

    private function buildSearchSnippet(string $normalizedVerseText, string $searchQuery): string
    {
        $verseText = trim($normalizedVerseText);
        $query = trim($searchQuery);

        if ($verseText === '') {
            return '';
        }

        if ($query === '') {
            return mb_strlen($verseText) > 90 ? mb_substr($verseText, 0, 90).'…' : $verseText;
        }

        $position = mb_strpos($verseText, $query);

        if ($position === false) {
            foreach ($this->expandSearchTextVariants($query) as $variant) {
                $position = mb_strpos($verseText, $variant);

                if ($position !== false) {
                    $query = $variant;

                    break;
                }
            }
        }

        if ($position === false) {
            foreach (array_values(array_filter(preg_split('/\s+/u', $query) ?: [])) as $token) {
                foreach ($this->expandSearchTextVariants($token) as $variantToken) {
                    $position = mb_strpos($verseText, $variantToken);

                    if ($position !== false) {
                        $query = $variantToken;

                        break 2;
                    }
                }
            }
        }

        if ($position === false) {
            $snippet = mb_strlen($verseText) > 90 ? mb_substr($verseText, 0, 90).'…' : $verseText;

            return '“'.$snippet.'”';
        }

        $queryLength = max(1, mb_strlen($query));
        $contextBefore = 24;
        $contextAfter = 34;
        $start = max(0, $position - $contextBefore);
        $length = min(mb_strlen($verseText) - $start, $contextBefore + $queryLength + $contextAfter);
        $snippet = trim(mb_substr($verseText, $start, $length));

        if ($start > 0) {
            $snippet = '…'.$snippet;
        }

        if (($start + $length) < mb_strlen($verseText)) {
            $snippet .= '…';
        }

        return '“'.$snippet.'”';
    }

    private function hasMeaningfulExplanation(string $value): bool
    {
        $normalized = trim($value);

        if ($normalized === '') {
            return false;
        }

        return ! in_array($normalized, ['-', '—', '–', 'ـ'], true);
    }
}
