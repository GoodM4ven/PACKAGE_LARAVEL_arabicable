<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable;

use GoodMaven\Arabicable\Enums\ArabicLinguisticConcept;
use GoodMaven\Arabicable\Enums\ArabicSpecialCharacters;
use GoodMaven\Arabicable\Enums\CommonArabicTextType;
use GoodMaven\Arabicable\Facades\ArabicFilter;
use GoodMaven\Arabicable\Models\CommonArabicText;
use GoodMaven\Arabicable\Support\Concerns\HandlesNumerals;
use GoodMaven\Arabicable\Support\Concerns\HandlesPunctuation;
use GoodMaven\Arabicable\Support\Concerns\HandlesSpaces;
use GoodMaven\Arabicable\Support\Config\ArabicableConfig;
use GoodMaven\Arabicable\Support\Text\ArabicStemmer;
use GoodMaven\Arabicable\Support\Text\ArabicStopWords;
use GoodMaven\Arabicable\Support\Text\ArabicVocalizations;
use GoodMaven\Arabicable\Support\Text\ArabicWordVariants;
use GoodMaven\Arabicable\Support\Text\HijriDateConverter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Arabic
{
    use HandlesNumerals;
    use HandlesPunctuation;
    use HandlesSpaces;

    public function removeHarakat(string $text): string
    {
        return $this->removeDiacritics(
            $text,
            (bool) ArabicableConfig::get('arabicable.processing.keep_shadda_when_stripping_diacritics', false),
        );
    }

    public function removeDiacritics(string $text, bool $keepShadda = false): string
    {
        $pattern = $keepShadda
            ? '/[\x{0610}-\x{061A}\x{064B}-\x{0650}\x{0652}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\x{08D4}-\x{08FF}\x{0640}]/u'
            : '/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\x{08D4}-\x{08FF}\x{0640}]/u';

        return preg_replace($pattern, '', $text) ?? $text;
    }

    public function normalizeHuroof(string $text): string
    {
        $huroof = ['أ', 'ى', 'إ', 'ٕ ', 'ﭐ', 'ﭑ'];
        $text = strtr($text, array_fill_keys($huroof, 'ا'));

        $huroof = ['ﭒ', 'ﭓ', 'ﭔ', 'ٕﭕ', 'ﭖ'];
        $text = strtr($text, array_fill_keys($huroof, 'ب'));

        $huroof = [
            'ئ' => 'ي',
            'ؤ' => 'و',
            'آ' => 'ا',
        ];

        return strtr($text, $huroof);
    }

    public function normalizeNumeralsForSearch(string $text): string
    {
        $mode = (string) ArabicableConfig::get('arabicable.numerals.search_mode', 'arabic');

        return match ($mode) {
            'both' => $this->deduplicateArabicAndIndianNumeralSequences(
                $this->convertNumeralsToArabicAndIndianSequences($text),
            ),
            'indian' => str_replace(
                ArabicSpecialCharacters::IndianNumerals->get(),
                ArabicSpecialCharacters::ArabicNumerals->get(),
                $text,
            ),
            default => str_replace(
                ArabicSpecialCharacters::ArabicNumerals->get(),
                ArabicSpecialCharacters::IndianNumerals->get(),
                $text,
            ),
        };
    }

    public function convertNumeralsToArabic(string $text): string
    {
        /** @var array<string, string> $map */
        $map = array_combine(
            ArabicSpecialCharacters::ArabicNumerals->get(),
            ArabicSpecialCharacters::IndianNumerals->get(),
        );

        return strtr($text, $map);
    }

    public function toTightPunctuationStyle(string $text): string
    {
        $this->validateForTextSpacing($text);

        $text = $this->normalizeSpaces($text);
        $text = $this->convertPunctuationMarksToArabic($text);
        $text = $this->removeSpacesAroundPunctuationMarks($text);
        $text = $this->addSpacesAfterPunctuationMarks($text);
        $text = $this->removeSpacesWithinEnclosingMarks($text);

        return trim($this->refineSpacesBetweenPunctuationMarks($text));
    }

    public function toLoosePunctuationStyle(string $text): string
    {
        $text = $this->toTightPunctuationStyle($text);
        $text = $this->addSpacesBeforePunctuationMarks($text);
        $text = $this->addSpacesAfterPunctuationMarks($text);

        return trim($this->refineSpacesBetweenPunctuationMarks($text));
    }

    public function addHarakat(string $text): string
    {
        return ArabicFilter::withHarakat($text);
    }

    /**
     * @return array<string, int>
     */
    public function identifySpecialCharacters(string $text, bool $includeKnownPunctuation = true): array
    {
        $knownPunctuation = array_merge(
            ArabicSpecialCharacters::PunctuationMarks->get(),
            ArabicSpecialCharacters::ForeignPunctuationMarks->get(),
            ArabicSpecialCharacters::ArabicPunctuationMarks->get(),
            ArabicSpecialCharacters::EnclosingMarks->get(),
            ArabicSpecialCharacters::EnclosingStarterMarks->get(),
            ArabicSpecialCharacters::EnclosingEnderMarks->get(),
            ArabicSpecialCharacters::ArabicEnclosingMarks->get(),
            ArabicSpecialCharacters::ArabicEnclosingStarterMarks->get(),
            ArabicSpecialCharacters::ArabicEnclosingEnderMarks->get(),
        );

        $characters = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $counts = [];

        foreach ($characters as $character) {
            if (trim($character) === '') {
                continue;
            }

            if (preg_match('/^[\p{Arabic}\p{N}]$/u', $character) === 1) {
                continue;
            }

            if (! $includeKnownPunctuation && in_array($character, $knownPunctuation, true)) {
                continue;
            }

            $counts[$character] = ($counts[$character] ?? 0) + 1;
        }

        return $counts;
    }

    public function stripWeirdCharacters(
        string $text,
        bool $keepHarakat = false,
        bool $keepPunctuation = true,
    ): string {
        if (! $keepHarakat) {
            $text = $this->removeDiacritics($text);
        }

        $allowedPunctuation = '';

        if ($keepPunctuation) {
            $allowedPunctuation = implode('', array_merge(
                ArabicSpecialCharacters::PunctuationMarks->get(),
                ArabicSpecialCharacters::ForeignPunctuationMarks->get(),
                ArabicSpecialCharacters::ArabicPunctuationMarks->get(),
                ArabicSpecialCharacters::EnclosingMarks->get(),
                ArabicSpecialCharacters::EnclosingStarterMarks->get(),
                ArabicSpecialCharacters::EnclosingEnderMarks->get(),
                ArabicSpecialCharacters::ArabicEnclosingMarks->get(),
                ArabicSpecialCharacters::ArabicEnclosingStarterMarks->get(),
                ArabicSpecialCharacters::ArabicEnclosingEnderMarks->get(),
            ));
        }

        $pattern = '/[^\\p{Arabic}\\p{N}\\s'.preg_quote($allowedPunctuation, '/').']+/u';
        $text = preg_replace($pattern, ' ', $text) ?? $text;

        return $this->normalizeSpaces($text);
    }

    /**
     * @return array{year: int, month: int, day: int}
     */
    public function gregorianToHijri(int $year, int $month, int $day): array
    {
        /** @var HijriDateConverter $converter */
        $converter = app(HijriDateConverter::class);

        return $converter->gregorianToHijri($year, $month, $day);
    }

    /**
     * @return array{year: int, month: int, day: int}
     */
    public function hijriToGregorian(int $year, int $month, int $day): array
    {
        /** @var HijriDateConverter $converter */
        $converter = app(HijriDateConverter::class);

        return $converter->hijriToGregorian($year, $month, $day);
    }

    /**
     * @return array<int, string>
     */
    public function tokenize(string $text): array
    {
        $text = ArabicFilter::forSearch($text);

        /** @var CamelTools $camel */
        $camel = app(CamelTools::class);

        $tokens = $camel->simpleWordTokenize($text, true);

        return array_values(array_filter($tokens, static function (string $part): bool {
            $trimmed = trim($part);

            if ($trimmed === '') {
                return false;
            }

            return preg_match('/^[\p{P}\p{S}]+$/u', $trimmed) !== 1;
        }));
    }

    public function stemWord(string $word): string
    {
        /** @var ArabicStemmer $stemmer */
        $stemmer = app(ArabicStemmer::class);

        return $stemmer->stem($word);
    }

    /**
     * @param  array<int, string>  $words
     * @return array<int, string>
     */
    public function stemWords(array $words): array
    {
        /** @var ArabicStemmer $stemmer */
        $stemmer = app(ArabicStemmer::class);

        return $stemmer->stemMany($words);
    }

    /**
     * @param  array<int, string>  $words
     * @return array<int, string>
     */
    public function removeStopWords(array $words): array
    {
        /** @var ArabicStopWords $stopWords */
        $stopWords = app(ArabicStopWords::class);

        return $stopWords->removeFrom($words);
    }

    /**
     * @param  string|array<int, string>  $words
     * @return string|array<int, string>
     */
    public function removeConnectors(string|array $words, bool $asString = false): string|array
    {
        $tokens = is_string($words)
            ? $this->tokenize($words)
            : array_values(array_filter($words, static fn (string $word): bool => trim($word) !== ''));

        $filtered = $this->removeStopWords($tokens);

        if ($asString) {
            return implode(' ', $filtered);
        }

        return $filtered;
    }

    /**
     * @return array{tokens: array<int, string>, stems: array<int, string>, keywords: array<int, string>}
     */
    public function extractKeywords(
        string $text,
        bool $stripCommons = true,
        bool $stripConnectors = true,
    ): array {
        $workingText = $text;

        if ($stripCommons) {
            $workingText = (string) $this->removeCommons($workingText, asString: true);
        }

        $tokens = $this->tokenize($workingText);

        if ($stripConnectors) {
            $tokens = $this->removeStopWords($tokens);
        }

        $stems = $this->stemWords($tokens);
        $keywords = $stems;

        return [
            'tokens' => $tokens,
            'stems' => $stems,
            'keywords' => array_values(array_unique($keywords)),
        ];
    }

    /**
     * @param  string|array<int, string>  $words
     * @return array<int, string>
     */
    public function expandWordVariants(
        string|array $words,
        ?int $maxVariantsPerToken = null,
        ?int $maxTerms = null,
        ?string $mode = null,
        ?bool $stripStopWords = null,
    ): array {
        $tokens = is_string($words)
            ? $this->tokenize($words)
            : array_values(array_filter(
                array_map(
                    static fn (string $word): string => ArabicFilter::forSearch($word),
                    $words,
                ),
                static fn (string $word): bool => $word !== '',
            ));

        if ($tokens === []) {
            return [];
        }

        /** @var ArabicWordVariants $variants */
        $variants = app(ArabicWordVariants::class);

        return $variants->expandTokens(
            $tokens,
            $maxVariantsPerToken,
            $maxTerms,
            $mode,
            $stripStopWords,
        );
    }

    /**
     * @return array{
     *     normalized: string,
     *     tokens: array<int, string>,
     *     stripped: array<int, string>,
     *     stems: array<int, string>,
     *     variants: array<int, string>,
     *     terms: array<int, string>
     * }
     */
    public function buildComprehensiveSearchPlan(string $query, int $maxTerms = 80): array
    {
        $normalized = ArabicFilter::forSearch($query);

        if ($normalized === '') {
            return [
                'normalized' => '',
                'tokens' => [],
                'stripped' => [],
                'stems' => [],
                'variants' => [],
                'terms' => [],
            ];
        }

        $tokens = $this->tokenize($query);
        $stripped = $this->removeStopWords($tokens);
        $tokenBasis = $stripped;
        $stems = $this->stemWords($tokenBasis);
        $variants = [];

        $terms = array_values(array_unique(array_filter(array_merge(
            [$normalized],
            $tokenBasis,
            $stems,
        ), static fn (string $term): bool => trim($term) !== '')));

        if ((bool) ArabicableConfig::get('arabicable.search.comprehensive.expand_with_word_variants', true)) {
            $variantTerms = $this->expandWordVariants(
                words: $tokenBasis,
                maxVariantsPerToken: (int) ArabicableConfig::get(
                    'arabicable.search.comprehensive.max_word_variants_per_token',
                    24,
                ),
                maxTerms: (int) ArabicableConfig::get(
                    'arabicable.search.comprehensive.max_variant_terms',
                    120,
                ),
                mode: (string) ArabicableConfig::get(
                    'arabicable.search.comprehensive.variant_mode',
                    'all',
                ),
                stripStopWords: (bool) ArabicableConfig::get(
                    'arabicable.search.comprehensive.strip_stop_words_from_variants',
                    true,
                ),
            );
            $variants = $variantTerms;

            $terms = array_values(array_unique(array_filter(array_merge(
                $terms,
                $variantTerms,
            ), static fn (string $term): bool => trim($term) !== '')));
        }

        return [
            'normalized' => $normalized,
            'tokens' => $tokens,
            'stripped' => $stripped,
            'stems' => $stems,
            'variants' => $variants,
            'terms' => array_slice($terms, 0, max(1, $maxTerms)),
        ];
    }

    /**
     * @param  string|array<int, string>  $words
     * @param  array<int, CommonArabicTextType>  $excludedTypes
     * @return array<int, string>|string
     */
    public function removeCommons(string|array $words, array $excludedTypes = [], bool $asString = false): string|array
    {
        $baseCacheKey = (string) ArabicableConfig::get('arabicable.common_arabic_text.cache_key', 'common_arabic_texts');
        $types = CommonArabicTextType::cases();

        /** @var class-string<CommonArabicText> $modelClass */
        $modelClass = ArabicableConfig::get('arabicable.common_arabic_text.model', CommonArabicText::class);

        $tableName = (new $modelClass)->getTable();

        if (! Schema::hasTable($tableName)) {
            return $asString
                ? (is_string($words) ? ArabicFilter::forSearch($words) : implode(' ', $words))
                : Arr::wrap($words);
        }

        /** @var array<string, Collection<int, CommonArabicText>> $cachedCommonTexts */
        $cachedCommonTexts = [];

        foreach ($types as $type) {
            $typeKey = $baseCacheKey.'.'.$type->value;

            $cachedCommonTexts[$type->value] = Cache::rememberForever(
                $typeKey,
                static fn () => $modelClass::query()
                    ->where('type', $type->value)
                    ->get()
                    ->sortByDesc(static fn (CommonArabicText $model): int => mb_strlen((string) $model->{ar_searchable('content')})),
            );
        }

        $wordsSentence = is_string($words) ? $words : implode(' ', $words);

        $originalWordsArray = explode(' ', $wordsSentence);
        $filteredWordsArray = array_map(static fn (string $word): string => ArabicFilter::forSearch($word), $originalWordsArray);

        foreach ($cachedCommonTexts as $type => $texts) {
            if (in_array(CommonArabicTextType::from($type), $excludedTypes, true)) {
                continue;
            }

            foreach ($texts as $model) {
                $pattern = '/\b'.preg_quote((string) $model->{ar_searchable('content')}, '/').'\b/u';

                foreach ($filteredWordsArray as $index => $word) {
                    if (preg_match($pattern, $word) === 1) {
                        $originalWordsArray[$index] = '|';
                    }
                }
            }
        }

        $wordsSentence = $this->normalizeSpaces(implode(' ', $originalWordsArray));

        $sentences = array_values(array_filter(
            preg_split('/\s*\|\s*/', $wordsSentence) ?: [],
            static fn (string $sentence): bool => mb_strlen(trim($sentence)) > 1,
        ));

        if ($asString) {
            return implode(' ', $sentences);
        }

        return $sentences;
    }

    public function clearConceptCache(ArabicLinguisticConcept $concept): void
    {
        if ($concept === ArabicLinguisticConcept::Vocalizations) {
            /** @var ArabicVocalizations $vocalizations */
            $vocalizations = app(ArabicVocalizations::class);
            $vocalizations->clearCache();

            return;
        }

        if ($concept === ArabicLinguisticConcept::StopWords) {
            /** @var ArabicStopWords $stopWords */
            $stopWords = app(ArabicStopWords::class);
            $stopWords->clearCache();

            return;
        }

        if ($concept === ArabicLinguisticConcept::CommonTexts || $concept === ArabicLinguisticConcept::All) {
            $baseCacheKey = (string) ArabicableConfig::get('arabicable.common_arabic_text.cache_key', 'common_arabic_texts');

            foreach (CommonArabicTextType::cases() as $type) {
                Cache::forget($baseCacheKey.'.'.$type->value);
            }
        }

        if ($concept === ArabicLinguisticConcept::All) {
            /** @var ArabicStopWords $stopWords */
            $stopWords = app(ArabicStopWords::class);
            $stopWords->clearCache();

            /** @var ArabicVocalizations $vocalizations */
            $vocalizations = app(ArabicVocalizations::class);
            $vocalizations->clearCache();
        }

        if ($concept === ArabicLinguisticConcept::MorphVariants || $concept === ArabicLinguisticConcept::All) {
            /** @var ArabicWordVariants $variants */
            $variants = app(ArabicWordVariants::class);
            $variants->clearCache();
        }
    }
}
