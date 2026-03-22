<?php

declare(strict_types=1);

it('keeps published package css focused on reusable package styles', function (): void {
    $distCssPath = dirname(__DIR__).'/resources/dist/arabicable.css';
    $contents = file_get_contents($distCssPath);

    expect($contents)->not->toBeFalse();
    expect($contents)->toContain('.font-quran');
    expect($contents)->toContain('.font-quran-surah-header');
    expect($contents)->not->toContain('.quran-ayah-line-run');
    expect($contents)->not->toContain('.quran-word-button');
    expect($contents)->not->toContain('.quran-segment-hover');
    expect($contents)->not->toContain('.rounded-2xl');
});
