<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Support\Text;

use GoodMaven\Arabicable\Arabic;
use GoodMaven\Arabicable\Facades\ArabicFilter;
use GoodMaven\Arabicable\Support\Config\ArabicableConfig;
use GoodMaven\Arabicable\Support\Data\DelimitedFileReader;
use Illuminate\Support\Facades\Cache;

class ArabicVocalizations
{
    private const CACHE_VERSION_KEY = 'arabicable.vocalizations.cache.version';

    public function vocalize(string $word): string
    {
        $canonical = $this->canonicalKey($word);
        $normalized = ArabicFilter::forSearch($word);

        if ($canonical === '' && $normalized === '') {
            return $word;
        }

        $map = $this->map();

        if ($canonical !== '' && isset($map[$canonical])) {
            return $map[$canonical];
        }

        return $normalized !== '' && isset($map[$normalized])
            ? $map[$normalized]
            : $word;
    }

    public function clearCache(): void
    {
        Cache::put(self::CACHE_VERSION_KEY, $this->cacheVersion() + 1);
    }

    /**
     * @return array<string, string>
     */
    private function map(): array
    {
        $path = (string) ArabicableConfig::get('arabicable.data_sources.vocalizations', '');

        if ($path === '' || ! is_file($path)) {
            return [];
        }

        $cacheKey = sprintf(
            'arabicable.vocalizations.%d.%s',
            $this->cacheVersion(),
            md5($path),
        );

        /** @var array<string, string> $map */
        $map = Cache::rememberForever($cacheKey, function () use ($path): array {
            return str_ends_with(strtolower($path), '.json')
                ? $this->mapFromJson($path)
                : $this->mapFromTsv($path);
        });

        return $map;
    }

    /**
     * @return array<string, string>
     */
    private function mapFromJson(string $path): array
    {
        $payload = json_decode((string) file_get_contents($path), true);

        if (! is_array($payload)) {
            return [];
        }

        $map = [];

        foreach ($payload as $word => $vocalized) {
            if (! is_string($word) || ! is_string($vocalized)) {
                continue;
            }

            $key = ArabicFilter::forSearch($word);
            $canonical = $this->canonicalKey($word);
            $value = trim($vocalized);

            if (($key === '' && $canonical === '') || $value === '') {
                continue;
            }

            if ($canonical !== '') {
                $map[$canonical] = $value;
            }

            if ($key !== '') {
                $map[$key] = $value;
            }
        }

        return $map;
    }

    /**
     * @return array<string, string>
     */
    private function mapFromTsv(string $path): array
    {
        $map = [];
        $headerHandled = false;
        $headerMap = [];

        foreach (DelimitedFileReader::readRows($path, "\t", skipHeader: false) as $cols) {
            if (! $headerHandled && $this->looksLikeHeader($cols)) {
                $headerHandled = true;
                $headerMap = $this->buildHeaderMap($cols);

                continue;
            }

            $headerHandled = true;

            $word = trim((string) ($cols[$headerMap['word'] ?? 0] ?? ''));
            $vocalized = trim((string) ($cols[$headerMap['vocalized'] ?? 1] ?? ''));

            if ($word === '' || $vocalized === '') {
                continue;
            }

            $key = ArabicFilter::forSearch($word);
            $canonical = $this->canonicalKey($word);

            if ($key === '' && $canonical === '') {
                continue;
            }

            if ($canonical !== '') {
                $map[$canonical] = $vocalized;
            }

            if ($key !== '') {
                $map[$key] = $vocalized;
            }
        }

        return $map;
    }

    /**
     * @param  array<int, string>  $cols
     */
    private function looksLikeHeader(array $cols): bool
    {
        $first = mb_strtolower(trim((string) ($cols[0] ?? '')), 'UTF-8');
        $second = mb_strtolower(trim((string) ($cols[1] ?? '')), 'UTF-8');

        return in_array($first, ['word', 'token'], true)
            && in_array($second, ['vocalized', 'diacritized'], true);
    }

    /**
     * @param  array<int, string>  $cols
     * @return array<string, int>
     */
    private function buildHeaderMap(array $cols): array
    {
        $map = ['word' => 0, 'vocalized' => 1];

        foreach ($cols as $index => $column) {
            $normalized = mb_strtolower(trim($column), 'UTF-8');

            if (in_array($normalized, ['word', 'token'], true)) {
                $map['word'] = $index;
            }

            if (in_array($normalized, ['vocalized', 'diacritized'], true)) {
                $map['vocalized'] = $index;
            }
        }

        return $map;
    }

    private function cacheVersion(): int
    {
        /** @var int $version */
        $version = Cache::rememberForever(self::CACHE_VERSION_KEY, static fn (): int => 1);

        return $version;
    }

    private function canonicalKey(string $word): string
    {
        $word = trim($word);

        if ($word === '') {
            return '';
        }

        $word = app(Arabic::class)->removeHarakat($word);
        $word = preg_replace('/[^\p{Arabic}\p{N}\s]+/u', ' ', $word) ?? $word;
        $word = app(Arabic::class)->normalizeSpaces($word);
        $word = strtr($word, [
            'أ' => 'ا',
            'إ' => 'ا',
            'آ' => 'ا',
            'ٱ' => 'ا',
            'ى' => 'ي',
            'ؤ' => 'و',
            'ئ' => 'ي',
        ]);

        return trim($word);
    }
}
