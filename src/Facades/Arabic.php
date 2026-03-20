<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string removeHarakat(string $text)
 * @method static string removeDiacritics(string $text, bool $keepShadda = false)
 * @method static string addHarakat(string $text)
 * @method static string normalizeHuroof(string $text)
 * @method static string normalizeNumeralsForSearch(string $text)
 * @method static string convertNumeralsToArabic(string $text)
 * @method static array<string, int> identifySpecialCharacters(string $text, bool $includeKnownPunctuation = true)
 * @method static string stripWeirdCharacters(string $text, bool $keepHarakat = false, bool $keepPunctuation = true)
 * @method static string toTightPunctuationStyle(string $text)
 * @method static string toLoosePunctuationStyle(string $text)
 * @method static array{year: int, month: int, day: int} gregorianToHijri(int $year, int $month, int $day)
 * @method static array{year: int, month: int, day: int} hijriToGregorian(int $year, int $month, int $day)
 * @method static array<int, string> tokenize(string $text)
 * @method static string stemWord(string $word)
 * @method static array<int, string> stemWords(array<int, string> $words)
 * @method static array<int, string> removeStopWords(array<int, string> $words)
 * @method static string|array<int, string> removeConnectors(string|array<int, string> $words, bool $asString = false)
 * @method static array{tokens: array<int, string>, stems: array<int, string>, keywords: array<int, string>} extractKeywords(string $text, bool $stripCommons = true, bool $stripConnectors = true)
 * @method static array<int, string> expandWordVariants(string|array<int, string> $words, ?int $maxVariantsPerToken = null, ?int $maxTerms = null, ?string $mode = null, ?bool $stripStopWords = null)
 * @method static array{normalized: string, tokens: array<int, string>, stripped: array<int, string>, stems: array<int, string>, variants: array<int, string>, terms: array<int, string>} buildComprehensiveSearchPlan(string $query, int $maxTerms = 80)
 * @method static array<int, string>|string removeCommons(string|array<int, string> $words, array<int, \GoodMaven\Arabicable\Enums\CommonArabicTextType> $excludedTypes = [], bool $asString = false)
 * @method static void clearConceptCache(\GoodMaven\Arabicable\Enums\ArabicLinguisticConcept $concept)
 * @method static string convertNumeralsToIndian(string $text)
 * @method static string convertNumeralsToArabicAndIndianSequences(string $text)
 * @method static string deduplicateArabicAndIndianNumeralSequences(string $text)
 * @method static string convertPunctuationMarksToArabic(string $text)
 * @method static string removeAllPunctuationMarks(string $text)
 * @method static void validateForTextSpacing(string $text)
 * @method static string normalizeSpaces(string $text)
 * @method static string addSpacesBeforePunctuationMarks(string $text, array<int, string> $inclusions = [], array<int, string> $exclusions = [])
 * @method static string addSpacesAfterPunctuationMarks(string $text, array<int, string> $inclusions = [], array<int, string> $exclusions = [])
 * @method static string removeSpacesAroundPunctuationMarks(string $text, array<int, string> $inclusions = [], array<int, string> $exclusions = [])
 * @method static string removeSpacesWithinEnclosingMarks(string $text, array<int, string> $exclusions = [])
 * @method static string refineSpacesBetweenPunctuationMarks(string $text)
 *
 * @see \GoodMaven\Arabicable\Arabic
 */
final class Arabic extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \GoodMaven\Arabicable\Arabic::class;
    }
}
