<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \GoodMaven\Arabicable\CamelTools camel()
 * @method static \GoodMaven\Arabicable\Arabic arabic()
 * @method static \GoodMaven\Arabicable\ArabicFilter filter()
 *
 * @see \GoodMaven\Arabicable\Arabicable
 */
class Arabicable extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \GoodMaven\Arabicable\Arabicable::class;
    }
}
