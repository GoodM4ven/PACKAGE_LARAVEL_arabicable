<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Support\Concerns;

use GoodMaven\Arabicable\Enums\ArabicSpecialCharacters;

trait HandlesNumerals
{
    public function convertNumeralsToIndian(string $text): string
    {
        /** @var array<string, string> $map */
        $map = array_combine(
            ArabicSpecialCharacters::IndianNumerals->get(),
            ArabicSpecialCharacters::ArabicNumerals->get(),
        );

        return strtr($text, $map);
    }

    public function convertNumeralsToArabicAndIndianSequences(string $text): string
    {
        $arabicNumerals = implode('', ArabicSpecialCharacters::ArabicNumerals->get());
        $indianNumerals = implode('', ArabicSpecialCharacters::IndianNumerals->get());

        if (preg_match('/['.$arabicNumerals.']/u', $text) === 1) {
            $text = preg_replace_callback(
                '/['.$arabicNumerals.']+/u',
                static function (array $matches): string {
                    $arabicNumber = (string) ($matches[0] ?? '');
                    $indianNumber = str_replace(
                        ArabicSpecialCharacters::ArabicNumerals->get(),
                        ArabicSpecialCharacters::IndianNumerals->get(),
                        $arabicNumber,
                    );

                    return $arabicNumber.' '.$indianNumber;
                },
                $text,
            ) ?? $text;
        }

        if (preg_match('/['.$indianNumerals.']+/', $text) === 1) {
            $text = preg_replace_callback(
                '/['.$indianNumerals.']+/',
                static function (array $matches): string {
                    $indianNumber = (string) ($matches[0] ?? '');
                    $arabicNumber = str_replace(
                        ArabicSpecialCharacters::IndianNumerals->get(),
                        ArabicSpecialCharacters::ArabicNumerals->get(),
                        $indianNumber,
                    );

                    return $indianNumber.' '.$arabicNumber;
                },
                $text,
            ) ?? $text;
        }

        return $text;
    }

    public function deduplicateArabicAndIndianNumeralSequences(string $text): string
    {
        $pattern = '/\d+|[\x{0660}-\x{0669}]+/u';
        preg_match_all($pattern, $text, $matches);
        $matchedNumbers = $matches[0];

        if ($matchedNumbers === []) {
            return $text;
        }

        $uniqueNumbers = implode(' ', array_unique($matchedNumbers));
        $cleanedText = preg_replace($pattern, '', $text) ?? $text;

        return trim($cleanedText).' '.$uniqueNumbers;
    }
}
