<?php

declare(strict_types=1);

use GoodMaven\Arabicable\Support\Camel\CamelCharMapper;
use GoodMaven\Arabicable\Support\Camel\CamelTransliterator;

function testMapper(): CamelCharMapper
{
    return new CamelCharMapper([
        'A-Z' => 'X',
        'a-z' => 'x',
    ]);
}

it('validates transliterator marker constraints', function (): void {
    expect(fn () => new CamelTransliterator(testMapper(), ''))->toThrow(ValueError::class);
    expect(fn () => new CamelTransliterator(testMapper(), '@@LAT @@'))->toThrow(ValueError::class);
    expect(fn () => new CamelTransliterator(testMapper(), ' @@LAT@@'))->toThrow(ValueError::class);
    expect(fn () => new CamelTransliterator(testMapper(), '@@LAT@@ '))->toThrow(ValueError::class);
    expect(fn () => new CamelTransliterator(testMapper(), '@@LAT@@'))->not->toThrow(Throwable::class);
});

it('transliterates while respecting marker logic', function (): void {
    $trans = new CamelTransliterator(testMapper(), '@@');

    expect($trans->transliterate(''))->toBe('');
    expect($trans->transliterate('Hello'))->toBe('Xxxxx');
    expect($trans->transliterate('@@Hello'))->toBe('@@Hello');
    expect($trans->transliterate('@@Hello', true))->toBe('Hello');
    expect($trans->transliterate('@@Hello', false, true))->toBe('@@Xxxxx');
    expect($trans->transliterate('@@Hello', true, true))->toBe('Xxxxx');

    expect($trans->transliterate('Hello @@World, this is a @@sentence!'))
        ->toBe('Xxxxx @@World, xxxx xx x @@sentence!');

    expect($trans->transliterate('Hello @@World, this is a @@sentence!', true))
        ->toBe('Xxxxx World, xxxx xx x sentence!');

    expect($trans->transliterate('Hello @@World, this is a @@sentence!', false, true))
        ->toBe('Xxxxx @@Xxxxx, xxxx xx x @@xxxxxxxx!');

    expect($trans->transliterate('Hello @@World, this is a @@sentence!', true, true))
        ->toBe('Xxxxx Xxxxx, xxxx xx x xxxxxxxx!');
});
