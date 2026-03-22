<?php

declare(strict_types=1);

it('ships configurable surah header fonts and package files', function (): void {
    $available = config('arabicable.quran_fonts.surah_headers.available', []);

    expect($available)->toBeArray()
        ->toHaveKeys(['qcf-surah-header-color-regular', 'surah-name-v2']);

    $expectedFonts = [
        'qcf-surah-header-color-regular' => [
            'family' => 'QcfSurahHeaderColor',
            'filename' => 'QCF_SurahHeader_COLOR-Regular.woff2',
        ],
        'surah-name-v2' => [
            'family' => 'SurahNameV2',
            'filename' => 'surah-name-v2.woff2',
        ],
    ];

    foreach ($expectedFonts as $fontKey => $fontMeta) {
        $configured = $available[$fontKey] ?? [];
        $filename = (string) ($fontMeta['filename'] ?? '');
        $rawDataFile = dirname(__DIR__).'/resources/raw-data/quran/fonts/surah-headers/'.$filename;
        $distFile = dirname(__DIR__).'/resources/dist/'.$filename;

        expect($configured)->toBeArray();
        expect((string) ($configured['family'] ?? ''))->toBe((string) ($fontMeta['family'] ?? ''));
        expect((string) ($configured['filename'] ?? ''))->toBe($filename);
        expect(is_file($rawDataFile))->toBeTrue();
        expect(is_file($distFile))->toBeTrue();
    }
});
