<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable;

use GoodMaven\Arabicable\Support\Camel\CamelCharMapper;
use GoodMaven\Arabicable\Support\Camel\CamelTransliterator;
use GoodMaven\Arabicable\Support\Camel\CamelWordTokenizer;
use InvalidArgumentException;

final class CamelTools
{
    /**
     * @var array<int, string>
     */
    private const AR_DIACRITICS = ['ً', 'ٌ', 'ٍ', 'َ', 'ُ', 'ِ', 'ّ', 'ْ', 'ٰ'];

    /**
     * @var array<int, string>
     */
    private const BW_DIACRITICS = ['F', 'K', 'N', '`', 'a', 'i', 'o', 'u', '~'];

    /**
     * @var array<int, string>
     */
    private const SAFEBW_DIACRITICS = ['F', 'K', 'N', 'a', 'e', 'i', 'o', 'u', '~'];

    /**
     * @var array<int, string>
     */
    private const XMLBW_DIACRITICS = ['F', 'K', 'N', '`', 'a', 'i', 'o', 'u', '~'];

    /**
     * @var array<int, string>
     */
    private const HSB_DIACRITICS = ['.', 'a', 'i', 'u', '~', 'Ä', 'á', 'ã', 'ĩ', 'ũ'];

    private ?CamelWordTokenizer $wordTokenizer = null;

    public function mapper(iterable $charMap, ?string $default = null): CamelCharMapper
    {
        return new CamelCharMapper($charMap, $default);
    }

    public function mapperFromJson(string $path): CamelCharMapper
    {
        return CamelCharMapper::mapperFromJson($path);
    }

    public function builtinMapper(string $mapName): CamelCharMapper
    {
        return CamelCharMapper::builtinMapper($mapName);
    }

    /**
     * @return list<string>
     */
    public function builtinCharmaps(): array
    {
        return CamelCharMapper::builtinCharmaps();
    }

    public function mapWithBuiltin(string $mapName, string $text): string
    {
        return $this->builtinMapper($mapName)->mapString($text);
    }

    public function arclean(string $text): string
    {
        return $this->mapWithBuiltin('arclean', $text);
    }

    public function transliterator(CamelCharMapper $mapper, string $marker = '@@IGNORE@@'): CamelTransliterator
    {
        return new CamelTransliterator($mapper, $marker);
    }

    public function transliterateWithBuiltin(
        string $mapName,
        string $text,
        string $marker = '@@IGNORE@@',
        bool $stripMarkers = false,
        bool $ignoreMarkers = false,
    ): string {
        $transliterator = new CamelTransliterator($this->builtinMapper($mapName), $marker);

        return $transliterator->transliterate($text, $stripMarkers, $ignoreMarkers);
    }

    public function normalizeUnicode(string $text, bool $compatibility = true): string
    {
        $fixed = strtr($text, [
            "\u{FDFC}" => 'ريال',
            "\u{FDFD}" => 'بسم الله الرحمن الرحيم',
        ]);

        if (! class_exists(\Normalizer::class)) {
            return $fixed;
        }

        $form = $compatibility ? \Normalizer::FORM_KC : \Normalizer::FORM_C;

        $normalized = \Normalizer::normalize($fixed, $form);

        return is_string($normalized) ? $normalized : $fixed;
    }

    public function normalizeAlefAr(string $text): string
    {
        return $this->normalizeAlef($text, 'ar');
    }

    public function normalizeAlefBw(string $text): string
    {
        return $this->normalizeAlef($text, 'bw');
    }

    public function normalizeAlefSafeBw(string $text): string
    {
        return $this->normalizeAlef($text, 'safebw');
    }

    public function normalizeAlefXmlBw(string $text): string
    {
        return $this->normalizeAlef($text, 'xmlbw');
    }

    public function normalizeAlefHsb(string $text): string
    {
        return $this->normalizeAlef($text, 'hsb');
    }

    public function normalizeAlef(string $text, string $encoding = 'ar'): string
    {
        return match ($this->normalizeEncoding($encoding)) {
            'ar' => preg_replace('/[\x{0625}\x{0623}\x{0671}\x{0622}]/u', 'ا', $text) ?? $text,
            'bw' => preg_replace('/[<>{|]/u', 'A', $text) ?? $text,
            'safebw' => preg_replace('/[IOLM]/u', 'A', $text) ?? $text,
            'xmlbw' => preg_replace('/[IO{|]/u', 'A', $text) ?? $text,
            'hsb' => preg_replace('/[\x{0102}\x{00C2}\x{00C4}\x{0100}]/u', 'A', $text) ?? $text,
        };
    }

    public function normalizeAlefMaksuraAr(string $text): string
    {
        return $this->normalizeAlefMaksura($text, 'ar');
    }

