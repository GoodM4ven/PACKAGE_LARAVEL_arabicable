<?php

declare(strict_types=1);

use Workbench\App\Livewire\QuranReader;

it('ships only v4 surah header font metadata and package files', function (): void {
    $configured = config('arabicable.quran_fonts.surah_headers', []);

    expect($configured)->toBeArray();
    expect((string) ($configured['family'] ?? ''))->toBe('SurahNameV4');
    expect((string) ($configured['filename'] ?? ''))->toBe('surah-name-v4.ttf');
    expect((string) ($configured['format'] ?? ''))->toBe('ttf');
    expect(is_file(dirname(__DIR__).'/resources/raw-data/quran/fonts/surah-headers/surah-name-v4.ttf'))->toBeTrue();
    expect(is_file(dirname(__DIR__).'/resources/dist/surah-name-v4.ttf'))->toBeTrue();
});

it('maps surah header numbers to v4 glyph codepoints', function (): void {
    $component = new QuranReader;
    $reflection = new ReflectionClass($component);
    $method = $reflection->getMethod('formatSurahHeaderLabel');
    $method->setAccessible(true);

    expect($method->invoke($component, 2))->toBe(mb_chr(0xE002, 'UTF-8'));
    expect($method->invoke($component, 102))->toBe(mb_chr(0xE066, 'UTF-8'));
    expect($method->invoke($component, 114))->toBe(mb_chr(0xE072, 'UTF-8'));
});

it('falls back to readable arabic surah label for invalid surah numbers', function (): void {
    $component = new QuranReader;
    $reflection = new ReflectionClass($component);
    $method = $reflection->getMethod('formatSurahHeaderLabel');
    $method->setAccessible(true);

    expect($method->invoke($component, 0))->toBe('سورة');
});
