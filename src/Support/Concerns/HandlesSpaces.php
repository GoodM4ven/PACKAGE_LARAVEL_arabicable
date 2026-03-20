<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Support\Concerns;

use GoodMaven\Arabicable\Enums\ArabicSpecialCharacters;
use GoodMaven\Arabicable\Support\Config\ArabicableConfig;
use GoodMaven\Arabicable\Support\Exceptions\ArabicableValidationException;

trait HandlesSpaces
{
    public function validateForTextSpacing(string $text): void
    {
        $pairMap = array_combine(
            ArabicSpecialCharacters::EnclosingStarterMarks->get(),
            ArabicSpecialCharacters::EnclosingEnderMarks->get(),
        ) ?: [];

        $pairMap += array_combine(
            ArabicSpecialCharacters::ArabicEnclosingStarterMarks->get(),
            ArabicSpecialCharacters::ArabicEnclosingEnderMarks->get(),
        ) ?: [];

        foreach ($pairMap as $starter => $ender) {
            $starterCount = substr_count($text, $starter);
            $enderCount = substr_count($text, $ender);

            if ($starterCount !== $enderCount) {
                throw new ArabicableValidationException(
                    "Found the number of starter '{$starter}' not matching the number of ender '{$ender}' enclosing marks.",
                );
            }
        }

        $sameTypeMarks = array_merge(
            ArabicSpecialCharacters::EnclosingMarks->get(),
            ArabicSpecialCharacters::ArabicEnclosingMarks->get(),
        );

        foreach ($sameTypeMarks as $mark) {
            if (substr_count($text, $mark) % 2 !== 0) {
                throw new ArabicableValidationException(
                    "Detected an uneven number of the enclosing mark '{$mark}'.",
                );
            }
        }
    }

    public function normalizeSpaces(string $text): string
    {
        $trimmed = mb_ereg_replace('^\s*|\s*$', '', $text);
        $normalized = preg_replace('/\s+/u', ' ', $trimmed ?? $text) ?? $text;

        return $normalized;
    }

    /**
     * @param  array<int, string>  $inclusions
     * @param  array<int, string>  $exclusions
     */
    public function addSpacesBeforePunctuationMarks(string $text, array $inclusions = [], array $exclusions = []): string
    {
        $filteredMarks = $this->getFilteredPunctuationMarks($inclusions, $exclusions);
        $escapedMarks = array_map(static fn (string $mark): string => preg_quote($mark, '/'), $filteredMarks);
        $pattern = '/(?<![\s'.implode('', $escapedMarks).'])(['.implode('', $escapedMarks).'])/u';

        return preg_replace($pattern, ' $1', $text) ?? $text;
    }

    /**
     * @param  array<int, string>  $inclusions
     * @param  array<int, string>  $exclusions
     */
    public function addSpacesAfterPunctuationMarks(string $text, array $inclusions = [], array $exclusions = []): string
    {
        $filteredMarks = $this->getFilteredPunctuationMarks($inclusions, $exclusions);
        $escapedMarks = array_map(static fn (string $mark): string => preg_quote($mark, '/'), $filteredMarks);

        $specialMarks = arabicable_special_characters(only: [
            ArabicSpecialCharacters::EnclosingMarks,
            ArabicSpecialCharacters::EnclosingStarterMarks,
            ArabicSpecialCharacters::ArabicEnclosingMarks,
            ArabicSpecialCharacters::ArabicEnclosingStarterMarks,
        ]);

        $escapedSpecialMarks = array_map(static fn (string $mark): string => preg_quote($mark, '/'), $specialMarks);

        $allMarksPattern = implode('', $escapedMarks);
        $specialMarksPattern = implode('', $escapedSpecialMarks);

        $pattern = '/(['.$allMarksPattern.'])(?![\s'.$allMarksPattern.'])'
            .'|(['.$allMarksPattern.'])(?=['.$specialMarksPattern.'])/u';

        return preg_replace_callback(
            $pattern,
            static fn (array $matches): string => isset($matches[2]) ? $matches[2].' ' : $matches[1].' ',
            $text,
        ) ?? $text;
    }

    /**
     * @param  array<int, string>  $inclusions
     * @param  array<int, string>  $exclusions
     */
    public function removeSpacesAroundPunctuationMarks(string $text, array $inclusions = [], array $exclusions = []): string
    {
        $filteredMarks = $this->getFilteredPunctuationMarks($inclusions, $exclusions);

        foreach ($filteredMarks as $mark) {
            $escapedMark = preg_quote($mark, '/');
            $text = preg_replace('/\s*'.$escapedMark.'\s*/u', $mark, $text) ?? $text;
        }

        return $text;
    }

