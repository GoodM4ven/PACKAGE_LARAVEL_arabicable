<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Support\Config;

use GoodMaven\Arabicable\Models\CommonArabicText;
use InvalidArgumentException;

class ArabicableConfigValidator
{
    public static function validate(): void
    {
        if (! (bool) ArabicableConfig::get('arabicable.validate_configuration', true)) {
            return;
        }

        self::validateSpecialCharacters();
        self::validateSuffixes();
        self::validateNumeralsMode();
        self::validateComprehensiveSearchConfig();
        self::validateDataSources();
        self::validateModelClass('arabicable.common_arabic_text.model', CommonArabicText::class);
    }

    private static function validateSpecialCharacters(): void
    {
        $value = ArabicableConfig::get('arabicable.special_characters');

        if (! is_array($value) || count($value) !== 12) {
            throw new InvalidArgumentException('arabicable.special_characters must be an array of 12 entries.');
        }

        foreach ($value as $key => $list) {
            if (! is_string($key) || ! is_array($list)) {
                throw new InvalidArgumentException('arabicable.special_characters keys must be strings and values must be arrays.');
            }

            foreach ($list as $item) {
                if (! is_string($item) || $item === '') {
                    throw new InvalidArgumentException('arabicable.special_characters values must contain only non-empty strings.');
                }
            }
        }
    }

    private static function validateSuffixes(): void
    {
        $suffixes = ArabicableConfig::get('arabicable.property_suffix_keys', []);
        $required = ['numbers_to_indian', 'text_with_harakat', 'text_for_search', 'text_for_stem'];

        foreach ($required as $key) {
            if (! isset($suffixes[$key]) || ! is_string($suffixes[$key]) || $suffixes[$key] === '') {
                throw new InvalidArgumentException("arabicable.property_suffix_keys.{$key} must be a non-empty string.");
            }
        }
    }

    private static function validateNumeralsMode(): void
    {
        $mode = ArabicableConfig::get('arabicable.numerals.search_mode', 'arabic');

        if (! in_array($mode, ['arabic', 'indian', 'both'], true)) {
            throw new InvalidArgumentException('arabicable.numerals.search_mode must be one of: arabic, indian, both.');
        }
    }

    private static function validateModelClass(string $key, string $fallback): void
    {
        $modelClass = ArabicableConfig::get($key, $fallback);

        if (! is_string($modelClass) || ! class_exists($modelClass)) {
            throw new InvalidArgumentException("{$key} must point to an existing class.");
        }
    }

    private static function validateComprehensiveSearchConfig(): void
    {
        $config = ArabicableConfig::get('arabicable.search.comprehensive', []);

        if (! is_array($config)) {
            throw new InvalidArgumentException('arabicable.search.comprehensive must be an array.');
        }

        $maxTerms = $config['max_terms'] ?? 60;
        if (! is_int($maxTerms) || $maxTerms < 1) {
            throw new InvalidArgumentException('arabicable.search.comprehensive.max_terms must be an integer >= 1.');
        }

        $expand = $config['expand_with_word_variants'] ?? true;
        if (! is_bool($expand)) {
            throw new InvalidArgumentException('arabicable.search.comprehensive.expand_with_word_variants must be boolean.');
        }

        $maxPerToken = $config['max_word_variants_per_token'] ?? 24;
        if (! is_int($maxPerToken) || $maxPerToken < 1) {
            throw new InvalidArgumentException('arabicable.search.comprehensive.max_word_variants_per_token must be an integer >= 1.');
        }

        $maxVariantTerms = $config['max_variant_terms'] ?? 120;
        if (! is_int($maxVariantTerms) || $maxVariantTerms < 1) {
            throw new InvalidArgumentException('arabicable.search.comprehensive.max_variant_terms must be an integer >= 1.');
        }

        $minVariantLength = $config['min_variant_term_length'] ?? 2;
        if (! is_int($minVariantLength) || $minVariantLength < 1) {
            throw new InvalidArgumentException('arabicable.search.comprehensive.min_variant_term_length must be an integer >= 1.');
        }

        $variantMode = $config['variant_mode'] ?? 'all';
        if (! is_string($variantMode) || ! in_array($variantMode, ['all', 'roots', 'stems', 'original_words'], true)) {
            throw new InvalidArgumentException('arabicable.search.comprehensive.variant_mode must be one of: all, roots, stems, original_words.');
        }

        $stripStopWords = $config['strip_stop_words_from_variants'] ?? true;
        if (! is_bool($stripStopWords)) {
            throw new InvalidArgumentException('arabicable.search.comprehensive.strip_stop_words_from_variants must be boolean.');
        }
    }

    private static function validateDataSources(): void
    {
        $sources = ArabicableConfig::get('arabicable.data_sources', []);

        if (! is_array($sources)) {
            throw new InvalidArgumentException('arabicable.data_sources must be an array.');
        }

        foreach ($sources as $key => $path) {
            if (! is_string($key) || ! is_string($path)) {
                throw new InvalidArgumentException('arabicable.data_sources must contain only string keys and string paths.');
            }
        }
    }
}
