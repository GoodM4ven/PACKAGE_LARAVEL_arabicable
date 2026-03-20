<?php

declare(strict_types=1);

use GoodMaven\Arabicable\Enums\ArabicSpecialCharacters;
use GoodMaven\Arabicable\Facades\Arabic;
use GoodMaven\Arabicable\Support\Config\ArabicableConfig;
use GoodMaven\Arabicable\Support\Text\ArabicWordVariants;

it('provides property helper suffixes', function (): void {
    expect(ar_indian('date'))->toBe('date_indian');
    expect(ar_with_harakat('content'))->toBe('content_with_harakat');
    expect(ar_searchable('content'))->toBe('content_searchable');
    expect(ar_stem('content'))->toBe('content_stemmed');
});

it('filters and combines special characters', function (): void {
    $marks = arabicable_special_characters(only: [
        ArabicSpecialCharacters::ForeignPunctuationMarks,
    ]);

    expect($marks)->toBe([',', ';', '?']);

    $map = arabicable_special_characters(
        only: [ArabicSpecialCharacters::IndianNumerals, ArabicSpecialCharacters::ArabicNumerals],
        combineInstead: true,
    );

    expect($map['1'])->toBe('١');
});

it('expands lexical variants through helper wrapper', function (): void {
    ArabicableConfig::set('arabicable.data_sources.word_variants', __DIR__.'/Fixtures/verbs-classified-compiled-mini.tsv');
    ArabicableConfig::set('arabicable.data_sources.quran_word_index', __DIR__.'/Fixtures/quran-word-index-compiled-mini.tsv');
    ArabicableConfig::set('arabicable.data_sources.stop_words_forms', __DIR__.'/Fixtures/stopwords-forms-compiled-mini.tsv');

    app(ArabicWordVariants::class)->clearCache();

    $variants = ar_expand_variants('يضرب', mode: 'original_words');

    expect($variants)->toContain('ضارب');
    expect($variants)->toContain('ضروب');
});

it('converts gregorian and hijri dates in both directions', function (): void {
    $hijri = Arabic::gregorianToHijri(2025, 1, 1);
    $gregorian = Arabic::hijriToGregorian($hijri['year'], $hijri['month'], $hijri['day']);

    expect($hijri)->toHaveKeys(['year', 'month', 'day']);
    expect($hijri['year'])->toBeInt();
    expect($hijri['month'])->toBeInt();
    expect($hijri['day'])->toBeInt();
    expect($gregorian)->toBe(['year' => 2025, 'month' => 1, 'day' => 1]);
});
