<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Support\Camel;

use GoodMaven\Arabicable\Support\Exceptions\CamelBuiltinCharMapNotFoundException;
use GoodMaven\Arabicable\Support\Exceptions\CamelInvalidCharMapKeyException;
use RuntimeException;
use TypeError;

final class CamelCharMapper
{
    /**
     * @var list<string>
     */
    public const BUILTIN_CHARMAPS = [
        'ar2bw',
        'ar2safebw',
        'ar2xmlbw',
        'ar2hsb',
        'bw2ar',
        'bw2safebw',
        'bw2xmlbw',
        'bw2hsb',
        'safebw2ar',
        'safebw2bw',
        'safebw2xmlbw',
        'safebw2hsb',
        'xmlbw2ar',
        'xmlbw2bw',
        'xmlbw2safebw',
        'xmlbw2hsb',
        'hsb2ar',
        'hsb2bw',
        'hsb2safebw',
        'hsb2xmlbw',
        'arclean',
    ];

    /**
     * @var array<string, string|null>
     */
    private array $charMap;

    private ?string $default;

    public function __construct(iterable $charMap, ?string $default = null)
    {
        if ($default !== null && ! CamelStringUtils::isUnicodeString($default)) {
            throw new TypeError(
                sprintf(
                    'Expected a Unicode string or null value for default, got %s instead.',
                    get_debug_type($default),
                ),
            );
        }

        $this->charMap = self::expandCharMap($charMap);
        $this->default = $default;
    }

    public function __invoke(string $input): string
    {
        return $this->mapString($input);
    }

    /**
     * @return list<string>
     */
    public static function builtinCharmaps(): array
    {
        return self::BUILTIN_CHARMAPS;
    }

    public static function mapperFromJson(string $path): self
    {
        if (! is_file($path)) {
            throw new RuntimeException(sprintf('Char map file not found at [%s].', $path));
        }

        $payload = json_decode((string) file_get_contents($path), true);

        if (! is_array($payload)) {
            throw new RuntimeException(sprintf('Invalid char map JSON at [%s].', $path));
        }

        $charMap = $payload['charMap'] ?? [];
        $default = $payload['default'] ?? null;

        if (! is_array($charMap)) {
            throw new RuntimeException(sprintf('Invalid charMap payload in [%s].', $path));
        }

        if (! is_string($default) && $default !== null) {
            throw new RuntimeException(sprintf('Invalid default payload in [%s].', $path));
        }

        return new self($charMap, $default);
    }

    public static function builtinMapper(string $mapName): self
    {
        if (! in_array($mapName, self::BUILTIN_CHARMAPS, true)) {
            throw new CamelBuiltinCharMapNotFoundException(
                $mapName,
                sprintf('No built in mapping with name [%s] was found.', $mapName),
            );
        }

        $mapPath = self::builtinCharmapBasePath().DIRECTORY_SEPARATOR.$mapName.'_map.json';

        return self::mapperFromJson($mapPath);
    }

    public function mapString(string $input): string
    {
        if (! CamelStringUtils::isUnicodeString($input)) {
            throw new TypeError(sprintf('Expected Unicode string as input, got %s instead.', get_debug_type($input)));
        }

        if ($input === '') {
            return '';
        }

        $characters = preg_split('//u', $input, -1, PREG_SPLIT_NO_EMPTY);

        if ($characters === false) {
            throw new RuntimeException('Could not split input string into Unicode characters.');
        }

        $buffer = [];

        foreach ($characters as $character) {
            if (array_key_exists($character, $this->charMap)) {
                $transliteration = $this->charMap[$character];
            } else {
                $transliteration = $this->default;
            }

            if ($transliteration === null) {
                $buffer[] = $character;
            } else {
                $buffer[] = $transliteration;
            }
        }

        return implode('', $buffer);
    }

    /**
     * @param  iterable<array-key, mixed>  $charMap
     * @return array<string, string|null>
     */
    private static function expandCharMap(iterable $charMap): array
    {
        $expanded = [];

        foreach ($charMap as $key => $value) {
            if (! is_string($key) || ! CamelStringUtils::isUnicodeString($key)) {
                throw new TypeError(sprintf('Expected string as key. Got %s instead.', get_debug_type($key)));
            }

            if (! is_string($value) && $value !== null) {
                throw new TypeError(
                    sprintf(
                        'Expected a Unicode string or null value for key value, got %s instead.',
                        get_debug_type($value),
                    ),
                );
            }

            if (is_string($value) && ! CamelStringUtils::isUnicodeString($value)) {
                throw new TypeError(sprintf('Expected Unicode string for key [%s] value.', $key));
            }

            $length = mb_strlen($key, 'UTF-8');

            if ($length === 1) {
                $expanded[$key] = $value;

                continue;
            }

            if ($length === 3 && mb_substr($key, 1, 1, 'UTF-8') === '-') {
                $start = mb_substr($key, 0, 1, 'UTF-8');
                $end = mb_substr($key, 2, 1, 'UTF-8');

                $startOrd = mb_ord($start, 'UTF-8');
                $endOrd = mb_ord($end, 'UTF-8');

                if ($startOrd >= $endOrd) {
                    throw new CamelInvalidCharMapKeyException($key, 'Invalid character range order.');
                }

                for ($codepoint = $startOrd; $codepoint <= $endOrd; $codepoint++) {
                    $expanded[mb_chr($codepoint, 'UTF-8')] = $value;
                }

                continue;
            }

            throw new CamelInvalidCharMapKeyException($key, 'Invalid character or character range.');
        }

        return $expanded;
    }

    private static function builtinCharmapBasePath(): string
    {
        return dirname(__DIR__, 3)
            .DIRECTORY_SEPARATOR.'resources'
            .DIRECTORY_SEPARATOR.'raw-data'
            .DIRECTORY_SEPARATOR.'_camel'
            .DIRECTORY_SEPARATOR.'charmaps';
    }
}
