<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable;

use GoodMaven\Arabicable\Facades\Arabic;
use GoodMaven\Arabicable\Support\Config\ArabicableConfig;

class ArabicFilter
{
    public function withHarakat(string $text): string
    {
        Arabic::validateForTextSpacing($text);

        $text = Arabic::convertNumeralsToIndian($text);
        $text = Arabic::normalizeSpaces($text);
        $text = Arabic::convertPunctuationMarksToArabic($text);
        $text = Arabic::removeSpacesAroundPunctuationMarks($text);

        if (! (bool) ArabicableConfig::get('arabicable.spacing_after_punctuation_only', false)) {
            $text = Arabic::addSpacesBeforePunctuationMarks($text);
        }

        $text = Arabic::addSpacesAfterPunctuationMarks($text);
        $text = Arabic::removeSpacesWithinEnclosingMarks($text);

        return Arabic::refineSpacesBetweenPunctuationMarks($text);
    }

    public function withoutHarakat(string $text): string
    {
        return Arabic::removeHarakat($this->withHarakat($text));
    }

    public function withoutDiacritics(string $text, bool $keepShadda = false): string
    {
        return Arabic::removeDiacritics($this->withHarakat($text), $keepShadda);
    }

    public function forSearch(string $text): string
    {
        /** @var CamelTools $camel */
        $camel = app(CamelTools::class);

        $text = $camel->normalizeUnicode($text);
        $text = Arabic::removeHarakat($text);
        $text = Arabic::replaceAllPunctuationMarksWithSpaces($text);
        $text = Arabic::normalizeNumeralsForSearch($text);
        $text = Arabic::normalizeHuroof($text);

        return Arabic::normalizeSpaces($text);
    }

    public function forStem(string $text): string
    {
        $words = Arabic::tokenize($text);
        $words = Arabic::removeStopWords($words);
        $words = Arabic::stemWords($words);

        return Arabic::normalizeSpaces(implode(' ', $words));
    }

    public function forMemorizationComparison(
        string $text,
        bool $stripCommons = true,
        bool $stripConnectors = true,
    ): string {
        $payload = Arabic::extractKeywords(
            text: $text,
            stripCommons: $stripCommons,
            stripConnectors: $stripConnectors,
        );

        return Arabic::normalizeSpaces(implode(' ', $payload['keywords']));
    }
}