    public function normalizeAlefMaksuraBw(string $text): string
    {
        return $this->normalizeAlefMaksura($text, 'bw');
    }

    public function normalizeAlefMaksuraSafeBw(string $text): string
    {
        return $this->normalizeAlefMaksura($text, 'safebw');
    }

    public function normalizeAlefMaksuraXmlBw(string $text): string
    {
        return $this->normalizeAlefMaksura($text, 'xmlbw');
    }

    public function normalizeAlefMaksuraHsb(string $text): string
    {
        return $this->normalizeAlefMaksura($text, 'hsb');
    }

    public function normalizeAlefMaksura(string $text, string $encoding = 'ar'): string
    {
        return match ($this->normalizeEncoding($encoding)) {
            'ar' => str_replace('ى', 'ي', $text),
            'bw', 'safebw', 'xmlbw' => str_replace('Y', 'y', $text),
            'hsb' => str_replace('ý', 'y', $text),
        };
    }

    public function normalizeTehMarbutaAr(string $text): string
    {
        return $this->normalizeTehMarbuta($text, 'ar');
    }

    public function normalizeTehMarbutaBw(string $text): string
    {
        return $this->normalizeTehMarbuta($text, 'bw');
    }

    public function normalizeTehMarbutaSafeBw(string $text): string
    {
        return $this->normalizeTehMarbuta($text, 'safebw');
    }

    public function normalizeTehMarbutaXmlBw(string $text): string
    {
        return $this->normalizeTehMarbuta($text, 'xmlbw');
    }

    public function normalizeTehMarbutaHsb(string $text): string
    {
        return $this->normalizeTehMarbuta($text, 'hsb');
    }

    public function normalizeTehMarbuta(string $text, string $encoding = 'ar'): string
    {
        return match ($this->normalizeEncoding($encoding)) {
            'ar' => str_replace('ة', 'ه', $text),
            'bw', 'safebw', 'xmlbw' => str_replace('p', 'h', $text),
            'hsb' => str_replace('ħ', 'h', $text),
        };
    }

    public function normalizeOrthography(string $text, string $encoding = 'ar'): string
    {
        $normalized = $this->normalizeAlef($text, $encoding);
        $normalized = $this->normalizeAlefMaksura($normalized, $encoding);

        return $this->normalizeTehMarbuta($normalized, $encoding);
    }

    public function dediacAr(string $text): string
    {
        return $this->dediac($text, 'ar');
    }

    public function dediacBw(string $text): string
    {
        return $this->dediac($text, 'bw');
    }

    public function dediacSafeBw(string $text): string
    {
        return $this->dediac($text, 'safebw');
    }

    public function dediacXmlBw(string $text): string
    {
        return $this->dediac($text, 'xmlbw');
    }

    public function dediacHsb(string $text): string
    {
        return $this->dediac($text, 'hsb');
    }

    public function dediac(string $text, string $encoding = 'ar'): string
    {
        return match ($this->normalizeEncoding($encoding)) {
            'ar' => str_replace(self::AR_DIACRITICS, '', $text),
            'bw' => str_replace(self::BW_DIACRITICS, '', $text),
            'safebw' => str_replace(self::SAFEBW_DIACRITICS, '', $text),
            'xmlbw' => str_replace(self::XMLBW_DIACRITICS, '', $text),
            'hsb' => str_replace(self::HSB_DIACRITICS, '', $text),
        };
    }

    /**
     * @return array<int, string>
     */
    public function simpleWordTokenize(string $text, bool $splitDigits = false): array
    {
        return $this->wordTokenizer()->simpleWordTokenize($text, $splitDigits);
    }

    private function wordTokenizer(): CamelWordTokenizer
    {
        if (! $this->wordTokenizer instanceof CamelWordTokenizer) {
            $this->wordTokenizer = new CamelWordTokenizer;
        }

        return $this->wordTokenizer;
    }

    /**
     * @return 'ar'|'bw'|'safebw'|'xmlbw'|'hsb'
     */
    private function normalizeEncoding(string $encoding): string
    {
        $encoding = strtolower(str_replace(['_', '-', ' '], '', $encoding));

        return match ($encoding) {
            'ar', 'arabic' => 'ar',
            'bw', 'buckwalter' => 'bw',
            'safebw', 'safebuckwalter' => 'safebw',
            'xmlbw', 'xmlbuckwalter' => 'xmlbw',
            'hsb', 'habashsoudibuckwalter' => 'hsb',
            default => throw new InvalidArgumentException(
                sprintf('Unsupported encoding [%s]. Supported: ar, bw, safebw, xmlbw, hsb.', $encoding),
            ),
        };
    }
}
