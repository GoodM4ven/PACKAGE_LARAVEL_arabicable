<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::view('/', 'demo')->name('demo');
Route::view('/quran-reader', 'quran-reader')->name('quran-reader');

Route::get('/qpc-v2-fonts/{page}', function (int $page) {
    if ($page < 1 || $page > 604) {
        abort(404);
    }

    $candidates = [
        base_path('resources/raw-data/quran/fonts/qpc-v2/p'.$page.'.woff2'),
        dirname(base_path()).'/resources/raw-data/quran/fonts/qpc-v2/p'.$page.'.woff2',
        base_path('vendor/goodm4ven/arabicable/resources/raw-data/quran/fonts/qpc-v2/p'.$page.'.woff2'),
        base_path('resources/raw-data/quran/fonts/qpc-v2/p'.$page.'.ttf'),
        dirname(base_path()).'/resources/raw-data/quran/fonts/qpc-v2/p'.$page.'.ttf',
        base_path('vendor/goodm4ven/arabicable/resources/raw-data/quran/fonts/qpc-v2/p'.$page.'.ttf'),
    ];

    $fontPath = null;

    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            $fontPath = $candidate;

            break;
        }
    }

    if ($fontPath === null) {
        abort(404);
    }

    $isWoff2 = str_ends_with($fontPath, '.woff2');

    return response()->file($fontPath, [
        'Content-Type' => $isWoff2 ? 'font/woff2' : 'font/ttf',
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
})->whereNumber('page')->name('qpc-v2-font');

$configuredSurahHeaderFonts = config('arabicable.quran_fonts.surah_headers.available', []);
$surahHeaderFonts = is_array($configuredSurahHeaderFonts) ? $configuredSurahHeaderFonts : [];

if ($surahHeaderFonts === []) {
    $surahHeaderFonts = [
        'qcf-surah-header-color-regular' => [
            'family' => 'QcfSurahHeaderColor',
            'filename' => 'QCF_SurahHeader_COLOR-Regular.woff2',
            'format' => 'woff2',
        ],
        'surah-name-v2' => [
            'family' => 'SurahNameV2',
            'filename' => 'surah-name-v2.woff2',
            'format' => 'woff2',
        ],
    ];
}

$surahHeaderFontKeys = array_values(array_filter(
    array_keys($surahHeaderFonts),
    static fn (mixed $value): bool => is_string($value) && $value !== '',
));

Route::get('/quran-surah-header-fonts/{font}', function (string $font) use ($surahHeaderFonts) {
    $fontKey = trim($font);
    $fontConfig = $surahHeaderFonts[$fontKey] ?? null;
    $filename = is_array($fontConfig) ? trim((string) ($fontConfig['filename'] ?? '')) : '';
    $format = is_array($fontConfig) ? trim((string) ($fontConfig['format'] ?? 'woff2')) : 'woff2';
    $configuredSurahHeadersDir = trim((string) config('arabicable.data_sources.quran_surah_headers_fonts_dir', ''));
    $configuredFontsDir = trim((string) config('arabicable.data_sources.quran_fonts_dir', ''));

    if ($filename === '') {
        abort(404);
    }

    $paths = [
        $configuredSurahHeadersDir !== '' ? $configuredSurahHeadersDir.'/'.$filename : null,
        $configuredFontsDir !== '' ? $configuredFontsDir.'/'.$filename : null,
        base_path('resources/raw-data/quran/fonts/surah-headers/'.$filename),
        dirname(base_path()).'/resources/raw-data/quran/fonts/surah-headers/'.$filename,
        base_path('vendor/goodm4ven/arabicable/resources/raw-data/quran/fonts/surah-headers/'.$filename),
        base_path('resources/raw-data/quran/fonts/'.$filename),
        dirname(base_path()).'/resources/raw-data/quran/fonts/'.$filename,
        base_path('vendor/goodm4ven/arabicable/resources/raw-data/quran/fonts/'.$filename),
        base_path('vendor/goodm4ven/arabicable/resources/dist/'.$filename),
    ];

    $fontPath = null;

    foreach ($paths as $path) {
        if (! is_string($path) || $path === '') {
            continue;
        }

        if (is_file($path)) {
            $fontPath = $path;

            break;
        }
    }

    if ($fontPath === null) {
        abort(404);
    }

    $isTrueType = in_array($format, ['ttf', 'truetype'], true) || str_ends_with($filename, '.ttf');

    return response()->file($fontPath, [
        'Content-Type' => $isTrueType ? 'font/ttf' : 'font/woff2',
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
})->whereIn('font', $surahHeaderFontKeys)->name('quran-surah-header-font');
