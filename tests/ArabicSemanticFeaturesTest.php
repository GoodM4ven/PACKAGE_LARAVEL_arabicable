<?php

declare(strict_types=1);

use GoodMaven\Arabicable\Facades\Arabic;

it('keeps harakat style formatting without dictionary synthesis', function (): void {
    expect(Arabic::addHarakat('اباءكم'))->toBe('اباءكم');
});
