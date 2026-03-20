<?php

declare(strict_types=1);

use GoodMaven\Arabicable\Arabic;
use GoodMaven\Arabicable\CamelTools;
use GoodMaven\Arabicable\Enums\ArabicSpecialCharacters;
use GoodMaven\Arabicable\Support\Config\ArabicableConfig;
use GoodMaven\Arabicable\Support\Data\ValueSelector;
use GoodMaven\Arabicable\Support\Exceptions\ArabicableFunctionException;

if (! function_exists('ar_indian')) {
    function ar_indian(string $property): string
    {
        return $property.ArabicableConfig::get('arabicable.property_suffix_keys.numbers_to_indian', '_indian');
    }
}

if (! function_exists('ar_with_harakat')) {
    function ar_with_harakat(string $property): string
    {
        return $property.ArabicableConfig::get('arabicable.property_suffix_keys.text_with_harakat', '_with_harakat');
    }
}

if (! function_exists('ar_searchable')) {
    function ar_searchable(string $property): string
    {
        return $property.ArabicableConfig::get('arabicable.property_suffix_keys.text_for_search', '_searchable');
    }
}

if (! function_exists('ar_stem')) {
    function ar_stem(string $property): string
    {
        return $property.ArabicableConfig::get('arabicable.property_suffix_keys.text_for_stem', '_stemmed');
    }
}

if (! function_exists('ar_expand_variants')) {
    /**
     * @return array<int, string>
     */
    function ar_expand_variants(
        string|array $words,
        ?int $maxVariantsPerToken = null,
        ?int $maxTerms = null,
        ?string $mode = null,
        ?bool $stripStopWords = null,
    ): array {
        return app(Arabic::class)
            ->expandWordVariants($words, $maxVariantsPerToken, $maxTerms, $mode, $stripStopWords);
    }
}

if (! function_exists('arabicable_special_characters')) {
    /**
     * @param  array<int, ArabicSpecialCharacters>|ArabicSpecialCharacters  $only
     * @param  array<int, ArabicSpecialCharacters>|ArabicSpecialCharacters  $except
     * @return array<int, string>|array<string, string>
     */
    function arabicable_special_characters(
        array|ArabicSpecialCharacters $only = [],
        array|ArabicSpecialCharacters $except = [],
        bool $combineInstead = false,
    ): array {
        $characters = ValueSelector::filterEnums(ArabicSpecialCharacters::cases(), $only, $except);

        if (! ValueSelector::isEnumArray($characters, ArabicSpecialCharacters::class)) {
            throw new ArabicableFunctionException('Only ArabicSpecialCharacters enum cases are allowed.');
        }

        $characterArrays = collect($characters)
            ->map(static fn (ArabicSpecialCharacters $char): array => $char->get())
            ->toArray();

        if (! $combineInstead) {
            return array_merge(...$characterArrays);
        }

        $count = count($characterArrays);

        if ($count !== 2) {
            throw new ArabicableFunctionException(
                "Combining works only with exactly two character sets. Currently {$count} are considered.",
            );
        }

        /** @var array<string, string> $combined */
        $combined = array_combine($characterArrays[0], $characterArrays[1]);

        return $combined;
    }
}

if (! function_exists('camel_tools')) {
    function camel_tools(): CamelTools
    {
        return app(CamelTools::class);
    }
}

if (! function_exists('camel_map_builtin')) {
    function camel_map_builtin(string $mapName, string $text): string
    {
        return camel_tools()->mapWithBuiltin($mapName, $text);
    }
}

if (! function_exists('camel_transliterate_builtin')) {
    function camel_transliterate_builtin(
        string $mapName,
        string $text,
        string $marker = '@@IGNORE@@',
        bool $stripMarkers = false,
        bool $ignoreMarkers = false,
    ): string {
        return camel_tools()->transliterateWithBuiltin(
            $mapName,
            $text,
            $marker,
            $stripMarkers,
            $ignoreMarkers,
        );
    }
}

if (! function_exists('camel_arclean')) {
    function camel_arclean(string $text): string
    {
        return camel_tools()->arclean($text);
    }
}

if (! function_exists('camel_dediac_ar')) {
    function camel_dediac_ar(string $text): string
    {
        return camel_tools()->dediacAr($text);
    }
}

if (! function_exists('camel_dediac')) {
    function camel_dediac(string $text, string $encoding = 'ar'): string
    {
        return camel_tools()->dediac($text, $encoding);
    }
}

if (! function_exists('camel_normalize_alef')) {
    function camel_normalize_alef(string $text, string $encoding = 'ar'): string
    {
        return camel_tools()->normalizeAlef($text, $encoding);
    }
}

if (! function_exists('camel_normalize_alef_maksura')) {
    function camel_normalize_alef_maksura(string $text, string $encoding = 'ar'): string
    {
        return camel_tools()->normalizeAlefMaksura($text, $encoding);
    }
}

if (! function_exists('camel_normalize_teh_marbuta')) {
    function camel_normalize_teh_marbuta(string $text, string $encoding = 'ar'): string
    {
        return camel_tools()->normalizeTehMarbuta($text, $encoding);
    }
}

if (! function_exists('camel_normalize_orthography')) {
    function camel_normalize_orthography(string $text, string $encoding = 'ar'): string
    {
        return camel_tools()->normalizeOrthography($text, $encoding);
    }
}

if (! function_exists('camel_simple_word_tokenize')) {
    /**
     * @return array<int, string>
     */
    function camel_simple_word_tokenize(string $text, bool $splitDigits = false): array
    {
        return camel_tools()->simpleWordTokenize($text, $splitDigits);
    }
}
