<?php

declare(strict_types=1);

use GoodMaven\Arabicable\Support\Quran\QuranSearchText;

it('normalizes quran search query while removing hidden directional characters', function (): void {
    $normalized = QuranSearchText::normalizeQuery("وقال ربكم\u{200F} ادعوني أستجب لكم");

    expect($normalized)->toBe('وقال ربكم ادعوني استجب لكم');
});

it('expands strict exact phrase variants for vocative shorthand and legacy orthography', function (): void {
    $variants = QuranSearchText::expandStrictExactPhraseVariants("ي\u{200C}بني أقم الصلاة");

    expect($variants)
        ->toContain('يابني اقم الصلاة')
        ->toContain('يابني اقم الصلواة');
});

it('prepares search tokens with vocative-only token removed', function (): void {
    $tokens = QuranSearchText::prepareTokens(['يا', 'بني', 'اقم', 'الصلاة']);

    expect($tokens)->toBe(['بني', 'اقم', 'الصلاة']);
});
