<?php

declare(strict_types=1);
use GoodMaven\Arabicable\ArabicableServiceProvider;
use GoodMaven\TailwindMerge\TailwindMergeServiceProvider;
use Laravel\Boost\BoostServiceProvider;
use Livewire\LivewireServiceProvider;
use Workbench\App\Providers\TestableWorkbenchServiceProvider;

return [
    ArabicableServiceProvider::class,
    TailwindMergeServiceProvider::class,
    TestableWorkbenchServiceProvider::class,
    BoostServiceProvider::class,
    LivewireServiceProvider::class,
];
