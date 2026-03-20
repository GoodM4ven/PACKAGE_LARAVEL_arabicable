<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Support\Config;

class ArabicableConfig
{
    public static function get(string $key, mixed $default = null): mixed
    {
        return config($key, $default);
    }

    public static function set(string $key, mixed $value): void
    {
        config()->set($key, $value);
    }
}
