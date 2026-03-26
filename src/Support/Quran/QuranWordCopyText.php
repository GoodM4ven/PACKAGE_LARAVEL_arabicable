<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Support\Quran;

final class QuranWordCopyText
{
    /**
     * @param  iterable<array{
     *     global_word_index?: int|string|null,
     *     token_uthmani?: string|null,
     *     token_searchable_typed?: string|null
     * }|object{
     *     global_word_index?: int|string|null,
     *     token_uthmani?: string|null,
     *     token_searchable_typed?: string|null
     * }>  $wordRows
     * @return array<int, string>
     */
    public static function buildMapByGlobalWordIndex(iterable $wordRows): array
    {
        $map = [];

        foreach ($wordRows as $wordRow) {
            $globalWordIndex = (int) data_get($wordRow, 'global_word_index', 0);

            if ($globalWordIndex < 1) {
                continue;
            }

            $copyText = self::normalizeToken(
                data_get($wordRow, 'token_uthmani'),
                data_get($wordRow, 'token_searchable_typed'),
            );

            if ($copyText === null) {
                continue;
            }

            $map[$globalWordIndex] = $copyText;
        }

        return $map;
    }

    /**
     * @param  iterable<array{
     *     surah_number?: int|string|null,
     *     ayah_number?: int|string|null,
     *     word_position?: int|string|null,
     *     token_uthmani?: string|null,
     *     token_searchable_typed?: string|null
     * }|object{
     *     surah_number?: int|string|null,
     *     ayah_number?: int|string|null,
     *     word_position?: int|string|null,
     *     token_uthmani?: string|null,
     *     token_searchable_typed?: string|null
     * }>  $wordRows
     * @return array<string, string>
     */
    public static function buildMapByAyahPosition(iterable $wordRows): array
    {
        $map = [];

        foreach ($wordRows as $wordRow) {
            $surahNumber = (int) data_get($wordRow, 'surah_number', 0);
            $ayahNumber = (int) data_get($wordRow, 'ayah_number', 0);
            $wordPosition = (int) data_get($wordRow, 'word_position', 0);
            $key = self::ayahWordKey($surahNumber, $ayahNumber, $wordPosition);

            if ($key === null) {
                continue;
            }

            $copyText = self::normalizeToken(
                data_get($wordRow, 'token_uthmani'),
                data_get($wordRow, 'token_searchable_typed'),
            );

            if ($copyText === null) {
                continue;
            }

            $map[$key] = $copyText;
        }

        return $map;
    }

    public static function ayahWordKey(int $surahNumber, int $ayahNumber, int $wordPosition): ?string
    {
        if ($surahNumber < 1 || $ayahNumber < 1 || $wordPosition < 1) {
            return null;
        }

        return $surahNumber.':'.$ayahNumber.':'.$wordPosition;
    }

    public static function normalizeToken(?string $tokenUthmani, ?string $tokenSearchableTyped): ?string
    {
        $uthmaniText = trim((string) ($tokenUthmani ?? ''));
        $typedText = trim((string) ($tokenSearchableTyped ?? ''));
        $copyText = $uthmaniText;

        if ($copyText !== '') {
            $copyText = self::normalizeUthmaniCopyText($copyText);
        }

        if ($copyText !== '' && ! self::containsPresentationForms($copyText)) {
            return $copyText;
        }

        if ($typedText !== '') {
            return self::normalizeTypedCopyText($typedText);
        }

        return $copyText !== '' ? $copyText : null;
    }

    private static function normalizeUthmaniCopyText(string $text): string
    {
        $normalized = preg_replace(
            '/[\x{200B}-\x{200F}\x{061C}\x{2066}-\x{2069}\x{FEFF}]/u',
            '',
            $text,
        ) ?? $text;
        $normalized = preg_replace('/\x{06E1}/u', "\u{0652}", $normalized) ?? $normalized;
        $normalized = preg_replace('/[\x{06D6}-\x{06ED}]/u', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/\x{0640}/u', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }

    private static function normalizeTypedCopyText(string $text): string
    {
        $normalized = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($normalized);
    }

    private static function containsPresentationForms(string $text): bool
    {
        return (bool) preg_match('/[\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $text);
    }
}
