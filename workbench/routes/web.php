<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::view('/', 'demo')->name('demo');
Route::view('/quran-reader', 'quran-reader')->name('quran-reader');

Route::get('/qpc-v2-fonts/{page}.ttf', function (int $page) {
    if ($page < 1 || $page > 604) {
        abort(404);
    }

    $candidates = [
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

    if (! is_string($fontPath) || $fontPath === '') {
        abort(404);
    }

    return response()->file($fontPath, [
        'Content-Type' => 'font/ttf',
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
})->whereNumber('page')->name('qpc-v2-font');
