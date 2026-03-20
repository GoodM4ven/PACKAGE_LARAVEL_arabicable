<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Facades;

use GoodMaven\Arabicable\Support\Camel\CamelCharMapper;
use GoodMaven\Arabicable\Support\Camel\CamelTransliterator;
use Illuminate\Support\Facades\Facade;

/**
 * @method static CamelCharMapper mapper(iterable $charMap, ?string $default = null)
 * @method static CamelCharMapper mapperFromJson(string $path)
 * @method static CamelCharMapper builtinMapper(string $mapName)
 * @method static list<string> builtinCharmaps()
 * @method static string mapWithBuiltin(string $mapName, string $text)
 * @method static string arclean(string $text)
 * @method static CamelTransliterator transliterator(CamelCharMapper $mapper, string $marker = '@@IGNORE@@')
 * @method static string transliterateWithBuiltin(string $mapName, string $text, string $marker = '@@IGNORE@@', bool $stripMarkers = false, bool $ignoreMarkers = false)
 * @method static string normalizeUnicode(string $text, bool $compatibility = true)
 * @method static string normalizeAlef(string $text, string $encoding = 'ar')
 * @method static string normalizeAlefAr(string $text)
 * @method static string normalizeAlefBw(string $text)
 * @method static string normalizeAlefSafeBw(string $text)
 * @method static string normalizeAlefXmlBw(string $text)
 * @method static string normalizeAlefHsb(string $text)
 * @method static string normalizeAlefMaksura(string $text, string $encoding = 'ar')
 * @method static string normalizeAlefMaksuraAr(string $text)
 * @method static string normalizeAlefMaksuraBw(string $text)
 * @method static string normalizeAlefMaksuraSafeBw(string $text)
 * @method static string normalizeAlefMaksuraXmlBw(string $text)
 * @method static string normalizeAlefMaksuraHsb(string $text)
 * @method static string normalizeTehMarbuta(string $text, string $encoding = 'ar')
 * @method static string normalizeTehMarbutaAr(string $text)
 * @method static string normalizeTehMarbutaBw(string $text)
 * @method static string normalizeTehMarbutaSafeBw(string $text)
 * @method static string normalizeTehMarbutaXmlBw(string $text)
 * @method static string normalizeTehMarbutaHsb(string $text)
 * @method static string normalizeOrthography(string $text, string $encoding = 'ar')
 * @method static string dediac(string $text, string $encoding = 'ar')
 * @method static string dediacAr(string $text)
 * @method static string dediacBw(string $text)
 * @method static string dediacSafeBw(string $text)
 * @method static string dediacXmlBw(string $text)
 * @method static string dediacHsb(string $text)
 * @method static array<int, string> simpleWordTokenize(string $text, bool $splitDigits = false)
 *
 * @see \GoodMaven\Arabicable\CamelTools
 */
final class CamelTools extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \GoodMaven\Arabicable\CamelTools::class;
    }
}
