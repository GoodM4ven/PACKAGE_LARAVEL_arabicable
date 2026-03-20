<?php

declare(strict_types=1);

use GoodMaven\Arabicable\Facades\Arabic;
use GoodMaven\Arabicable\Facades\ArabicFilter;
use GoodMaven\Arabicable\Support\Config\ArabicableConfig;

it('formats with harakat based on punctuation spacing config', function (): void {
    ArabicableConfig::set('arabicable.spacing_after_punctuation_only', false);

    expect(ArabicFilter::withHarakat('123, بسم'))
        ->toBe('١٢٣ ، بسم');

    ArabicableConfig::set('arabicable.spacing_after_punctuation_only', true);

    expect(ArabicFilter::withHarakat('123, بسم'))
        ->toBe('١٢٣، بسم');
});

it('builds a normalized search string', function (): void {
    ArabicableConfig::set('arabicable.numerals.search_mode', 'arabic');

    expect(ArabicFilter::forSearch('أَحْمَد، 12!'))
        ->toBe('احمد 12');
});

it('keeps token boundaries when punctuation has no surrounding spaces', function (): void {
    expect(ArabicFilter::forSearch('الجهل،فليس'))->toBe('الجهل فليس');
});

it('normalizes hamza-on-waw and hamza-on-yaa for search without standalone hamza artifacts', function (): void {
    expect(ArabicFilter::forSearch('المؤمنين'))
        ->toBe('المومنين');
});

it('supports multiple numerals search modes', function (): void {
    ArabicableConfig::set('arabicable.numerals.search_mode', 'both');
    expect(ArabicFilter::forSearch('أَحْمَد، 12!'))->toBe('احمد 12 ١٢');

    ArabicableConfig::set('arabicable.numerals.search_mode', 'indian');
    expect(ArabicFilter::forSearch('أَحْمَد، 12!'))->toBe('احمد ١٢');
});

it('sanitizes text while preserving punctuation when requested', function (): void {
    expect(Arabic::stripWeirdCharacters('أَهْلًا ### بكم!!! نص🙂', keepHarakat: false, keepPunctuation: true))
        ->toBe('أهلا بكم!!! نص');
});
