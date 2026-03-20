<?php

declare(strict_types=1);

use GoodMaven\Arabicable\Support\Camel\CamelCharMapper;
use GoodMaven\Arabicable\Support\Exceptions\CamelBuiltinCharMapNotFoundException;
use GoodMaven\Arabicable\Support\Exceptions\CamelInvalidCharMapKeyException;

it('validates char mapper constructor payload types', function (): void {
    expect(fn () => new CamelCharMapper(null))->toThrow(TypeError::class);
    expect(fn () => new CamelCharMapper([], null))->not->toThrow(Throwable::class);
    expect(fn () => new CamelCharMapper(['a' => 'ok'], 'default'))->not->toThrow(Throwable::class);
    expect(fn () => new CamelCharMapper(['a' => 'ok'], []))->toThrow(TypeError::class);
});

it('validates char map keys and values', function (): void {
    expect(fn () => new CamelCharMapper(['a-f' => '']))->not->toThrow(Throwable::class);
    expect(fn () => new CamelCharMapper(['a' => null]))->not->toThrow(Throwable::class);

    expect(fn () => new CamelCharMapper(['c-a' => 'x']))->toThrow(CamelInvalidCharMapKeyException::class);
    expect(fn () => new CamelCharMapper(['a--' => 'x']))->toThrow(CamelInvalidCharMapKeyException::class);
    expect(fn () => new CamelCharMapper(['a-' => 'x']))->toThrow(CamelInvalidCharMapKeyException::class);
    expect(fn () => new CamelCharMapper(['a' => []]))->toThrow(TypeError::class);
});

it('maps unicode strings with ranged and direct mappings', function (): void {
    $mapper = new CamelCharMapper([
        'e' => 'u',
        'h-m' => '*',
        'a-d' => 'm',
        '٠' => '0',
        '١' => '1',
        '٢' => '2',
        '٣-٥' => '-',
        '٦-٩' => '+',
    ]);

    expect($mapper->mapString('Hello, world!'))->toBe('Hu**o, wor*m!');
    expect($mapper->mapString('٠١٢٣٤٥٦٧٨٩'))->toBe('012---++++');
    expect(fn () => $mapper->mapString(null))->toThrow(TypeError::class);
});

it('keeps characters when a map key explicitly points to null', function (): void {
    $mapper = new CamelCharMapper([
        'س' => null,
    ], '');

    expect($mapper->mapString('سلام'))->toBe('س');
});

it('loads builtin charmaps and reports missing ones', function (): void {
    $mapper = CamelCharMapper::builtinMapper('ar2bw');

    expect($mapper->mapString('ا'))->toBe('A');
    expect(fn () => CamelCharMapper::builtinMapper('does-not-exist'))
        ->toThrow(CamelBuiltinCharMapNotFoundException::class);
});
