<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Support\Camel;

final class CamelStringUtils
{
    public static function isUnicodeString(mixed $value): bool
    {
        return is_string($value) && mb_check_encoding($value, 'UTF-8');
    }

    public static function forceUnicode(mixed $value, string $encoding = 'UTF-8'): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            if (mb_check_encoding($value, 'UTF-8')) {
                return $value;
            }

            return mb_convert_encoding($value, 'UTF-8', $encoding);
        }

        return (string) $value;
    }
}
