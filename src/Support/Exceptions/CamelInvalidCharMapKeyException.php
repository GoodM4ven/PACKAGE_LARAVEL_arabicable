<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Support\Exceptions;

use InvalidArgumentException;

class CamelInvalidCharMapKeyException extends InvalidArgumentException
{
    public function __construct(
        public readonly string $key,
        string $message,
    ) {
        parent::__construct($message);
    }
}
