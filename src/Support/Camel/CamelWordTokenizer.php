<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Support\Camel;

final class CamelWordTokenizer
{
    /**
     * @return array<int, string>
     */
    public function simpleWordTokenize(string $text, bool $splitDigits = false): array
    {
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        if ($words === false) {
            return [];
        }

        $tokens = [];

        foreach ($words as $word) {
            $this->tokenizeWord($word, $tokens, $splitDigits);
        }

        return $tokens;
    }

    /**
     * @param  array<int, string>  $tokens
     */
    private function tokenizeWord(string $word, array &$tokens, bool $splitDigits): void
    {
        $units = $this->splitGraphemes($word);
        $unitCount = count($units);

        if ($unitCount === 0) {
            return;
        }

        $buffer = '';
        $index = 0;

        while ($index < $unitCount) {
            $unit = $units[$index];

            if ($this->isSymbolOrPunctuation($unit)) {
                if ($buffer !== '') {
                    $tokens[] = $buffer;
                    $buffer = '';
                }

                $tokens[] = $unit;
                $index++;

                continue;
            }

            if ($splitDigits && $this->isDigit($unit)) {
                if ($buffer !== '') {
                    $tokens[] = $buffer;
                    $buffer = '';
                }

                $digits = $unit;
                $index++;

                while ($index < $unitCount && $this->isDigit($units[$index])) {
                    $digits .= $units[$index];
                    $index++;
                }

                $tokens[] = $digits;

                continue;
            }

            $buffer .= $unit;
            $index++;
        }

        if ($buffer !== '') {
            $tokens[] = $buffer;
        }
    }

    /**
     * @return array<int, string>
     */
    private function splitGraphemes(string $word): array
    {
        $matched = preg_match_all('/\X/u', $word, $parts);

        if ($matched === false || $matched === 0) {
            return [];
        }

        /** @var array<int, string> $units */
        $units = $parts[0];

        return $units;
    }

    private function isSymbolOrPunctuation(string $unit): bool
    {
        if (preg_match('/^[\p{P}\p{S}]$/u', $unit) === 1) {
            return true;
        }

        return preg_match('/^\p{Extended_Pictographic}/u', $unit) === 1;
    }

    private function isDigit(string $unit): bool
    {
        return preg_match('/^\p{Nd}$/u', $unit) === 1;
    }
}
