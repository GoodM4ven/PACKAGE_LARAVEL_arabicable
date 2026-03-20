<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Support\Concerns;

use GoodMaven\Arabicable\Enums\ArabicSpecialCharacters;
use GoodMaven\Arabicable\Support\Config\ArabicableConfig;

trait HandlesPunctuation
{
    /**
     * @return array<int, string>
     */
    protected function getAllPunctuationMarks(): array
    {
        return arabicable_special_characters(only: [
            ArabicSpecialCharacters::PunctuationMarks,
            ArabicSpecialCharacters::ForeignPunctuationMarks,
            ArabicSpecialCharacters::ArabicPunctuationMarks,
            ArabicSpecialCharacters::EnclosingMarks,
            ArabicSpecialCharacters::EnclosingStarterMarks,
            ArabicSpecialCharacters::EnclosingEnderMarks,
            ArabicSpecialCharacters::ArabicEnclosingMarks,
            ArabicSpecialCharacters::ArabicEnclosingStarterMarks,
            ArabicSpecialCharacters::ArabicEnclosingEnderMarks,
        ]);
    }

    /**
     * @return array<int, string>
     */
    protected function getAllEnclosingMarks(): array
    {
        return arabicable_special_characters(only: [
            ArabicSpecialCharacters::EnclosingMarks,
            ArabicSpecialCharacters::EnclosingStarterMarks,
            ArabicSpecialCharacters::EnclosingEnderMarks,
            ArabicSpecialCharacters::ArabicEnclosingMarks,
            ArabicSpecialCharacters::ArabicEnclosingStarterMarks,
            ArabicSpecialCharacters::ArabicEnclosingEnderMarks,
        ]);
    }

    /**
     * @param  array<int, string>  $inclusions
     * @param  array<int, string>  $exclusions
     * @return array<int, string>
     */
    protected function getFilteredPunctuationMarks(array $inclusions = [], array $exclusions = []): array
    {
        return array_values(array_diff(
            array_merge($this->getAllPunctuationMarks(), $inclusions),
            $exclusions,
        ));
    }

    public function convertPunctuationMarksToArabic(string $text): string
    {
        $text = str_replace(
            ArabicSpecialCharacters::ForeignPunctuationMarks->get(),
            ArabicSpecialCharacters::ArabicPunctuationMarks->get(),
            $text,
        );

        $normalizedMarks = ArabicableConfig::get('arabicable.normalized_punctuation_marks');

        if (is_array($normalizedMarks)) {
            foreach ($normalizedMarks as $mark => $fromOthers) {
                if (! is_string($mark) || ! is_array($fromOthers)) {
                    continue;
                }

                $text = str_replace($fromOthers, $mark, $text);
            }
        }

        return $text;
    }

    public function removeAllPunctuationMarks(string $text): string
    {
        return strtr($text, array_fill_keys($this->getAllPunctuationMarks(), ''));
    }

    public function replaceAllPunctuationMarksWithSpaces(string $text): string
    {
        return strtr($text, array_fill_keys($this->getAllPunctuationMarks(), ' '));
    }
}
