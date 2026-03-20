<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable;

final class Arabicable
{
    public function camel(): CamelTools
    {
        return app(CamelTools::class);
    }

    public function arabic(): Arabic
    {
        return app(Arabic::class);
    }

    public function filter(): ArabicFilter
    {
        return app(ArabicFilter::class);
    }
}