    /**
     * @param  array<int, string>  $exclusions
     */
    public function removeSpacesWithinEnclosingMarks(string $text, array $exclusions = []): string
    {
        $marks = array_merge(
            ArabicSpecialCharacters::EnclosingMarks->get(),
            ArabicSpecialCharacters::ArabicEnclosingMarks->get(),
        );

        $configuredExclusions = ArabicableConfig::get('arabicable.space_preserved_enclosings') ?? [];
        if (! is_array($configuredExclusions)) {
            $configuredExclusions = [];
        }

        $exclusions = array_merge($exclusions, $configuredExclusions);

        $starterMarks = array_diff(
            array_merge(
                $marks,
                ArabicSpecialCharacters::EnclosingStarterMarks->get(),
                ArabicSpecialCharacters::ArabicEnclosingStarterMarks->get(),
            ),
            $exclusions,
        );

        $enderMarks = array_diff(
            array_merge(
                $marks,
                ArabicSpecialCharacters::EnclosingEnderMarks->get(),
                ArabicSpecialCharacters::ArabicEnclosingEnderMarks->get(),
            ),
            $exclusions,
        );

        $escapedStarterMarks = array_map(static fn (string $mark): string => preg_quote($mark, '/'), $starterMarks);
        $escapedEnderMarks = array_map(static fn (string $mark): string => preg_quote($mark, '/'), $enderMarks);

        $text = preg_replace('/('.implode('|', $escapedStarterMarks).')\s+/u', '$1', $text) ?? $text;

        return preg_replace('/\s+('.implode('|', $escapedEnderMarks).')/u', '$1', $text) ?? $text;
    }

    public function refineSpacesBetweenPunctuationMarks(string $text): string
    {
        if ((bool) ArabicableConfig::get('arabicable.spacing_after_punctuation_only', false)) {
            $enclosings = array_diff($this->getAllEnclosingMarks(), ["'", '"', '/']);
            $escapedEnclosings = array_map(static fn (string $mark): string => preg_quote($mark, '/'), $enclosings);
            $marksPattern = implode('|', $escapedEnclosings);

            $text = preg_replace('/(?<!\s)(['.$marksPattern.'])/u', ' $1', $text) ?? $text;

            $configuredPreserved = ArabicableConfig::get('arabicable.space_preserved_enclosings') ?? [];
            if (! is_array($configuredPreserved)) {
                $configuredPreserved = [];
            }

            $enders = array_diff(
                array_merge(
                    ArabicSpecialCharacters::EnclosingEnderMarks->get(),
                    ArabicSpecialCharacters::ArabicEnclosingEnderMarks->get(),
                ),
                $configuredPreserved,
            );

            $enderPattern = implode('', $enders);
            $text = preg_replace('/\s+(?=['.preg_quote($enderPattern, '/').'])/u', '', $text) ?? $text;
            $text = preg_replace('/(?<!\s)-/u', ' -', $text) ?? $text;
        }

        $text = preg_replace('/\s*"\s*([^"]*?)\s*"\s*/u', ' "$1" ', $text) ?? $text;
        $text = preg_replace('/\s*\'\s*([^\']*?)\s*\'\s*/u', " '$1' ", $text) ?? $text;

        $text = preg_replace('/^\s*"\s*/u', '"', $text) ?? $text;
        $text = preg_replace('/\s*"\s*$/u', '"', $text) ?? $text;
        $text = preg_replace('/^\s*\'\s*/u', "'", $text) ?? $text;
        $text = preg_replace('/\s*\'\s*$/u', "'", $text) ?? $text;

        $marks = array_merge(
            ArabicSpecialCharacters::PunctuationMarks->get(),
            ArabicSpecialCharacters::ArabicPunctuationMarks->get(),
        );

        foreach ($marks as $mark) {
            $quotedMark = preg_quote($mark, '/');
            $text = preg_replace('/"\s*'.$quotedMark.'/u', '"'.$mark, $text) ?? $text;
            $text = preg_replace('/\'\s*'.$quotedMark.'/u', "'".$mark, $text) ?? $text;
        }

        $compiledMarks = preg_quote(implode('', $marks), '/');
        $text = preg_replace('/(['.$compiledMarks.'])\s*"\s*$/u', '$1"', $text) ?? $text;

        return preg_replace('/(['.$compiledMarks.'])\s*\'\s*$/u', '$1\'', $text) ?? $text;
    }
}
