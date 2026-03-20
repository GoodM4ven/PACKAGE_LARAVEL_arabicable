<?php

declare(strict_types=1);

use GoodMaven\Arabicable\CamelTools;

it('normalizes alef variants across supported encodings', function (): void {
    $camel = app(CamelTools::class);

    expect($camel->normalizeAlefAr('إأٱآ'))->toBe('اااا');
    expect($camel->normalizeAlefBw('<>{|'))->toBe('AAAA');
    expect($camel->normalizeAlefSafeBw('IOLM'))->toBe('AAAA');
    expect($camel->normalizeAlefXmlBw('IO{|'))->toBe('AAAA');
    expect($camel->normalizeAlefHsb('ĂÂÄĀ'))->toBe('AAAA');
});

it('normalizes alef maksura and teh marbuta across supported encodings', function (): void {
    $camel = app(CamelTools::class);

    expect($camel->normalizeAlefMaksuraAr('على'))->toBe('علي');
    expect($camel->normalizeAlefMaksuraBw('Y'))->toBe('y');
    expect($camel->normalizeAlefMaksuraSafeBw('Y'))->toBe('y');
    expect($camel->normalizeAlefMaksuraXmlBw('Y'))->toBe('y');
    expect($camel->normalizeAlefMaksuraHsb('ý'))->toBe('y');

    expect($camel->normalizeTehMarbutaAr('مدرسة'))->toBe('مدرسه');
    expect($camel->normalizeTehMarbutaBw('p'))->toBe('h');
    expect($camel->normalizeTehMarbutaSafeBw('p'))->toBe('h');
    expect($camel->normalizeTehMarbutaXmlBw('p'))->toBe('h');
    expect($camel->normalizeTehMarbutaHsb('ħ'))->toBe('h');
});

it('normalizes combined orthography with a single call', function (): void {
    $camel = app(CamelTools::class);

    expect($camel->normalizeOrthography('إلى مدرسة'))->toBe('الي مدرسه');
    expect($camel->normalizeOrthography('<Yp', 'bw'))->toBe('Ayh');
});

it('dediacritizes across supported encodings', function (): void {
    $camel = app(CamelTools::class);

    expect($camel->dediacAr('السَّلَامُ'))->toBe('السلام');
    expect($camel->dediacBw('katabaF'))->toBe('ktb');
    expect($camel->dediacSafeBw('katabaF'))->toBe('ktb');
    expect($camel->dediacXmlBw('katabaF'))->toBe('ktb');
    expect($camel->dediacHsb('kÄat'))
        ->toBe('kt');
});

it('throws for unsupported encoding names', function (): void {
    $camel = app(CamelTools::class);

    expect(fn () => $camel->dediac('test', 'unknown'))->toThrow(InvalidArgumentException::class);
    expect(fn () => $camel->normalizeAlef('test', 'unknown'))->toThrow(InvalidArgumentException::class);
});
