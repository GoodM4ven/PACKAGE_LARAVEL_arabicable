<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string withHarakat(string $text)
 * @method static string withoutHarakat(string $text)
 * @method static string withoutDiacritics(string $text, bool $keepShadda = false)
 * @method static string forSearch(string $text)
 * @method static string forStem(string $text)
 * @method static string forMemorizationComparison(string $text, bool $stripCommons = true, bool $stripConnectors = true)
 *
 * @see \GoodMaven\Arabicable\ArabicFilter
 */
final class ArabicFilter extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \GoodMaven\Arabicable\ArabicFilter::class;
    }
}
