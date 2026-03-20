<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Support\Exceptions;

use InvalidArgumentException;

class CamelBuiltinCharMapNotFoundException extends InvalidArgumentException
{
    public function __construct(
        public readonly string $mapName,
        string $message,
    ) {
        parent::__construct($message);
    }
}
