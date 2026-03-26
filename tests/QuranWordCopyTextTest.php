<?php

declare(strict_types=1);

use GoodMaven\Arabicable\Support\Quran\QuranWordCopyText;

it('builds a copy-text map keyed by surah ayah and word position', function (): void {
    $rows = [
        (object) [
            'surah_number' => 1,
            'ayah_number' => 1,
            'word_position' => 1,
            'token_uthmani' => 'بِسْمِ',
            'token_searchable_typed' => 'بسم',
        ],
        (object) [
            'surah_number' => 1,
            'ayah_number' => 1,
            'word_position' => 2,
            'token_uthmani' => '',
            'token_searchable_typed' => 'الله',
        ],
        (object) [
            'surah_number' => 1,
            'ayah_number' => 1,
            'word_position' => 0,
            'token_uthmani' => 'ignored',
            'token_searchable_typed' => 'ignored',
        ],
    ];

    $map = QuranWordCopyText::buildMapByAyahPosition($rows);

    expect($map)
        ->toHaveKey('1:1:1')
        ->toHaveKey('1:1:2')
        ->not->toHaveKey('1:1:0')
        ->and($map['1:1:1'])->toBe('بِسْمِ')
        ->and($map['1:1:2'])->toBe('الله');
});

it('normalizes quran word copy tokens with uthmani priority and typed fallback', function (): void {
    expect(QuranWordCopyText::normalizeToken(' ٱللَّهُ ', 'الله'))->toBe('ٱللَّهُ')
        ->and(QuranWordCopyText::normalizeToken('', 'الله'))->toBe('الله')
        ->and(QuranWordCopyText::normalizeToken('   ', '   '))->toBeNull();
});

it('builds ayah word key only for valid positive indexes', function (): void {
    expect(QuranWordCopyText::ayahWordKey(2, 255, 3))->toBe('2:255:3')
        ->and(QuranWordCopyText::ayahWordKey(0, 1, 1))->toBeNull()
        ->and(QuranWordCopyText::ayahWordKey(1, 0, 1))->toBeNull()
        ->and(QuranWordCopyText::ayahWordKey(1, 1, 0))->toBeNull();
});
