<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Support\Quran;

use GoodMaven\Arabicable\Facades\ArabicFilter;

final class QuranSearchText
{
    public static function normalizeQuery(string $text): string
    {
        $prepared = strtr($text, [
            'أ' => 'ا',
            'إ' => 'ا',
            'آ' => 'ا',
            'ٱ' => 'ا',
            'ٲ' => 'ا',
            'ٳ' => 'ا',
            'ٵ' => 'ا',
            'ؤ' => 'و',
            'ئ' => 'ي',
            'ی' => 'ي',
            'ى' => 'ي',
            'ے' => 'ي',
            'ۍ' => 'ي',
            'ې' => 'ي',
            'ۑ' => 'ي',
            'ک' => 'ك',
        ]);

        $prepared = preg_replace('/[\x{200B}-\x{200F}\x{061C}\x{2066}-\x{2069}\x{FEFF}]/u', '', $prepared)
            ?? $prepared;
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
    public static function expandVariants(string $text): array
    {
        $trimmed = trim(self::normalizeQuery($text));

        if ($trimmed === '') {
            return [];
        }

        $withoutConjunctions = self::stripLeadingConjunctionsFromPhrase($trimmed);
        $collapsedVocative = self::collapseVocativeSpacingInPhrase($trimmed);
        $vocativeBaniShortcut = self::normalizeVocativeBaniShortcutInPhrase($trimmed);
        $withoutVocative = self::stripVocativeParticlesFromPhrase($trimmed);
        $legacyOrthography = self::normalizeLegacyOrthographyForSearch($trimmed);
        $legacyOrthographyVocativeBaniShortcut = self::normalizeLegacyOrthographyForSearch(
            $vocativeBaniShortcut,
        );
        $legacyOrthographyWithoutConjunctions = self::normalizeLegacyOrthographyForSearch(
            $withoutConjunctions,
        );
        $legacyOrthographyCollapsedVocative = self::normalizeLegacyOrthographyForSearch(
            $collapsedVocative,
        );
        $legacyOrthographyWithoutVocative = self::normalizeLegacyOrthographyForSearch(
            $withoutVocative,
        );
        $legacySpellingVariants = self::expandLegacySpellingVariantsForPhrase($trimmed);
        $legacySpellingVariantsWithoutConjunctions = self::expandLegacySpellingVariantsForPhrase(
            $withoutConjunctions,
        );
        $legacySpellingVariantsWithoutVocative = self::expandLegacySpellingVariantsForPhrase(
            $withoutVocative,
        );
        $hamzatedMaddWordVariants = self::expandHamzatedMaddWordVariantsForPhrase($trimmed);
        $hamzatedMaddWordVariantsWithoutConjunctions = self::expandHamzatedMaddWordVariantsForPhrase(
            $withoutConjunctions,
        );
        $hamzatedMaddWordVariantsWithoutVocative = self::expandHamzatedMaddWordVariantsForPhrase(
            $withoutVocative,
        );
        $variants = [
            $trimmed,
            strtr($trimmed, ['ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک']),
            strtr($trimmed, ['ی' => 'ي', 'ى' => 'ي', 'ک' => 'ك']),
            strtr($trimmed, ['الرحمن' => 'الرحمان', 'رحمن' => 'رحمان']),
            strtr($trimmed, ['الرحمان' => 'الرحمن', 'رحمان' => 'رحمن']),
            $withoutConjunctions,
            $collapsedVocative,
            $vocativeBaniShortcut,
            self::collapseVocativeSpacingInPhrase($withoutConjunctions),
            $withoutVocative,
            self::stripVocativeParticlesFromPhrase($withoutConjunctions),
            $legacyOrthography,
            $legacyOrthographyVocativeBaniShortcut,
            $legacyOrthographyWithoutConjunctions,
            $legacyOrthographyCollapsedVocative,
            $legacyOrthographyWithoutVocative,
            self::normalizeQuestionVerbSpellingsInPhrase($trimmed),
            self::normalizeQuestionVerbSpellingsInPhrase($withoutConjunctions),
            ...$legacySpellingVariants,
            ...$legacySpellingVariantsWithoutConjunctions,
            ...$legacySpellingVariantsWithoutVocative,
            ...$hamzatedMaddWordVariants,
            ...$hamzatedMaddWordVariantsWithoutConjunctions,
            ...$hamzatedMaddWordVariantsWithoutVocative,
        ];

        $normalized = [];

        foreach ($variants as $variant) {
            $value = trim((string) $variant);

            if ($value === '') {
                continue;
            }

            $normalized[$value] = true;
        }

        return array_keys($normalized);
    }

    /**
     * @return array<int, string>
     */
    public static function expandStrictExactPhraseVariants(string $text): array
    {
        $trimmed = trim(self::normalizeQuery($text));

        if ($trimmed === '') {
            return [];
        }

        $collapsedVocative = self::collapseVocativeSpacingInPhrase($trimmed);
        $vocativeBaniShortcut = self::normalizeVocativeBaniShortcutInPhrase($trimmed);
        $legacyOrthography = self::normalizeLegacyOrthographyForSearch($trimmed);
        $legacyOrthographyCollapsedVocative = self::normalizeLegacyOrthographyForSearch(
            $collapsedVocative,
        );
        $legacyOrthographyVocativeBaniShortcut = self::normalizeLegacyOrthographyForSearch(
            $vocativeBaniShortcut,
        );
        $baseVariants = [
            $trimmed,
            $collapsedVocative,
            $vocativeBaniShortcut,
            $legacyOrthography,
            $legacyOrthographyCollapsedVocative,
            $legacyOrthographyVocativeBaniShortcut,
        ];
        $variants = [];

        foreach ($baseVariants as $baseVariant) {
            $variants[] = $baseVariant;
            $variants[] = strtr($baseVariant, ['ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک']);
            $variants[] = strtr($baseVariant, ['ی' => 'ي', 'ى' => 'ي', 'ک' => 'ك']);
            $variants[] = strtr($baseVariant, ['الرحمن' => 'الرحمان', 'رحمن' => 'رحمان']);
            $variants[] = strtr($baseVariant, ['الرحمان' => 'الرحمن', 'رحمان' => 'رحمن']);

            foreach (self::expandHamzatedMaddWordVariantsForPhrase($baseVariant) as $hamzatedVariant) {
                $variants[] = $hamzatedVariant;
            }
        }

        $normalized = [];

        foreach ($variants as $variant) {
            $value = trim((string) $variant);

            if ($value === '') {
                continue;
            }

            $normalized[$value] = true;
        }

        return array_keys($normalized);
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array<int, string>
     */
    public static function prepareTokens(array $tokens): array
    {
        $normalized = [];

        foreach ($tokens as $token) {
            $value = trim($token);

            if ($value === '') {
                continue;
            }

            if ($value === 'يا') {
                continue;
            }

            if (mb_strlen($value) < 2) {
                continue;
            }

            $normalized[$value] = true;
        }

        if ($normalized !== []) {
            return array_keys($normalized);
        }

        $fallback = [];

        foreach ($tokens as $token) {
            $value = trim($token);

            if ($value === '') {
                continue;
            }

            $fallback[$value] = true;
        }

        return array_keys($fallback);
    }

    private static function normalizeQuestionVerbSpellingsInPhrase(string $text): string
    {
        $tokens = preg_split('/\s+/u', trim($text)) ?: [];

        if ($tokens === []) {
            return '';
        }

        $normalized = [];

        foreach ($tokens as $token) {
            $normalized[] = self::normalizeQuestionVerbToken($token);
        }

        return trim(implode(' ', $normalized));
    }

    private static function normalizeQuestionVerbToken(string $token): string
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

    private static function stripLeadingConjunctionsFromPhrase(string $text): string
    {
        $tokens = preg_split('/\s+/u', trim($text)) ?: [];
        $normalized = [];

        foreach ($tokens as $token) {
            $normalized[] = self::stripLeadingConjunction($token);
        }

        return trim(implode(' ', $normalized));
    }

    private static function collapseVocativeSpacingInPhrase(string $text): string
    {
        return trim((string) (preg_replace('/(^|\s)يا\s+([\p{Arabic}]+)/u', '$1يا$2', trim($text)) ?? $text));
    }

    private static function normalizeVocativeBaniShortcutInPhrase(string $text): string
    {
        return trim((string) (preg_replace('/(^|\s)يبن(?=[\p{Arabic}])/u', '$1يابن', trim($text)) ?? $text));
    }

    private static function stripVocativeParticlesFromPhrase(string $text): string
    {
        return trim((string) (preg_replace('/(^|\s)يا\s+/u', '$1', trim($text)) ?? $text));
    }

    private static function stripLeadingConjunction(string $token): string
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

    private static function normalizeLegacyOrthographyForSearch(string $text): string
    {
        return strtr(trim($text), [
            'الصلاة' => 'الصلواة',
            'صلاة' => 'صلواة',
            'الزكاة' => 'الزكواة',
            'زكاة' => 'زكواة',
            'الحياة' => 'الحيوة',
            'حياة' => 'حيوة',
        ]);
    }

    /**
     * @return array<int, string>
     */
    private static function expandLegacySpellingVariantsForPhrase(string $text): array
    {
        $trimmed = trim($text);

        if ($trimmed === '') {
            return [];
        }

        $normalizedIla = preg_replace('/(^|\s)الي(?=\s|$)/u', '$1اليا', $trimmed) ?? $trimmed;
        $normalizedWawVerb = preg_replace('/(^|\s)([\p{Arabic}]{2,}عو)(?=\s|$)/u', '$1$2ا', $trimmed) ?? $trimmed;
        $normalizedCombined = preg_replace('/(^|\s)([\p{Arabic}]{2,}عو)(?=\s|$)/u', '$1$2ا', $normalizedIla)
            ?? $normalizedIla;

        $variants = [];

        foreach ([$normalizedIla, $normalizedWawVerb, $normalizedCombined] as $candidate) {
            $value = trim((string) $candidate);

            if ($value === '' || $value === $trimmed) {
                continue;
            }

            $variants[] = $value;
        }

        return array_values(array_unique($variants));
    }

    /**
     * @return array<int, string>
     */
    private static function expandHamzatedMaddWordVariantsForPhrase(string $text): array
    {
        $trimmed = trim($text);

        if ($trimmed === '') {
            return [];
        }

        $withHamzatedMadd = preg_replace('/(^|\s)الاء(?=\s|$)/u', '$1ءالاء', $trimmed) ?? $trimmed;
        $withoutHamzatedMadd = preg_replace('/(^|\s)ءالاء(?=\s|$)/u', '$1الاء', $trimmed) ?? $trimmed;

        $variants = [];

        foreach ([$withHamzatedMadd, $withoutHamzatedMadd] as $candidate) {
            $value = trim((string) $candidate);

            if ($value === '' || $value === $trimmed) {
                continue;
            }

            $variants[] = $value;
        }

        return array_values(array_unique($variants));
    }
}
