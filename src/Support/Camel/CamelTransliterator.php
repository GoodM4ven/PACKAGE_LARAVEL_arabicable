<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Support\Camel;

use TypeError;
use ValueError;

final class CamelTransliterator
{
    private string $marker;

    public function __construct(
        private readonly CamelCharMapper $mapper,
        string $marker = '@@IGNORE@@',
    ) {
        if (! CamelStringUtils::isUnicodeString($marker)) {
            throw new TypeError('Marker is not a Unicode string.');
        }

        if ($marker === '') {
            throw new ValueError('Marker is empty.');
        }

        if (preg_match('/\s/u', $marker) === 1) {
            throw new ValueError('Marker contains whitespace.');
        }

        $this->marker = $marker;
    }

    public function transliterate(
        string $input,
        bool $stripMarkers = false,
        bool $ignoreMarkers = false,
    ): string {
        if ($input === '') {
            return '';
        }

        $pattern = '/('.preg_quote($this->marker, '/').'\S+)/u';
        $parts = preg_split($pattern, $input, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($parts === false) {
            return $this->mapper->mapString($input);
        }

        $buffer = [];

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            if (str_starts_with($part, $this->marker)) {
                $payload = mb_substr($part, mb_strlen($this->marker, 'UTF-8'), null, 'UTF-8');

                if ($ignoreMarkers) {
                    if (! $stripMarkers) {
                        $buffer[] = $this->marker;
                    }

                    $buffer[] = $this->mapper->mapString($payload);
                } else {
                    if ($stripMarkers) {
                        $buffer[] = $payload;
                    } else {
                        $buffer[] = $part;
                    }
                }

                continue;
            }

            $buffer[] = $this->mapper->mapString($part);
        }

        return implode('', $buffer);
    }
}
