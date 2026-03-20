<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

// use Livewire\Livewire;
// use Workbench\App\Livewire\Demo;

final class TestableWorkbenchServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Livewire::addNamespace(
            namespace: 'arabicable',
            classNamespace: 'Workbench\\App\\Livewire',
            classPath: app_path('Livewire'),
            classViewPath: resource_path('views/livewire')
        );
    }
}
