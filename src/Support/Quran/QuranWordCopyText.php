<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Support\Quran;

final class QuranWordCopyText
{
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
        $copyText = trim((string) ($tokenUthmani ?? ''));

        if ($copyText === '') {
            $copyText = trim((string) ($tokenSearchableTyped ?? ''));
        }

        return $copyText !== '' ? $copyText : null;
    }
}
