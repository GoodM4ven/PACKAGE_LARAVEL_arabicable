<?php

declare(strict_types=1);

use GoodMaven\Arabicable\CamelTools;

it('resolves camel tools from the laravel container', function (): void {
    expect(app(CamelTools::class))->toBeInstanceOf(CamelTools::class);
    expect(camel_tools())->toBeInstanceOf(CamelTools::class);
});

it('applies arabic normalization and dediacritization helpers', function (): void {
    $camel = app(CamelTools::class);

    expect($camel->normalizeAlefAr('أقبل إِلى آفاق'))->toBe('اقبل اِلى افاق');
    expect($camel->normalizeAlefMaksuraAr('هدى'))->toBe('هدي');
    expect($camel->normalizeTehMarbutaAr('مدرسة'))->toBe('مدرسه');
    expect($camel->dediacAr('هَلْ ذَهَبْتَ'))->toBe('هل ذهبت');
});

it('supports global helper wrappers around builtin maps', function (): void {
    expect(camel_map_builtin('ar2bw', 'السلام'))->not->toBe('');
    expect(camel_transliterate_builtin('ar2bw', '@@السلام', '@@'))->toBe('@@السلام');
    expect(camel_transliterate_builtin('ar2bw', '@@السلام', '@@', true))->toBe('السلام');
    expect(camel_simple_word_tokenize('مرحبا، بالعالم!'))->toBe(['مرحبا', '،', 'بالعالم', '!']);
});

it('supports encoding-aware normalization and dediac helpers', function (): void {
    expect(camel_normalize_alef('<>{|', 'bw'))->toBe('AAAA');
    expect(camel_normalize_alef_maksura('Y', 'bw'))->toBe('y');
    expect(camel_normalize_teh_marbuta('p', 'bw'))->toBe('h');
    expect(camel_dediac('katabaF', 'bw'))->toBe('ktb');
});

it('cleans Arabic text through the arclean builtin mapper', function (): void {
    expect(camel_arclean('السَّلَامُ'))->toBe('السلام');
});
