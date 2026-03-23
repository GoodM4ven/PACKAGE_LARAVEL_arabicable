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

it('ships configurable basmalah variants and defaults to quran-common ligature', function (): void {
    $configured = config('arabicable.quran_fonts.basmalah', []);
    $available = is_array($configured) ? ($configured['available'] ?? []) : [];

    expect($configured)->toBeArray();
    expect((string) ($configured['preferred'] ?? ''))->toBe('quran-common-ligature');
    expect($available)->toBeArray();
    expect($available)->toHaveKeys(['madina-default', 'surah-names-v4', 'quran-common-ligature']);
    expect((string) ($available['quran-common-ligature']['glyph'] ?? ''))->toBe(mb_chr(0xFDFD, 'UTF-8'));
    expect((string) ($available['quran-common-ligature']['filename'] ?? ''))->toBe('quran-common.woff2');
    expect((string) ($available['surah-names-v4']['filename'] ?? ''))->toBe('surah_names.woff2');
    expect(is_file(dirname(__DIR__).'/resources/raw-data/quran/fonts/surah-headers/quran-common.woff2'))->toBeTrue();
    expect(is_file(dirname(__DIR__).'/resources/raw-data/quran/fonts/surah-headers/surah_names.woff2'))->toBeTrue();
    expect(is_file(dirname(__DIR__).'/resources/dist/quran-common.woff2'))->toBeTrue();
    expect(is_file(dirname(__DIR__).'/resources/dist/surah_names.woff2'))->toBeTrue();
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

it('uses configured basmalah variant glyph by default except for al-fatiha', function (): void {
    $component = new QuranReader;
    $reflection = new ReflectionClass($component);
    $method = $reflection->getMethod('formatBasmalahLabel');
    $method->setAccessible(true);

    expect($method->invoke($component, 2))->toBe(mb_chr(0xFDFD, 'UTF-8'));
    expect($method->invoke($component, 1))->toBe('بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ');
});

it('allows switching basmalah default variant through config', function (): void {
    config()->set('arabicable.quran_fonts.basmalah.preferred', 'madina-default');

    $component = new QuranReader;
    $reflection = new ReflectionClass($component);
    $method = $reflection->getMethod('formatBasmalahLabel');
    $method->setAccessible(true);

    expect($method->invoke($component, 2))->toBe('بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ');
});
